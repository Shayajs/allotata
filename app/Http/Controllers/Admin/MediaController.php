<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    /**
     * Afficher la page de gestion des médias
     */
    public function index(Request $request)
    {
        return view('admin.media.index');
    }

    /**
     * Obtenir la liste des fichiers et dossiers
     */
    public function list(Request $request)
    {
        $folder = $request->get('folder', '/');
        $type = $request->get('type'); // image, video, audio, document, etc.
        $search = $request->get('search');

        $query = MediaFile::query();

        // Filtrer par dossier
        if ($folder && $folder !== '/') {
            $query->where('folder_path', $folder);
        } else {
            $query->where(function($q) {
                $q->whereNull('folder_path')->orWhere('folder_path', '');
            });
        }

        // Filtrer par type
        if ($type) {
            $query->where('type', $type);
        }

        // Recherche
        if ($search) {
            $query->search($search);
        }

        // Trier par date de création (plus récent en premier)
        $query->orderBy('created_at', 'desc');

        $files = $query->paginate(50);

        // Obtenir la liste des dossiers uniques
        $folders = MediaFile::distinct()
            ->whereNotNull('folder_path')
            ->where('folder_path', '!=', '')
            ->pluck('folder_path')
            ->toArray();

        // Organiser les dossiers en arborescence
        $folderTree = $this->buildFolderTree($folders);

        return response()->json([
            'files' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ],
            'folder_tree' => $folderTree,
            'current_folder' => $folder,
        ]);
    }

    /**
     * Construire l'arborescence des dossiers
     */
    private function buildFolderTree($folders)
    {
        $tree = [];
        
        foreach ($folders as $folder) {
            $parts = explode('/', trim($folder, '/'));
            $current = &$tree;
            
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [
                        'name' => $part,
                        'path' => implode('/', array_slice($parts, 0, array_search($part, $parts) + 1)),
                        'children' => [],
                    ];
                }
                $current = &$current[$part]['children'];
            }
        }
        
        return $this->sortFolderTree($tree);
    }

    /**
     * Trier l'arborescence
     */
    private function sortFolderTree($tree)
    {
        ksort($tree);
        foreach ($tree as &$node) {
            if (!empty($node['children'])) {
                $node['children'] = $this->sortFolderTree($node['children']);
            }
        }
        return array_values($tree);
    }

    /**
     * Uploader un fichier
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'folder' => 'nullable|string',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $folder = $request->get('folder', '/');
            $description = $request->get('description');

            // Normaliser le chemin du dossier
            if ($folder === '/' || empty($folder)) {
                $folderPath = null;
                $storageFolder = 'media';
            } else {
                $folderPath = trim($folder, '/');
                $storageFolder = 'media/' . $folderPath;
            }

            // Générer un nom unique pour le fichier
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            $fileName = $nameWithoutExtension . '_' . time() . '.' . $extension;

            // Créer le dossier s'il n'existe pas
            if (!Storage::disk('public')->exists($storageFolder)) {
                Storage::disk('public')->makeDirectory($storageFolder, 0755, true);
            }

            // Stocker le fichier
            $path = $file->storeAs($storageFolder, $fileName, 'public');
            
            // Vérifier que le fichier a bien été stocké
            if (!$path || !Storage::disk('public')->exists($path)) {
                throw new \Exception('Le fichier n\'a pas pu être stocké correctement.');
            }

            // Détecter le type de fichier
            $mimeType = $file->getMimeType();
            $type = $this->detectFileType($mimeType);

            // Obtenir les dimensions pour les images
            $width = null;
            $height = null;

            if (str_starts_with($mimeType, 'image/')) {
                try {
                    $imagePath = Storage::disk('public')->path($path);
                    
                    // Vérifier que le fichier existe avant de le traiter
                    if (!file_exists($imagePath)) {
                        \Log::warning('Le fichier image n\'existe pas au chemin: ' . $imagePath);
                    } else {
                        // Utiliser Intervention Image si disponible, sinon utiliser getimagesize()
                        if (class_exists(\Intervention\Image\Facades\Image::class)) {
                            try {
                                $image = \Intervention\Image\Facades\Image::make($imagePath);
                                $width = $image->width();
                                $height = $image->height();
                                $image->destroy(); // Libérer la mémoire
                            } catch (\Exception $imgException) {
                                \Log::warning('Erreur avec Intervention Image, utilisation de getimagesize()', [
                                    'error' => $imgException->getMessage(),
                                ]);
                                // Fallback : utiliser getimagesize() si Intervention Image échoue
                                $imageInfo = @getimagesize($imagePath);
                                if ($imageInfo !== false) {
                                    $width = $imageInfo[0];
                                    $height = $imageInfo[1];
                                }
                            }
                        } else {
                            // Intervention Image n'est pas disponible, utiliser getimagesize()
                            $imageInfo = @getimagesize($imagePath);
                            if ($imageInfo !== false) {
                                $width = $imageInfo[0];
                                $height = $imageInfo[1];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Erreur lors de l\'obtention des dimensions de l\'image', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                    // On continue même si on ne peut pas obtenir les dimensions
                }
            }

            // Créer l'enregistrement dans la base de données
            $mediaFile = MediaFile::create([
                'name' => $nameWithoutExtension,
                'original_name' => $originalName,
                'path' => $path,
                'folder_path' => $folderPath,
                'type' => $type,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'description' => $description,
                'uploaded_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'file' => $mediaFile->load('uploader'),
                'url' => url('/media/' . $path),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Les erreurs de validation sont déjà gérées plus haut
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'upload de fichier', [
                'error' => $e->getMessage(),
                'file' => $request->file('file')?->getClientOriginalName(),
                'folder' => $request->get('folder'),
                'trace' => $e->getTraceAsString(),
            ]);

            // Nettoyer le fichier partiellement uploadé en cas d'erreur
            if (isset($path) && Storage::disk('public')->exists($path)) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Exception $deleteException) {
                    \Log::warning('Impossible de supprimer le fichier partiellement uploadé', [
                        'path' => $path,
                        'error' => $deleteException->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
                'message' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue lors de l\'upload du fichier.',
            ], 500);
        }
    }

    /**
     * Détecter le type de fichier depuis le MIME type
     */
    private function detectFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif ($mimeType === 'application/pdf') {
            return 'pdf';
        } elseif (in_array($mimeType, ['text/plain', 'text/markdown', 'text/html', 'text/css', 'text/javascript'])) {
            return 'text';
        } else {
            return 'document';
        }
    }

    /**
     * Renommer un fichier
     */
    public function rename(Request $request, MediaFile $mediaFile)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mediaFile->update([
                'name' => $request->get('name'),
            ]);

            return response()->json([
                'success' => true,
                'file' => $mediaFile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du renommage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Déplacer un fichier dans un autre dossier
     */
    public function move(Request $request, MediaFile $mediaFile)
    {
        $validator = Validator::make($request->all(), [
            'folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $newFolder = $request->get('folder', '/');
            
            // Normaliser le chemin du dossier
            if ($newFolder === '/' || empty($newFolder)) {
                $newFolderPath = null;
                $newStorageFolder = 'media';
            } else {
                $newFolderPath = trim($newFolder, '/');
                $newStorageFolder = 'media/' . $newFolderPath;
            }

            // Ancien et nouveau chemin
            $oldPath = $mediaFile->path;
            $fileName = basename($oldPath);
            $newPath = $newStorageFolder . '/' . $fileName;

            // Déplacer le fichier dans le storage
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $newPath);
            }

            // Mettre à jour l'enregistrement
            $mediaFile->update([
                'path' => $newPath,
                'folder_path' => $newFolderPath,
            ]);

            return response()->json([
                'success' => true,
                'file' => $mediaFile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du déplacement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un fichier
     */
    public function delete(MediaFile $mediaFile)
    {
        try {
            // Supprimer le fichier du storage
            if (Storage::disk('public')->exists($mediaFile->path)) {
                Storage::disk('public')->delete($mediaFile->path);
            }

            // Supprimer aussi la miniature si elle existe
            if ($mediaFile->thumbnail_path && Storage::disk('public')->exists($mediaFile->thumbnail_path)) {
                Storage::disk('public')->delete($mediaFile->thumbnail_path);
            }

            // Supprimer l'enregistrement (soft delete)
            $mediaFile->delete();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les informations d'un fichier
     */
    public function show(MediaFile $mediaFile)
    {
        return response()->json([
            'success' => true,
            'file' => $mediaFile->load('uploader'),
            'url' => url('/media/' . $mediaFile->path),
            'thumbnail_url' => $mediaFile->thumbnail_path ? url('/media/' . $mediaFile->thumbnail_path) : null,
        ]);
    }

    /**
     * Uploader une miniature pour un fichier (vidéo ou audio)
     */
    public function uploadThumbnail(Request $request, MediaFile $mediaFile)
    {
        // Vérifier que le fichier est de type vidéo ou audio
        if (!in_array($mediaFile->type, ['video', 'audio'])) {
            return response()->json([
                'success' => false,
                'error' => 'Les miniatures ne peuvent être ajoutées qu\'aux vidéos et aux bandes sonores.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $thumbnail = $request->file('thumbnail');

            // Créer le dossier thumbnails s'il n'existe pas
            $thumbnailFolder = 'media/thumbnails';
            if (!Storage::disk('public')->exists($thumbnailFolder)) {
                Storage::disk('public')->makeDirectory($thumbnailFolder);
            }

            // Générer un nom unique pour la miniature
            $extension = $thumbnail->getClientOriginalExtension();
            $fileName = 'thumb_' . $mediaFile->id . '_' . time() . '.' . $extension;

            // Stocker la miniature
            $thumbnailPath = Storage::disk('public')->putFileAs(
                $thumbnailFolder,
                $thumbnail,
                $fileName
            );

            // Traiter et redimensionner la miniature si nécessaire avec Intervention Image
            if (class_exists(\Intervention\Image\Facades\Image::class)) {
                try {
                    $image = \Intervention\Image\Facades\Image::make(Storage::disk('public')->path($thumbnailPath));
                    
                    // Redimensionner si trop grande (max 800x600)
                    if ($image->width() > 800 || $image->height() > 600) {
                        $image->resize(800, 600, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    // Sauvegarder l'image redimensionnée
                    $image->save(Storage::disk('public')->path($thumbnailPath), 85); // Qualité 85%
                } catch (\Exception $e) {
                    \Log::warning('Erreur lors du traitement de la miniature avec Intervention Image', [
                        'error' => $e->getMessage(),
                    ]);
                    // On continue même si le traitement échoue
                }
            } else {
                \Log::info('Intervention Image non disponible, la miniature est sauvegardée sans redimensionnement');
                // Intervention Image n'est pas disponible, on garde la miniature originale
            }

            // Supprimer l'ancienne miniature si elle existe
            if ($mediaFile->thumbnail_path && Storage::disk('public')->exists($mediaFile->thumbnail_path)) {
                Storage::disk('public')->delete($mediaFile->thumbnail_path);
            }

            // Mettre à jour l'enregistrement
            $mediaFile->update([
                'thumbnail_path' => $thumbnailPath,
            ]);

            return response()->json([
                'success' => true,
                'file' => $mediaFile->fresh()->load('uploader'),
                'thumbnail_url' => url('/media/' . $thumbnailPath),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'upload de la miniature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'upload de la miniature: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un dossier
     */
    public function createFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9_\-\s]+$/',
            'parent_folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $folderName = trim($request->get('folder_name'));
            $parentFolder = $request->get('parent_folder', '/');

            // Normaliser le chemin du dossier parent
            if ($parentFolder === '/' || empty($parentFolder)) {
                $parentFolderPath = null;
                $newFolderPath = $folderName;
                $storageFolder = 'media/' . $folderName;
            } else {
                $parentFolderPath = trim($parentFolder, '/');
                $newFolderPath = $parentFolderPath . '/' . $folderName;
                $storageFolder = 'media/' . $newFolderPath;
            }

            // Créer le dossier dans le storage
            if (!Storage::disk('public')->exists($storageFolder)) {
                Storage::disk('public')->makeDirectory($storageFolder);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce dossier existe déjà.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'folder_path' => $newFolderPath,
                'message' => 'Dossier créé avec succès.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du dossier', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création du dossier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer la miniature d'un fichier
     */
    public function deleteThumbnail(MediaFile $mediaFile)
    {
        try {
            // Supprimer le fichier de la miniature du storage
            if ($mediaFile->thumbnail_path && Storage::disk('public')->exists($mediaFile->thumbnail_path)) {
                Storage::disk('public')->delete($mediaFile->thumbnail_path);
            }

            // Mettre à jour l'enregistrement
            $mediaFile->update([
                'thumbnail_path' => null,
            ]);

            return response()->json([
                'success' => true,
                'file' => $mediaFile->fresh()->load('uploader'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression de la miniature: ' . $e->getMessage(),
            ], 500);
        }
    }
}
