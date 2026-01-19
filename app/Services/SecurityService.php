<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserIpHistory;
use App\Models\LoginAttempt;
use App\Models\SecurityLog;
use App\Models\TrustedDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    /**
     * Vérifie si une IP est suspecte pour un utilisateur donné
     */
    public function isSuspiciousIp(User $user, string $ipAddress): bool
    {
        // Obtenir l'historique des IPs de l'utilisateur
        $ipHistory = UserIpHistory::where('user_id', $user->id)->get();
        
        // Si c'est la première connexion ou très peu d'IPs, pas suspect
        if ($ipHistory->count() <= 2) {
            return false;
        }

        // Vérifier si cette IP a déjà été utilisée
        $knownIp = $ipHistory->where('ip_address', $ipAddress)->first();
        
        if ($knownIp) {
            // IP connue, pas suspect
            return false;
        }

        // Obtenir les informations géographiques de la nouvelle IP
        $ipInfo = $this->getIpInfo($ipAddress);
        
        if (!$ipInfo) {
            // En cas d'erreur, considérer comme suspect par précaution
            return true;
        }

        // Obtenir les informations des IPs connues
        $knownCountries = $ipHistory->pluck('country_code')->filter()->unique()->toArray();
        
        // Si l'IP est d'un pays jamais utilisé, considérer comme suspect
        if (!empty($knownCountries) && !in_array($ipInfo['country_code'] ?? null, $knownCountries)) {
            return true;
        }

        // Vérifier les tentatives de connexion récentes depuis cette IP
        $recentAttempts = LoginAttempt::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(1))
            ->where('success', false)
            ->count();

        // Si plus de 3 tentatives échouées depuis cette IP dans la dernière heure, suspect
        if ($recentAttempts > 3) {
            return true;
        }

        return false;
    }

    /**
     * Obtient les informations géographiques d'une IP
     */
    public function getIpInfo(string $ipAddress): ?array
    {
        try {
            // Utiliser ip-api.com (service gratuit, 45 requêtes/minute)
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ipAddress}", [
                'fields' => 'status,country,countryCode,city,query'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'success') {
                    return [
                        'ip' => $data['query'] ?? $ipAddress,
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'city' => $data['city'] ?? null,
                        'location' => $this->formatLocation($data['city'] ?? null, $data['country'] ?? null),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la récupération des infos IP pour {$ipAddress}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Formate la localisation pour l'affichage
     */
    private function formatLocation(?string $city, ?string $country): string
    {
        $parts = array_filter([$city, $country]);
        return implode(', ', $parts) ?: 'Localisation inconnue';
    }

    /**
     * Détermine si on doit envoyer une notification (email/SMS) selon le niveau de suspicion
     */
    public function shouldSendRecoveryNotification(User $user, string $ipAddress): bool
    {
        // Toujours envoyer si IP suspecte
        if ($this->isSuspiciousIp($user, $ipAddress)) {
            return true;
        }

        // Vérifier les tentatives récentes depuis cette IP
        $recentAttempts = LoginAttempt::where('email', $user->email)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('success', false)
            ->count();

        // Si 2 tentatives échouées ou plus dans les 24h, envoyer notification
        if ($recentAttempts >= 2) {
            return true;
        }

        // Vérifier si l'IP a déjà été utilisée par cet utilisateur
        $knownIp = UserIpHistory::where('user_id', $user->id)
            ->where('ip_address', $ipAddress)
            ->first();

        // Si IP jamais vue, envoyer notification
        if (!$knownIp) {
            return true;
        }

        return false;
    }

    /**
     * Enregistre une connexion réussie et met à jour l'historique IP
     */
    public function recordSuccessfulLogin(User $user, string $ipAddress, ?string $userAgent = null): void
    {
        $ipInfo = $this->getIpInfo($ipAddress);
        
        // Enregistrer/mettre à jour l'historique IP
        UserIpHistory::recordIp(
            $user->id,
            $ipAddress,
            $ipInfo['location'] ?? null,
            $ipInfo['country_code'] ?? null
        );

        // Enregistrer le log de sécurité
        $isSuspicious = $this->isSuspiciousIp($user, $ipAddress);
        
        SecurityLog::log(
            $user->id,
            'login_success',
            $ipAddress,
            $userAgent,
            $ipInfo['location'] ?? null,
            ['ip_info' => $ipInfo],
            $isSuspicious ? 'medium' : 'low',
            $isSuspicious,
            $isSuspicious ? 'Connexion depuis une IP suspecte ou inhabituelle' : null
        );
    }

    /**
     * Détermine si l'A2F doit être demandé pour une connexion
     * Basé sur l'IP, le périphérique et la géolocalisation
     */
    public function shouldRequireA2F(User $user, string $ipAddress, string $userAgent): bool
    {
        // Si l'utilisateur n'a pas l'A2F activé, ne pas demander
        if (!$user->a2f_enabled) {
            return false;
        }

        // Vérifier si le périphérique/IP sont déjà approuvés
        if (TrustedDevice::isTrusted($user->id, $ipAddress, $userAgent)) {
            return false;
        }

        // Obtenir les infos de l'IP
        $ipInfo = $this->getIpInfo($ipAddress);
        
        // Vérifier si l'IP est connue dans l'historique
        $ipKnown = UserIpHistory::where('user_id', $user->id)
            ->where('ip_address', $ipAddress)
            ->exists();

        // Vérifier si le périphérique est connu (même hash)
        $deviceKnown = TrustedDevice::isDeviceKnown($user->id, $userAgent);

        // Cas 1: IP ET périphérique connus → Pas d'A2F
        if ($ipKnown && $deviceKnown) {
            return false;
        }

        // Cas 2: IP connue mais périphérique différent → Vérifier si changement géographique
        if ($ipKnown && !$deviceKnown) {
            // Si changement de pays, demander A2F
            if ($ipInfo && $this->isCountryChange($user, $ipInfo['country_code'] ?? null)) {
                return true;
            }
            // Sinon, périphérique différent mais même réseau → Pas d'A2F (déjà enregistré)
            return false;
        }

        // Cas 3: Nouvelle IP → Vérifier le pays
        if (!$ipKnown) {
            // Si changement de pays important, demander A2F
            if ($ipInfo && $this->isCountryChange($user, $ipInfo['country_code'] ?? null)) {
                return true;
            }
            
            // Vérifier si c'est une IP suspecte
            if ($this->isSuspiciousIp($user, $ipAddress)) {
                return true;
            }
        }

        // Par défaut, si nouvelle IP ou nouveau périphérique, demander A2F
        return !$ipKnown || !$deviceKnown;
    }

    /**
     * Vérifie si c'est un changement de pays par rapport aux connexions précédentes
     */
    private function isCountryChange(User $user, ?string $newCountryCode): bool
    {
        if (!$newCountryCode) {
            return false; // Pas d'info, on ne peut pas décider
        }

        // Obtenir les pays connus de l'utilisateur
        $knownCountries = UserIpHistory::where('user_id', $user->id)
            ->whereNotNull('country_code')
            ->where('last_seen_at', '>=', now()->subDays(30)) // Derniers 30 jours
            ->distinct()
            ->pluck('country_code')
            ->toArray();

        // Si aucun pays connu, pas de changement
        if (empty($knownCountries)) {
            return false;
        }

        // Si le nouveau pays n'est pas dans la liste, c'est un changement
        return !in_array($newCountryCode, $knownCountries);
    }

    /**
     * Marque un périphérique/IP comme approuvé après vérification A2F réussie
     */
    public function markDeviceAsTrusted(User $user, string $ipAddress, string $userAgent): void
    {
        $ipInfo = $this->getIpInfo($ipAddress);
        
        TrustedDevice::markAsTrusted(
            $user->id,
            $ipAddress,
            $userAgent,
            $ipInfo['country_code'] ?? null,
            $ipInfo['location'] ?? null
        );

        // Mettre à jour aussi l'historique IP
        UserIpHistory::recordIp(
            $user->id,
            $ipAddress,
            $ipInfo['location'] ?? null,
            $ipInfo['country_code'] ?? null
        );
    }
}
