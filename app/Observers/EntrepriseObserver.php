<?php

namespace App\Observers;

use App\Models\Entreprise;
use App\Models\EntrepriseSecurityHistory;
use Illuminate\Support\Facades\Request;

class EntrepriseObserver
{
    public function created(Entreprise $entreprise): void
    {
        if ($entreprise->est_verifiee) {
            return;
        }

        try {
            app(\App\Services\AdminNotificationService::class)->notifyEntreprisePendingValidation($entreprise);
        } catch (\Throwable $e) {
            \Log::warning('Notification admin entreprise à valider: '.$e->getMessage());
        }
    }

    /**
     * Handle the Entreprise "updated" event.
     */
    public function updated(Entreprise $entreprise): void
    {
        // Si l'email a changé, enregistrer dans l'historique
        if ($entreprise->isDirty('email')) {
            $oldEmail = $entreprise->getOriginal('email');
            $newEmail = $entreprise->email;
            if ($oldEmail && $newEmail) {
                EntrepriseSecurityHistory::recordEmailChange(
                    $entreprise,
                    $oldEmail,
                    $newEmail,
                    auth()->id(), // null si auto, sinon ID de l'admin
                    Request::ip(),
                    Request::userAgent(),
                    'Changement d\'email'
                );
            }
        }

        // Invalider le cache public si des champs affectant l'affichage public ont changé
        // Liste des champs qui affectent l'affichage public
        $publicFields = [
            'nom', 'slug', 'type_activite', 'description', 'mots_cles',
            'logo', 'image_fond', 'ville', 'adresse_rue', 'code_postal',
            'latitude', 'longitude', 'afficher_adresse_complete',
            'rayon_deplacement', 'afficher_nom_gerant', 'prix_negociables',
            'rdv_uniquement_messagerie', 'rdv_sur_demande_message', 'est_verifiee', 'phrase_accroche',
            'site_web_externe'
        ];

        // Vérifier si un champ public a changé
        $hasPublicFieldChanged = false;
        foreach ($publicFields as $field) {
            if ($entreprise->isDirty($field)) {
                $hasPublicFieldChanged = true;
                break;
            }
        }

        if ($hasPublicFieldChanged) {
            \App\Services\CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);
        }
    }
}
