<?php

namespace App\Traits;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\EssaiGratuit;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEssaisGratuits
{
    /**
     * Relation : Tous les essais gratuits de cette entité
     */
    public function essaisGratuits(): MorphMany
    {
        return $this->morphMany(EssaiGratuit::class, 'essayable');
    }

    /**
     * Récupère l'essai actif pour un type donné
     */
    public function essaiActif(string $type): ?EssaiGratuit
    {
        return $this->essaisGratuits()
            ->where('type_abonnement', $type)
            ->where('statut', 'actif')
            ->where('date_fin', '>', now())
            ->first();
    }

    /**
     * Vérifie si un essai est en cours pour ce type
     */
    public function aEssaiEnCours(string $type): bool
    {
        return $this->essaiActif($type) !== null;
    }

    /**
     * Un seul essai self-service par type, définitivement.
     * L'admin peut en accorder d'autres s'il n'y a pas d'abonnement payant.
     */
    public function peutDemarrerEssai(string $type): bool
    {
        return ! $this->essaisGratuits()
            ->where('type_abonnement', $type)
            ->exists();
    }

    /**
     * Abonnement payant (Stripe, échéances, manuel) — hors essai gratuit.
     */
    public function aAbonnementPayantPour(string $type): bool
    {
        if ($this instanceof User && $type === 'premium') {
            if ($this->hasActiveManualPremium()) {
                return true;
            }

            try {
                return $this->subscribed('default');
            } catch (\Throwable $e) {
                return false;
            }
        }

        if ($this instanceof Entreprise && in_array($type, ['site_web', 'multi_personnes'], true)) {
            $sub = $this->abonnements()->where('type', $type)->first();

            return $sub?->estAbonnementPayant() ?? false;
        }

        return false;
    }

    /**
     * Récupère le dernier essai pour un type (même expiré)
     */
    public function dernierEssai(string $type): ?EssaiGratuit
    {
        return $this->essaisGratuits()
            ->where('type_abonnement', $type)
            ->latest('date_debut')
            ->first();
    }

    /**
     * Démarre un essai gratuit
     */
    public function demarrerEssai(
        string $type,
        int $jours = 7,
        string $source = 'bouton_cta',
        ?string $codePromo = null,
        ?int $parrainId = null,
        ?int $adminId = null,
        ?string $notesAdmin = null
    ): EssaiGratuit {
        // Calcule la valeur de l'essai
        $types = EssaiGratuit::getTypesAbonnement();
        $valeurMensuelle = $types[$type]['valeur_mensuelle'] ?? 0;
        $valeurEssai = ($valeurMensuelle / 30) * $jours;

        $dateFin = now()->addDays($jours);

        $essai = $this->essaisGratuits()->create([
            'type_abonnement' => $type,
            'date_debut' => now(),
            'date_fin' => $dateFin,
            'duree_jours' => $jours,
            'statut' => 'actif',
            'source' => $source,
            'code_promo_utilise' => $codePromo,
            'parrain_id' => $parrainId,
            'accorde_par_admin_id' => $adminId,
            'notes_admin' => $notesAdmin,
            'valeur_essai' => $valeurEssai,
            'ip_activation' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'utm_source' => request()->get('utm_source'),
            'utm_medium' => request()->get('utm_medium'),
            'utm_campaign' => request()->get('utm_campaign'),
        ]);

        $this->provisionnerAccesEssai($type, $dateFin);

        return $essai;
    }

