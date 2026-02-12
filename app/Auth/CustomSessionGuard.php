<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\UserProvider;

class CustomSessionGuard extends SessionGuard
{
    /**
     * Get the number of minutes the remember me cookie should be valid.
     * 
     * Par défaut, Laravel utilise 2 semaines (20160 minutes).
     * On l'étend à 10 ans (5256000 minutes) pour une connexion pratiquement infinie.
     *
     * @return int
     */
    public function getRememberTokenExpiration()
    {
        return 5256000; // 10 ans en minutes
    }
}
