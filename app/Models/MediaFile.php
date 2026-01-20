<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'thumbnail_path',
        'folder_path',
        'type',
        'mime_type',
        'size',
        'width',
        'height',
        'description',
        'alt_text',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'uploaded_by' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur qui a uploadé le fichier
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtenir l'URL publique du fichier
     */
    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Obtenir l'URL publique de la miniature
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        
        // Si pas de miniature personnalisée, retourner le fichier lui-même ou une image par défaut
        if ($this->type === 'image') {
            return Storage::disk('public')->url($this->path);
        }
        
        return null;
    }

    /**
     * Obtenir le type de fichier (image, video, audio, document, etc.)
     */
    public function getFileTypeAttribute()
    {
        $mime = $this->mime_type ?? '';
        
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mime, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mime, 'audio/')) {
            return 'audio';
        } elseif (in_array($mime, ['application/pdf'])) {
            return 'pdf';
        } elseif (in_array($mime, ['text/plain', 'text/markdown', 'text/html'])) {
            return 'text';
        } else {
            return 'document';
        }
    }

    /**
     * Scope pour filtrer par type de fichier
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par dossier
     */
    public function scopeInFolder($query, $folderPath)
    {
        if (empty($folderPath) || $folderPath === '/') {
            return $query->whereNull('folder_path')->orWhere('folder_path', '');
        }
        
        return $query->where('folder_path', $folderPath);
    }

    /**
     * Scope pour la recherche
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('original_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
