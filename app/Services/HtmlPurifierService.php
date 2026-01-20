<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlPurifierService
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Nettoyer le HTML avec HTML Purifier (protection XSS)
     */
    public static function purify(string $dirty): string
    {
        if (self::$purifier === null) {
            self::$purifier = new HTMLPurifier(self::getConfig());
        }

        return self::$purifier->purify($dirty);
    }

    /**
     * Configuration de HTML Purifier
     * Permet iframes mais nettoie les scripts malveillants
     */
    private static function getConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        
        // Permettre les éléments HTML de base
        $config->set('HTML.Allowed', 
            'p,br,strong,em,u,b,i,a[href|title],ul,ol,li,blockquote,img[src|alt|title|width|height],' .
            'h1,h2,h3,h4,h5,h6,table[border|cellpadding|cellspacing],thead,tbody,tr,td,th,' .
            'div[class|style],span[class|style],iframe[src|width|height|frameborder|allowfullscreen],' .
            'video[src|controls|width|height|poster],source[src|type],audio[src|controls]'
        );
        
        // Permettre les attributs CSS sécurisés
        $config->set('CSS.AllowedProperties', 
            'text-align,color,background-color,font-size,font-weight,font-style,' .
            'text-decoration,margin,padding,border,width,height,max-width,max-height'
        );
        
        // Permettre les iframes mais seulement de sources approuvées
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.)?(youtube|vimeo|dailymotion|wistia)\.com%');
        
        // Permettre les liens vers http et https
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        
        // Permettre les images avec src http/https ou data URI
        $config->set('URI.DisableExternalResources', false);
        $config->set('URI.DisableExternalResourcesPath', true);
        
        // Autofixer le HTML
        $config->set('HTML.TidyLevel', 'heavy');
        
        // Permettre les data URIs pour les images (si nécessaire)
        $config->set('URI.DisableResources', false);
        
        return $config;
    }

    /**
     * Vérifier si le HTML est sécurisé (sans scripts malveillants)
     */
    public static function isSafe(string $html): bool
    {
        $purified = self::purify($html);
        
        // Si après nettoyage il y a encore des balises script ou javascript:
        if (preg_match('/<script/i', $purified) || preg_match('/javascript:/i', $purified)) {
            return false;
        }
        
        return true;
    }
}
