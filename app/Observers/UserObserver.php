<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Si le mot de passe a changé, déverrouiller le compte
        if ($user->isDirty('password') && $user->accountLockout) {
            $user->accountLockout->unlock();
        }
    }
}
