<?php

namespace App\Console\Commands;

use App\Mail\EssaiGratuitExpireMail;
use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\EssaiGratuit;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckEssaisExpiration extends Command
{
    protected $signature = 'essais:check-expiration';

    protected $description = 'Vérifie les essais gratuits expirants, retire l\'accès et notifie (essai arrêté, plus de nouvel essai)';

    public function handle()
    {
        $this->info('Vérification des essais gratuits...');

        $this->reparerEssaisActifs();
        $this->sendRappels();
        $this->marquerExpires();
        $this->rattraperAccesOublies();
        $this->sendNotificationsExpiration();
        $this->sendRelances();

        $this->info('Vérification terminée.');

        return Command::SUCCESS;
    }

    /**
     * Essais encore en cours : ouvre l'accès manquant, sans prolonger la date de fin.
     */
    private function reparerEssaisActifs(): void
    {
        $essais = EssaiGratuit::query()->actifs()->with('essayable')->get();
        $count = 0;

        foreach ($essais as $essai) {
            $cible = $essai->essayable;
            if (! $cible || ! method_exists($cible, 'reparerAccesEssaiActif')) {
                continue;
            }

            if ($cible->reparerAccesEssaiActif($essai->type_abonnement)) {
                $count++;
            }
        }

        $this->info("  → {$count} essai(s) actif(s) resynchronisé(s) (date de fin inchangée)");
    }

    /**
     * Rappels 2 jours avant expiration.
     */
    private function sendRappels(): void
    {
        $essais = EssaiGratuit::where('statut', 'actif')
            ->whereNull('notification_rappel_envoye_le')
            ->where('date_fin', '<=', now()->addDays(2))
            ->where('date_fin', '>', now())
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            $jours = max(1, $essai->joursRestants());
            $typeLabel = $essai->typeLabel();

            $ok = $this->createNotification(
                $essai,
                'rappel_essai',
                'Votre essai gratuit expire bientôt',
                "Votre essai « {$typeLabel} » expire dans {$jours} jour(s). Après cette date, l'accès sera retiré et vous ne pourrez plus réessayer. Abonnez-vous pour continuer."
            );

            if ($ok) {
                $essai->update(['notification_rappel_envoye_le' => now()]);
                $count++;
            }
        }

        $this->info("  → {$count} rappel(s) envoyé(s)");
    }

    /**
     * Marque les essais expirés et retire l'accès.
     */
    private function marquerExpires(): void
    {
        $essais = EssaiGratuit::where('statut', 'actif')
            ->where('date_fin', '<=', now())
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            $essai->marquerExpire();
            $count++;
        }

        $this->info("  → {$count} essai(s) marqué(s) comme expiré(s)");
    }

    /**
     * Annule tout accès d'essai encore ouvert alors que l'essai est terminé.
     */
    private function rattraperAccesOublies(): void
    {
        $countUsers = 0;
        User::query()
            ->where('trial_ends_at', '>', now())
            ->whereDoesntHave('essaisGratuits', function ($q) {
                $q->where('type_abonnement', 'premium')
                    ->where('statut', 'actif')
                    ->where('date_fin', '>', now());
            })
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$countUsers) {
                foreach ($users as $user) {
                    $user->forceFill(['trial_ends_at' => now()->subSecond()])->save();
                    $countUsers++;
                }
            });

        $countSubs = 0;
        EntrepriseSubscription::query()
            ->whereNull('stripe_id')
            ->where(function ($q) {
                $q->where('trial_ends_at', '>', now())
                    ->orWhere(function ($q2) {
                        $q2->where('name', 'like', 'essai_%')
                            ->where(function ($q3) {
                                $q3->whereNull('actif_jusqu')
                                    ->orWhereDate('actif_jusqu', '>=', now()->toDateString());
                            });
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('notes_manuel', 'like', 'Essai gratuit%')
                            ->where(function ($q3) {
                                $q3->whereNull('actif_jusqu')
                                    ->orWhereDate('actif_jusqu', '>=', now()->toDateString());
                            });
                    });
            })
            ->with('entreprise')
            ->orderBy('id')
            ->chunkById(100, function ($subs) use (&$countSubs) {
                foreach ($subs as $sub) {
                    $entreprise = $sub->entreprise;
                    if ($entreprise && $entreprise->aEssaiEnCours($sub->type)) {
                        continue;
                    }
                    if ($sub->est_manuel && ! $sub->estIssuEssaiGratuit()) {
                        continue;
                    }
                    if ($sub->estAbonnementPayant()) {
                        continue;
                    }

                    $sub->update([
                        'trial_ends_at' => null,
                        'actif_jusqu' => now()->subDay()->toDateString(),
                    ]);
                    $countSubs++;
                }
            });

        $this->info("  → {$countUsers} accès Premium d'essai oublié(s) retiré(s), {$countSubs} option(s) entreprise d'essai retirée(s)");
    }

    /**
     * Notifie l'arrêt d'essai (y compris rattrapage si un cron a été manqué).
     */
    private function sendNotificationsExpiration(): void
    {
        $essais = EssaiGratuit::where('statut', 'expire')
            ->whereNull('notification_expiration_envoye_le')
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            if ($this->notifierExpiration($essai)) {
                $count++;
            }
        }

        $this->info("  → {$count} notification(s) d'arrêt d'essai envoyée(s)");
    }

    /**
     * Relance 3 jours après arrêt : abonnement uniquement, pas de nouvel essai.
     */
    private function sendRelances(): void
    {
        $essais = EssaiGratuit::where('statut', 'expire')
            ->whereNull('notification_relance_envoye_le')
            ->whereNotNull('notification_expiration_envoye_le')
            ->where('notification_expiration_envoye_le', '<=', now()->subDays(3))
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            $typeLabel = $essai->typeLabel();

            $ok = $this->createNotification(
                $essai,
                'relance_essai',
                'Votre essai est arrêté — abonnez-vous',
                "Votre essai « {$typeLabel} » est arrêté. Vous ne pouvez plus en démarrer un nouveau. Abonnez-vous pour retrouver l'accès."
            );

            if ($ok) {
                $essai->update(['notification_relance_envoye_le' => now()]);
                $count++;
            }
        }

        $this->info("  → {$count} relance(s) envoyée(s)");
    }

    private function notifierExpiration(EssaiGratuit $essai): bool
    {
        if ($essai->notification_expiration_envoye_le) {
            return false;
        }

        $typeLabel = $essai->typeLabel();

        $ok = $this->createNotification(
            $essai,
            'expiration_essai',
            'Votre essai gratuit est arrêté',
            "Votre essai « {$typeLabel} » est terminé. L'accès a été retiré. Un nouvel essai n'est plus possible : abonnez-vous pour continuer.",
            withEmail: true
        );

        if ($ok) {
            $essai->update(['notification_expiration_envoye_le' => now()]);
        }

        return $ok;
    }

    /**
     * @return bool true si le destinataire a été trouvé (canaux selon préférences)
     */
    private function createNotification(
        EssaiGratuit $essai,
        string $type,
        string $titre,
        string $message,
        bool $withEmail = false
    ): bool {
        try {
            $destinataire = $this->resolveDestinataire($essai);
            if (! $destinataire) {
                Log::warning("EssaiGratuit #{$essai->id} : destinataire introuvable");

                return false;
            }

            [$user, $lien] = $destinataire;
            $typeLabel = $essai->typeLabel();

            $emailCallback = null;
            if ($withEmail && $user->email) {
                $emailCallback = fn () => Mail::to($user->email)->send(
                    new EssaiGratuitExpireMail($user, $typeLabel, $lien)
                );
            }

            Notification::creer(
                $user->id,
                $type,
                $titre,
                $message,
                $lien,
                [
                    'essai_id' => $essai->id,
                    'type_abonnement' => $essai->type_abonnement,
                    'nouvel_essai_interdit' => true,
                ],
                $emailCallback
            );

            return true;
        } catch (\Throwable $e) {
            Log::error("Erreur notification essai #{$essai->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * @return array{0: User, 1: string}|null
     */
    private function resolveDestinataire(EssaiGratuit $essai): ?array
    {
        $essayable = $essai->essayable;
        if (! $essayable) {
            return null;
        }

        if ($essayable instanceof Entreprise) {
            $user = $essayable->user;
            if (! $user) {
                return null;
            }

            return [
                $user,
                route('entreprise.dashboard', ['slug' => $essayable->slug, 'tab' => 'abonnements']),
            ];
        }

        if ($essayable instanceof User) {
            return [
                $essayable,
                route('settings.index', ['tab' => 'subscription']),
            ];
        }

        return null;
    }
}
