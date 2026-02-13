<?php

namespace App\Services;

use App\Models\User;

class CoursePageLinkService
{
    /**
     * Convertir un page_key en URL réelle.
     *
     * Format : "dashboard.{tab}" ou "entreprise.{tab}"
     */
    public static function resolve(string $pageKey, ?User $user = null): ?string
    {
        $parts = explode('.', $pageKey, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$context, $tab] = $parts;

        if ($context === 'dashboard') {
            return route('dashboard', ['tab' => $tab]);
        }

        if ($context === 'entreprise') {
            // Trouver la première entreprise de l'utilisateur
            if (!$user) {
                return null;
            }

            $entreprise = $user->entreprises()->first()
                ?? $user->entreprisesMembres()->first()?->entreprise;

            if (!$entreprise) {
                return null;
            }

            return route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => $tab]);
        }

        return null;
    }
}
