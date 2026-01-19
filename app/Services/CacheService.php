<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Invalider le cache d'une entreprise
     */
    public static function clearEntrepriseCache($entrepriseId, $slug = null): void
    {
        Cache::forget("entreprise_stats_{$entrepriseId}");
        
        if ($slug) {
            Cache::forget("entreprise_public_{$slug}");
        }
    }

    /**
     * Invalider tous les caches liés à une entreprise
     */
    public static function clearAllEntrepriseCache($entrepriseId, $slug = null): void
    {
        self::clearEntrepriseCache($entrepriseId, $slug);
        
        // Ajouter d'autres caches si nécessaire
        Cache::tags(["entreprise_{$entrepriseId}"])->flush();
    }
}
