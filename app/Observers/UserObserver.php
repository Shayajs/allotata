<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserSecurityHistory;
use Illuminate\Support\Facades\Request;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Si le mot de passe a changé, déverrouiller le compte et enregistrer dans l'historique
        if ($user->isDirty('password')) {
            // Enregistrer l'ancien mot de passe dans l'historique
            $oldPasswordHash = $user->getOriginal('password');
            if ($oldPasswordHash) {
                UserSecurityHistory::recordPasswordChange(
                    $user,
                    $oldPasswordHash,
                    auth()->id(), // null si auto, sinon ID de l'admin
                    Request::ip(),
                    Request::userAgent(),
                    'Changement de mot de passe'
                );
            }
            
            // Déverrouiller le compte
            if ($user->accountLockout) {
                $user->accountLockout->unlock();
            }
        }

        // Si l'email a changé, enregistrer dans l'historique
        if ($user->isDirty('email')) {
            $oldEmail = $user->getOriginal('email');
            $newEmail = $user->email;
            if ($oldEmail && $newEmail) {
                UserSecurityHistory::recordEmailChange(
                    $user,
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
