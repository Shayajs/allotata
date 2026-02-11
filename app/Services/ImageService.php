<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Taille maximale avant compression (500 ko)
     */
    private const MAX_SIZE_BEFORE_COMPRESSION = 500 * 1024; // 500 KB

    /**
     * Dimensions maximales après compression
     */
    private const MAX_WIDTH = 1200;
    private const MAX_HEIGHT = 1200;

    /**
     * Qualité de compression sévère
     */
    private const COMPRESSION_QUALITY = 60; // Très bas pour compression sévère

    /**
     * Traite et compresse une image uploadée
     * 
     * @param UploadedFile $file Le fichier uploadé
     * @param string $directory Le répertoire de stockage (ex: 'logos', 'realisations')
     * @param string|null $filename Nom de fichier personnalisé (optionnel)
     * @return string Le chemin relatif de l'image stockée
     */
    public function processAndStore(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        // Générer un nom de fichier unique si non fourni
        if (!$filename) {
            $filename = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
        }

        // Vérifier la taille du fichier directement depuis l'upload
        $fileSize = $file->getSize();

        // Si l'image fait moins de 500 ko, on la stocke directement sans compression
        if ($fileSize < self::MAX_SIZE_BEFORE_COMPRESSION) {
            // Stocker directement dans le répertoire final
            $finalPath = $file->storeAs($directory, $filename, 'public');
            return $finalPath;
        }

        // Sinon, compression sévère - stocker temporairement d'abord
        $tempPath = $file->storeAs('temp', $filename, 'local');
        $fullTempPath = Storage::disk('local')->path($tempPath);

        // Vérifier que le fichier existe
        if (!file_exists($fullTempPath)) {
            throw new \RuntimeException('Le fichier temporaire n\'a pas pu être créé : ' . $tempPath);
        }

        // Compression sévère
        $compressedPath = $this->compressSeverely($fullTempPath, $directory, $filename);

        // Supprimer le fichier temporaire
        Storage::disk('local')->delete($tempPath);

        return $compressedPath;
    }

    /**
     * Compresse sévèrement une image
     * 
     * @param string $imagePath Chemin complet de l'image
     * @param string $directory Répertoire de destination
     * @param string $filename Nom du fichier
     * @return string Chemin relatif de l'image compressée
     */
    private function compressSeverely(string $imagePath, string $directory, string $filename): string
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('L\'extension GD n\'est pas disponible.');
        }

        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \RuntimeException('Impossible de lire les informations de l\'image.');
        }

        $mimeType = $imageInfo['mime'];
        list($width, $height) = $imageInfo;

        // Calculer les nouvelles dimensions (compression agressive)
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Créer l'image source selon le type
        $source = $this->createImageFromFile($imagePath, $mimeType);
        if (!$source) {
            throw new \RuntimeException('Format d\'image non supporté.');
        }

        // Créer l'image redimensionnée
        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionner avec interpolation de haute qualité
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Déterminer le format de sortie (toujours JPEG pour meilleure compression, sauf si PNG avec transparence)
        $outputFormat = ($mimeType === 'image/png' && $this->hasTransparency($source)) ? 'png' : 'jpeg';
        $outputExtension = $outputFormat === 'png' ? 'png' : 'jpg';
        
        // Modifier l'extension du filename si nécessaire
        $outputFilename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $outputExtension;

        // Chemin de destination
        $outputPath = storage_path('app/public/' . $directory . '/' . $outputFilename);

        // Créer le répertoire s'il n'existe pas
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0775, true);
        }

        // Sauvegarder avec compression sévère
        $this->saveImage($destination, $outputPath, $outputFormat);

        // Nettoyer la mémoire
        imagedestroy($source);
        imagedestroy($destination);

        // Vérifier que le fichier final fait bien moins de 500 ko
        $finalSize = filesize($outputPath);
        if ($finalSize >= self::MAX_SIZE_BEFORE_COMPRESSION) {
            // Compression encore plus agressive
            $this->compressEvenMore($outputPath, $outputFormat);
        }

        return $directory . '/' . $outputFilename;
    }

    /**
     * Crée une ressource image depuis un fichier
     */
    private function createImageFromFile(string $path, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return null;
        }
    }

    /**
     * Vérifie si une image a de la transparence
     */
    private function hasTransparency($image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sauvegarde une image avec compression
     */
    private function saveImage($image, string $path, string $format): void
    {
        switch ($format) {
            case 'jpeg':
                imagejpeg($image, $path, self::COMPRESSION_QUALITY);
                break;
            case 'png':
                // Pour PNG, on utilise la compression maximale (9)
                imagepng($image, $path, 9);
                break;
        }
    }

    /**
     * Compression encore plus agressive si nécessaire
     */
    private function compressEvenMore(string $imagePath, string $format): void
    {
        if ($format !== 'jpeg') {
            // Convertir en JPEG pour meilleure compression
            $source = imagecreatefrompng($imagePath);
            $jpegPath = str_replace('.png', '.jpg', $imagePath);
            imagejpeg($source, $jpegPath, 50); // Qualité encore plus basse
            imagedestroy($source);
            unlink($imagePath); // Supprimer le PNG
            $imagePath = $jpegPath;
        }

        // Réduire encore plus la qualité si toujours trop gros
        $source = imagecreatefromjpeg($imagePath);
        $width = imagesx($source);
        $height = imagesy($source);

        // Réduire encore de 20%
        $newWidth = (int)($width * 0.8);
        $newHeight = (int)($height * 0.8);

        $destination = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($destination, $imagePath, 50); // Qualité très basse

        imagedestroy($source);
        imagedestroy($destination);
    }

    /**
     * Supprime une image
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Optimise une image existante (pour les images déjà stockées)
     */
    public function optimizeExisting(string $path): void
    {
        $fullPath = storage_path('app/public/' . $path);
        
        if (!file_exists($fullPath)) {
            return;
        }

        $fileSize = filesize($fullPath);
        
        // Si déjà < 500 ko, on ne fait rien
        if ($fileSize < self::MAX_SIZE_BEFORE_COMPRESSION) {
            return;
        }

        // Compression sévère
        $directory = dirname($path);
        $filename = basename($path);
        $this->compressSeverely($fullPath, $directory, $filename);
    }
}

