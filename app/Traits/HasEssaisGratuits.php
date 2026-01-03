<?php

namespace App\Traits;

use App\Models\EssaiGratuit;
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
     * Vérifie si l'entité peut démarrer un essai pour ce type
     * (pas d'essai dans l'année écoulée)
     */
    public function peutDemarrerEssai(string $type): bool
    {
        // Vérifie qu'il n'a pas déjà eu un essai dans l'année
        return !$this->essaisGratuits()
            ->where('type_abonnement', $type)
            ->where('date_debut', '>=', now()->subYear())
            ->exists();
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

        return $this->essaisGratuits()->create([
            'type_abonnement' => $type,
            'date_debut' => now(),
            'date_fin' => now()->addDays($jours),
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
            
            // Vérifie s'il a un essai expiré récent
            $dernierEssai = $this->dernierEssai($type);
            if ($dernierEssai) {
                return [
                    'statut' => $dernierEssai->statut,
                    'peut_demarrer' => false,
                    'date_expiration' => $dernierEssai->date_fin,
                    'prochaine_eligibilite' => $dernierEssai->date_debut->addYear(),
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
