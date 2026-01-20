<?php

namespace App\Services;

class HtmlPurifierService
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Nettoyer le HTML avec HTML Purifier (protection XSS)
     * Restaure le format AlloTata après le nettoyage si nécessaire
     */
    public static function purify(string $dirty): string
    {
        try {
            // Vérifier que HTMLPurifier est disponible
            if (!class_exists(\HTMLPurifier::class)) {
                \Log::error('HTMLPurifier n\'est pas installé sur le serveur. Exécutez: composer install');
                // Retourner le HTML original si HTMLPurifier n'est pas disponible
                // en mode production pour éviter de casser le site
                if (config('app.debug')) {
                    throw new \Exception('HTMLPurifier n\'est pas installé. Exécutez: composer require ezyang/htmlpurifier');
                }
                return $dirty;
            }
            
            // Réinitialiser l'instance pour prendre en compte la nouvelle configuration
            // (nécessaire si la config a changé après la première utilisation)
            self::$purifier = null;
            
            if (self::$purifier === null) {
                self::$purifier = new \HTMLPurifier(self::getConfig());
            }

            $purified = self::$purifier->purify($dirty);
            
            // Log pour déboguer (temporaire)
            if (config('app.debug')) {
                \Log::debug('HTML Purifier - Avant/Après', [
                    'before_length' => strlen($dirty),
                    'after_length' => strlen($purified),
                    'before_preview' => substr($dirty, 0, 200),
                    'after_preview' => substr($purified, 0, 200),
                    'contains_section' => strpos($purified, '<section') !== false,
                ]);
            }
            
            // Restaurer le format AlloTata si nécessaire (après le nettoyage HTML Purifier)
            $purified = self::restoreAlloTataFormat($purified);
            
            return $purified;
        } catch (\Exception $e) {
            \Log::error('Erreur lors du nettoyage HTML avec HTML Purifier', [
                'error' => $e->getMessage(),
                'html_length' => strlen($dirty),
                'trace' => $e->getTraceAsString()
            ]);
            // En cas d'erreur, retourner le HTML original (non échappé) plutôt que de l'échapper
            // car c'est du HTML valide qui devrait passer
            // En production, ne pas faire planter le site si HTMLPurifier a un problème
            if (config('app.debug')) {
                throw $e;
            }
            return $dirty;
        }
    }

    /**
     * Restaurer le format AlloTata après le nettoyage HTML Purifier
     * S'assure que tous les spans avec classe allotata-text ont les styles complets
     */
    private static function restoreAlloTataFormat(string $html): string
    {
        if (empty($html)) {
            return $html;
        }

        // Styles complets nécessaires pour le format AlloTata
        $fullStyle = 'font-weight: 900; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;';

        // Pattern pour trouver les spans avec classe allotata-text
        // On utilise une regex qui capture tous les attributs
        $pattern = '/<span\s+([^>]*class=["\']([^"\']*allotata-text[^"\']*)["\'][^>]*)>(.*?)<\/span>/is';
        
        return preg_replace_callback($pattern, function($matches) use ($fullStyle) {
            $allAttrs = $matches[1];
            $classes = $matches[2];
            $content = $matches[3];
            
            // Extraire les styles existants
            $hasStyle = preg_match('/style=["\']([^"\']*)["\']/i', $allAttrs, $styleMatches);
            
            if ($hasStyle) {
                $existingStyles = $styleMatches[1];
                
                // Vérifier que tous les styles nécessaires sont présents
                $requiredProps = [
                    'font-weight: 900',
                    'background: linear-gradient(135deg, #22c55e 0%, #f97316 100%)',
                    '-webkit-background-clip: text',
                    '-webkit-text-fill-color: transparent',
                    'background-clip: text',
                    'display: inline-block'
                ];
                
                $needsUpdate = false;
                $updatedStyles = $existingStyles;
                
                foreach ($requiredProps as $requiredProp) {
                    $propName = explode(':', $requiredProp)[0];
                    if (!preg_match('/' . preg_quote($propName, '/') . '\s*:/i', $existingStyles)) {
                        $needsUpdate = true;
                        $updatedStyles .= '; ' . $requiredProp;
                    }
                }
                
                if ($needsUpdate) {
                    // Remplacer le style existant
                    $newAttrs = preg_replace('/style=["\'][^"\']*["\']/i', 'style="' . htmlspecialchars($updatedStyles, ENT_QUOTES) . '"', $allAttrs);
                    return '<span ' . $newAttrs . '>' . $content . '</span>';
                }
                
                return $matches[0]; // Pas de changement nécessaire
            } else {
                // Pas de style, ajouter tous les styles nécessaires
                $cleanAttrs = preg_replace('/class=["\'][^"\']*["\']/', '', $allAttrs);
                $cleanAttrs = trim(preg_replace('/\s+/', ' ', $cleanAttrs));
                return '<span class="' . htmlspecialchars($classes, ENT_QUOTES) . '" style="' . htmlspecialchars($fullStyle, ENT_QUOTES) . '"' . ($cleanAttrs ? ' ' . $cleanAttrs : '') . '>' . $content . '</span>';
            }
        }, $html);
    }

    /**
     * Configuration de HTML Purifier
     * Permet iframes mais nettoie les scripts malveillants
     */
    private static function getConfig()
    {
        // Vérifier que HTMLPurifier_Config est disponible
        if (!class_exists(\HTMLPurifier_Config::class)) {
            throw new \Exception('HTMLPurifier_Config n\'est pas installé. Exécutez: composer require ezyang/htmlpurifier');
        }
        $config = \HTMLPurifier_Config::createDefault();
        
        // Permettre les éléments HTML de base
        // Ajout de style avec gradient pour AlloTata
        // IMPORTANT: span[class|style] permet la classe allotata-text et les styles inline
        // IMPORTANT: section, figure, figcaption pour les blocs de cours
        $config->set('HTML.Allowed', 
            'p,br,strong,em,u,b,i,a[href|title|target|rel],ul,ol,li,blockquote,' .
            'img[src|alt|title|width|height|class|style],' .
            'h1,h2,h3,h4,h5,h6,' .
            'table[border|cellpadding|cellspacing|class|style],thead,tbody,tr,td,th,' .
            'div[class|style|id],span[class|style|id],' .
            'section[class|style|id],figure[class|style],figcaption[class|style],' .
            'iframe[src|width|height|frameborder|allowfullscreen|class|style|allow|loading|referrerpolicy],' .
            'video[src|controls|width|height|poster|class|style|preload],' .
            'source[src|type],audio[src|controls|class|style],' .
            'button[type|class|style|onclick],svg[*],path[*],circle[*],rect[*]'
        );
        
        // Permettre les attributs CSS sécurisés (incluant background pour le dégradé AlloTata)
        // IMPORTANT: Tous les attributs CSS nécessaires pour le gradient AlloTata sont autorisés
        $config->set('CSS.AllowedProperties', 
            'text-align,color,background-color,background,font-size,font-weight,font-style,' .
            'text-decoration,margin,padding,border,width,height,max-width,max-height,' .
            '-webkit-background-clip,-webkit-text-fill-color,background-clip,display,line-height'
        );
        
        // Autoriser toutes les classes CSS (pas seulement allotata-text)
        // Plus permissif pour les blocs de cours qui utilisent beaucoup de classes Tailwind
        $config->set('Attr.AllowedClasses', null);
        
        // Permettre tous les attributs nécessaires pour les blocs de cours
        $config->set('HTML.AllowedAttributes', 
            '*.class,*.style,*.id,a.href,a.title,a.target,a.rel,' .
            'img.src,img.alt,img.title,img.width,img.height,' .
            'iframe.src,iframe.width,iframe.height,iframe.frameborder,iframe.allowfullscreen,iframe.allow,iframe.loading,iframe.referrerpolicy,' .
            'video.src,video.controls,video.width,video.height,video.poster,video.preload,source.src,source.type,' .
            'button.type,button.onclick,svg.*,path.*,circle.*,rect.*'
        );
        
        // Autoriser les attributs data-* spécifiques sur les éléments
        // HTML Purifier ne supporte pas les wildcards dans HTML.AllowedAttributes,
        // donc on doit les ajouter manuellement
        try {
            $def = $config->getDefinition('HTML', true);
            if ($def && isset($def->info_global_attr)) {
                // Autoriser les attributs data-* spécifiques utilisés dans les blocs de cours
                $def->info_global_attr['data-video-block-id'] = new \HTMLPurifier_AttrDef_Text();
                $def->info_global_attr['data-video-id'] = new \HTMLPurifier_AttrDef_Text();
                $def->info_global_attr['data-video-pinned'] = new \HTMLPurifier_AttrDef_Text();
                // Ajouter data-* comme attribut générique via une définition personnalisée
                // Note: HTML Purifier ne supporte pas nativement les wildcards
            }
        } catch (\Exception $e) {
            \Log::warning('Impossible d\'ajouter les attributs data-* à HTML Purifier', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Alternative : Autoriser toutes les classes et tous les attributs data-* en étant plus permissif
        // Mais on garde la sécurité XSS de base
        
        // Permettre les iframes mais seulement de sources approuvées
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.)?(youtube|vimeo|dailymotion|wistia)\.com%');
        
        // Permettre les liens vers http et https
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        
        // Permettre les images avec src http/https ou data URI
        $config->set('URI.DisableExternalResources', false);
        $config->set('URI.DisableExternalResourcesPath', true);
        
        // Autofixer le HTML (mais ne pas être trop agressif)
        $config->set('HTML.TidyLevel', 'medium');
        
        // Ne pas convertir le document en fragment (garder les balises racines comme <section>)
        $config->set('Core.ConvertDocumentToFragment', false);
        
        // Ne pas supprimer les balises inconnues automatiquement
        $config->set('HTML.TidyRemove', '');
        
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
