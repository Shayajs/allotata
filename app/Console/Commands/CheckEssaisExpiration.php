<?php

namespace App\Console\Commands;

use App\Models\EssaiGratuit;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEssaisExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'essais:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les essais gratuits expirants et envoie des notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des essais gratuits...');

        // 1. Envoyer les rappels (J-2)
        $this->sendRappels();

        // 2. Marquer les essais expirés
        $this->marquerExpires();

        // 3. Envoyer les notifications d'expiration
        $this->sendNotificationsExpiration();

        // 4. Envoyer les relances (J+3)
        $this->sendRelances();

        $this->info('Vérification terminée.');

        return Command::SUCCESS;
    }

    /**
     * Envoie les rappels 2 jours avant expiration
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
            $this->createNotification(
                $essai,
                'rappel_essai',
                '⏰ Votre essai gratuit expire bientôt !',
                "Votre essai gratuit expire dans {$essai->joursRestants()} jour(s). Profitez-en pour découvrir toutes les fonctionnalités avant la fin !"
            );

            $essai->update(['notification_rappel_envoye_le' => now()]);
            $count++;
        }

        $this->info("  → {$count} rappel(s) envoyé(s)");
    }

    /**
     * Marque les essais expirés
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
     * Envoie les notifications d'expiration le jour même
     */
    private function sendNotificationsExpiration(): void
    {
        $essais = EssaiGratuit::where('statut', 'expire')
            ->whereNull('notification_expiration_envoye_le')
            ->where('date_fin', '>=', now()->subDay())
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            $typeLabel = EssaiGratuit::getTypesAbonnement()[$essai->type_abonnement]['label'] ?? $essai->type_abonnement;
            
            $this->createNotification(
                $essai,
                'expiration_essai',
                '📦 Votre essai gratuit est terminé',
                "Votre essai gratuit \"{$typeLabel}\" a pris fin. Abonnez-vous maintenant pour continuer à profiter de toutes les fonctionnalités !"
            );

            $essai->update(['notification_expiration_envoye_le' => now()]);
            $count++;
        }

        $this->info("  → {$count} notification(s) d'expiration envoyée(s)");
    }

    /**
     * Envoie les relances 3 jours après expiration
     */
    private function sendRelances(): void
    {
        $essais = EssaiGratuit::where('statut', 'expire')
            ->whereNull('notification_relance_envoye_le')
            ->whereNotNull('notification_expiration_envoye_le')
            ->where('date_fin', '<=', now()->subDays(3))
            ->get();

        $count = 0;
        foreach ($essais as $essai) {
            $typeLabel = EssaiGratuit::getTypesAbonnement()[$essai->type_abonnement]['label'] ?? $essai->type_abonnement;
            
            $this->createNotification(
                $essai,
                'relance_essai',
                '💡 Vous nous manquez !',
                "N'avez-vous pas apprécié votre essai de \"{$typeLabel}\" ? Dites-nous ce que nous pourrions améliorer !"
            );

            $essai->update(['notification_relance_envoye_le' => now()]);
            $count++;
        }

        $this->info("  → {$count} relance(s) envoyée(s)");
    }

    /**
     * Crée une notification pour l'essayable
     */
    private function createNotification(EssaiGratuit $essai, string $type, string $titre, string $message): void
    {
        try {
            $essayable = $essai->essayable;
            
            if (!$essayable) {
                Log::warning("EssaiGratuit #{$essai->id} n'a pas d'essayable associé");
                return;
            }

            // Pour les entreprises, on notifie le propriétaire
            if ($essai->essayable_type === 'App\\Models\\Entreprise') {
                $userId = $essayable->user_id;
                $lien = route('entreprise.dashboard', ['slug' => $essayable->slug]);
            } else {
                $userId = $essayable->id;
                $lien = route('subscription.index');
            }

            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
                'lien' => $lien,
                'est_lue' => false,
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création de notification pour essai #{$essai->id}: " . $e->getMessage());
        }
    }
}
