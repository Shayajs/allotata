<?php

namespace App\Helpers;

use App\Models\Setting;

class SiteHelper
{
    /**
     * Récupérer le logo du site selon le contexte
     * 
     * @param string $type 'light', 'dark', 'transparent', ou 'auto' (détecte selon le thème)
     * @return string|null URL du logo ou null si non défini
     */
    public static function getLogo(?string $type = 'auto'): ?string
    {
        // Si auto, détecter selon le thème (pour le favicon et le site web)
        if ($type === 'auto') {
            // Pour les emails, on utilise toujours transparent
            // Pour le web, on pourrait détecter le thème mais on va utiliser un système différent
            $type = 'transparent';
        }
        
        $logoPath = Setting::get("site_logo_{$type}", null);
        
        if (!$logoPath) {
            return null;
        }
        
        // Retourner l'URL complète
        return route('storage.serve', ['path' => $logoPath]);
    }
    
    /**
     * Récupérer le logo pour les emails (toujours transparent)
     */
    public static function getEmailLogo(): ?string
    {
        return self::getLogo('transparent');
    }
    
    /**
     * Récupérer le favicon selon le mode (clair/sombre)
     */
    public static function getFavicon(?string $theme = null): ?string
    {
        // Si le thème n'est pas spécifié, on retourne les deux versions
        if ($theme === null) {
            return null;
        }
        
        $logoPath = Setting::get("site_logo_{$theme}", null);
        
        if (!$logoPath) {
            return null;
        }
        
        return route('storage.serve', ['path' => $logoPath]);
    }
    
    /**
     * Récupérer le nom du site
     */
    public static function getSiteName(): string
    {
        return Setting::get('site_name', 'Allo Tata');
    }
}
