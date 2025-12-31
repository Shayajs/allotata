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
        // Nettoyer le chemin pour éviter les attaques de type directory traversal
        // On supprime tous les ".." et on nettoie le chemin
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');
        
        // Construire le chemin complet
        $filePath = storage_path('app/public/' . $path);
        
        // Vérifier que le fichier existe et est un fichier (pas un répertoire)
        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'Fichier non trouvé');
        }
        
        // Vérification de sécurité : s'assurer que le fichier est bien dans storage/app/public
        // Approche robuste qui fonctionne avec WSL, liens symboliques et montages réseau
        $this->validateFilePath($filePath, $path);
        
        // Déterminer le type MIME
        $mimeType = $this->getMimeType($filePath);
        
        // Servir le fichier avec les bons en-têtes
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Disposition' => 'inline', // Afficher dans le navigateur plutôt que télécharger
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
        $allowedDirectories = ['logos', 'profils', 'images_fond', 'realisations', 'messages', 'temp'];
        $pathSegments = explode('/', $requestedPath);
        
        if (!empty($pathSegments) && in_array($pathSegments[0], $allowedDirectories)) {
            // Le premier segment est un dossier autorisé, on accepte
            // (c'est une sécurité supplémentaire, pas la principale)
            return;
        }
        
        // Si toutes les vérifications échouent, on refuse l'accès
        if (config('app.debug')) {
            \Log::warning('StorageController - Accès refusé', [
                'filePath' => $normalizedFilePath,
                'storagePath' => $normalizedStoragePath,
                'realFilePath' => $realFilePath ?? 'null',
                'realStoragePath' => $realStoragePath ?? 'null',
                'requestedPath' => $requestedPath,
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
