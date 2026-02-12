<?php

namespace App\Helpers;

class VideoHelper
{
    /**
     * Convertit une URL vidéo (YouTube, Dailymotion, Vimeo, etc.) en URL d'embed
     * 
     * @param string $url L'URL de la vidéo
     * @return string|null L'URL d'embed ou null si l'URL n'est pas supportée
     */
    public static function getEmbedUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Dailymotion
        if (preg_match('/dailymotion\.com\/(?:video|embed)\/([^"&?\/\s]+)/', $url, $matches)) {
            return 'https://www.dailymotion.com/embed/video/' . $matches[1];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        // Si l'URL est déjà une URL d'embed, la retourner telle quelle
        if (preg_match('/youtube\.com\/embed\/|dailymotion\.com\/embed\/|player\.vimeo\.com\/video\//', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Vérifie si une URL est une URL vidéo valide
     * 
     * @param string $url L'URL à vérifier
     * @return bool True si l'URL est valide, false sinon
     */
    public static function isValidVideoUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return self::getEmbedUrl($url) !== null;
    }
}
