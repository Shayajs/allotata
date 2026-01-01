<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageController extends Controller
{
    /**
     * Servir les fichiers depuis storage/app/public
     * 
     * @param string $path Chemin relatif du fichier (ex: logos/image.png)
     * @return BinaryFileResponse|Response
     */
    public function serve($path)
    {
        // Nettoyer le chemin
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');
        
        // Construire le chemin complet
        // Utiliser base_path() pour être sûr d'avoir le bon chemin même si storage_path() est mal configuré
        $filePath = base_path('storage/app/public/' . $path);
        
        // Log pour déboguer
        \Log::info('StorageController serve', [
            'path' => $path,
            'filePath' => $filePath,
            'base_path' => base_path(),
            'storage_path' => storage_path(),
            'exists' => file_exists($filePath),
        ]);
        
        // Vérifier que le fichier existe
        if (!file_exists($filePath) || !is_file($filePath)) {
            \Log::error('StorageController - Fichier non trouvé', [
                'path' => $path,
                'filePath' => $filePath,
            ]);
            abort(404, 'Fichier non trouvé: ' . $path);
        }
        
        // Vérification de sécurité simplifiée pour le développement
        $allowedDirs = ['logos', 'profils', 'images_fond', 'realisations', 'messages', 'temp', 'services'];
        $firstSegment = explode('/', $path)[0] ?? '';
        
        // Vérification minimale : juste s'assurer que c'est dans un dossier autorisé
        if (!in_array($firstSegment, $allowedDirs)) {
            \Log::error('StorageController - Dossier non autorisé', [
                'path' => $path,
                'firstSegment' => $firstSegment,
            ]);
            abort(403, 'Accès refusé - Dossier non autorisé: ' . $firstSegment);
        }
        
        // Déterminer le type MIME
        $mimeType = $this->getMimeType($filePath);
        
        \Log::info('StorageController - Servir fichier', [
            'path' => $path,
            'mimeType' => $mimeType,
        ]);
        
        // Servir le fichier avec les bons en-têtes
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Disposition' => 'inline',
        ]);
    }
    
    /**
     * Valide que le fichier est bien dans storage/app/public (sécurité)
     * Fonctionne avec WSL, liens symboliques et montages réseau
     * 
     * @param string $filePath Chemin complet du fichier
     * @param string $requestedPath Chemin demandé (pour les logs)
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function validateFilePath(string $filePath, string $requestedPath): void
    {
        $storagePath = storage_path('app/public');
        
        // Normaliser les chemins (supprimer les backslashes, normaliser les slashes)
        $normalizedFilePath = rtrim(str_replace('\\', '/', $filePath), '/');
        $normalizedStoragePath = rtrim(str_replace('\\', '/', $storagePath), '/');
        
        // Log pour déboguer
        if (config('app.debug')) {
            \Log::debug('StorageController - Validation', [
                'requestedPath' => $requestedPath,
                'filePath' => $filePath,
                'normalizedFilePath' => $normalizedFilePath,
                'normalizedStoragePath' => $normalizedStoragePath,
                'startsWith' => str_starts_with($normalizedFilePath, $normalizedStoragePath),
            ]);
        }
        
        // Méthode 1 : Vérification simple avec str_starts_with (fonctionne dans 99% des cas)
        if (str_starts_with($normalizedFilePath, $normalizedStoragePath)) {
            return; // OK, le fichier est dans le bon répertoire
        }
        
        // Méthode 2 : Si la méthode 1 échoue (WSL avec montages réseau), utiliser realpath
        // Mais seulement si les deux chemins peuvent être résolus
        $realFilePath = realpath($filePath);
        $realStoragePath = realpath($storagePath);
        
        if ($realFilePath && $realStoragePath) {
            $normalizedRealFilePath = rtrim(str_replace('\\', '/', $realFilePath), '/');
            $normalizedRealStoragePath = rtrim(str_replace('\\', '/', $realStoragePath), '/');
            
            if (str_starts_with($normalizedRealFilePath, $normalizedRealStoragePath)) {
                return; // OK avec realpath
            }
        }
        
        // Méthode 3 : Vérification par segments (dernier recours)
        // On vérifie que le chemin demandé ne contient pas de ".." et commence bien par un sous-dossier autorisé
        $allowedDirectories = ['logos', 'profils', 'images_fond', 'realisations', 'messages', 'temp', 'services'];
        $pathSegments = explode('/', $requestedPath);
        
        // Si le premier segment est un dossier autorisé, on accepte
        if (!empty($pathSegments) && in_array($pathSegments[0], $allowedDirectories)) {
            // Le premier segment est un dossier autorisé, on accepte
            // (c'est une sécurité supplémentaire, pas la principale)
            return;
        }
        
        // Si le fichier est à la racine (pas de sous-dossier), on vérifie qu'il n'y a pas de ".."
        // et que le fichier existe bien dans storage/app/public
        if (count($pathSegments) === 1 && !str_contains($requestedPath, '..')) {
            // Fichier à la racine, mais on vérifie quand même qu'il est dans le bon répertoire
            // en comparant les chemins absolus normalisés
            $baseName = basename($normalizedFilePath);
            $storageBaseName = basename($normalizedStoragePath);
            
            // Si le fichier est directement dans storage/app/public (pas dans un sous-dossier)
            // et qu'il n'y a pas de ".." dans le chemin, on accepte
            if (dirname($normalizedFilePath) === $normalizedStoragePath) {
                return; // Fichier à la racine de storage/app/public
            }
        }
        
        // Si toutes les vérifications échouent, on refuse l'accès
        if (config('app.debug')) {
            \Log::warning('StorageController - Accès refusé', [
                'filePath' => $normalizedFilePath,
                'storagePath' => $normalizedStoragePath,
                'realFilePath' => $realFilePath ?? 'null',
                'realStoragePath' => $realStoragePath ?? 'null',
                'requestedPath' => $requestedPath,
                'pathSegments' => $pathSegments,
            ]);
        }
        
        abort(403, 'Accès refusé');
    }
    
    /**
     * Détermine le type MIME d'un fichier
     * 
     * @param string $filePath Chemin complet du fichier
     * @return string Type MIME
     */
    private function getMimeType(string $filePath): string
    {
        // Essayer d'abord avec mime_content_type
        $mimeType = mime_content_type($filePath);
        
        if ($mimeType) {
            return $mimeType;
        }
        
        // Fallback sur l'extension si mime_content_type échoue
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
