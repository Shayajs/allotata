<?php

namespace App\Observers;

use App\Models\Entreprise;
use App\Models\EntrepriseSecurityHistory;
use Illuminate\Support\Facades\Request;

class EntrepriseObserver
{
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
    }
}