    /**
     * Ouvre réellement l'accès (Cashier trial_ends_at / option entreprise)
     * en plus de l'enregistrement EssaiGratuit.
     */
    public function provisionnerAccesEssai(string $type, $dateFin): void
    {
        if ($this instanceof User && $type === 'premium') {
            $this->forceFill(['trial_ends_at' => $dateFin])->save();

            return;
        }

        if ($this instanceof Entreprise && in_array($type, ['site_web', 'multi_personnes'], true)) {
            $existant = $this->abonnements()->where('type', $type)->first();

            $payload = [
                'trial_ends_at' => $dateFin,
                'actif_jusqu' => $dateFin->toDateString(),
                'notes_manuel' => 'Essai gratuit jusqu\'au '.$dateFin->format('d/m/Y'),
            ];

            if (! $existant) {
                $payload['name'] = 'essai_'.$type;
                $payload['est_manuel'] = false;
                $payload['stripe_id'] = null;
                $payload['stripe_status'] = null;
                $payload['stripe_price'] = null;
            } elseif ($existant->est_manuel && ! $existant->estActif()) {
                $payload['est_manuel'] = false;
            }

            EntrepriseSubscription::updateOrCreate(
                [
                    'entreprise_id' => $this->id,
                    'type' => $type,
                ],
                $payload
            );
        }
    }

    /**
     * Resynchronise l'accès d'un essai encore en cours, sans déplacer la date de fin.
     */
    public function reparerAccesEssaiActif(string $type): bool
    {
        $essai = $this->essaiActif($type);
        if (! $essai || $this->aAbonnementPayantPour($type)) {
            return false;
        }

        $dateFin = $essai->date_fin;

        if ($this instanceof User && $type === 'premium') {
            if ($this->trial_ends_at && abs($this->trial_ends_at->diffInSeconds($dateFin, true)) <= 60) {
                return false;
            }

            $this->forceFill(['trial_ends_at' => $dateFin])->save();

            return true;
        }

        if ($this instanceof Entreprise && in_array($type, ['site_web', 'multi_personnes'], true)) {
            $sub = $this->abonnements()->where('type', $type)->first();
            $dejaOk = $sub
                && $sub->trial_ends_at
                && abs($sub->trial_ends_at->diffInSeconds($dateFin, true)) <= 60
                && $sub->actif_jusqu
                && $sub->actif_jusqu->toDateString() === $dateFin->toDateString();

            if ($dejaOk) {
                return false;
            }

            $this->provisionnerAccesEssai($type, $dateFin);

            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'entité a accès à une fonctionnalité via essai gratuit
     */
    public function aAccesViaEssai(string $type): bool
    {
        $essai = $this->essaiActif($type);
        return $essai !== null && $essai->estEnCours();
    }

    /**
     * Récupère les jours restants de l'essai actif
     */
    public function joursRestantsEssai(string $type): int
    {
        $essai = $this->essaiActif($type);
        return $essai ? $essai->joursRestants() : 0;
    }

    /**
     * Récupère les infos de l'essai actif pour l'affichage
     */
    public function getInfosEssai(string $type): ?array
    {
        $essai = $this->essaiActif($type);
        
        if (!$essai) {
            // Vérifie s'il peut démarrer un essai
            if ($this->peutDemarrerEssai($type)) {
                return [
                    'statut' => 'disponible',
                    'peut_demarrer' => true,
                ];
            }
            
            $dernierEssai = $this->dernierEssai($type);
            if ($dernierEssai) {
                return [
                    'statut' => $dernierEssai->statut,
                    'peut_demarrer' => false,
                    'date_expiration' => $dernierEssai->date_fin,
                    'nouvel_essai_interdit' => true,
                ];
            }
            
            return null;
        }

        return [
            'statut' => 'actif',
            'essai_id' => $essai->id,
            'date_fin' => $essai->date_fin,
            'jours_restants' => $essai->joursRestants(),
            'heures_restantes' => $essai->heuresRestantes(),
            'peut_demarrer' => false,
        ];
    }

    /**
     * Enregistre une connexion pendant l'essai
     */
    public function enregistrerConnexionEssai(string $type): void
    {
        $essai = $this->essaiActif($type);
        if ($essai) {
            $essai->enregistrerConnexion();
        }
    }

    /**
     * Enregistre une action pendant l'essai
     */
    public function enregistrerActionEssai(string $type): void
    {
        $essai = $this->essaiActif($type);
        if ($essai) {
            $essai->enregistrerAction();
        }
    }
}
