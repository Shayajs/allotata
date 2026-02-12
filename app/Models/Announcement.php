<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'titre',
        'message',
        'type',
        'cible',
        'date_debut',
        'date_fin',
        'est_actif',
        'afficher_banniere',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'est_actif' => 'boolean',
        'afficher_banniere' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Récupérer les annonces actives pour un utilisateur
     */
    public static function getActiveForUser(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('est_actif', true)
            ->where(function($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            })
            ->orderBy('created_at', 'desc');

        // Filtrer par cible
        if ($user) {
            $query->where(function($q) use ($user) {
                $q->where('cible', 'tous');
                
                if ($user->est_client) {
                    $q->orWhere('cible', 'clients');
                }
                if ($user->est_gerant) {
                    $q->orWhere('cible', 'gerants');
                }
                if ($user->is_admin) {
                    $q->orWhere('cible', 'admins');
                }
            });
        } else {
            // Visiteur non connecté : uniquement les annonces publiques
            $query->where('cible', 'tous');
        }

        return $query->get();
    }

    /**
     * Récupérer les annonces pour la bannière
     */
    public static function getBannerAnnouncements(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        return self::getActiveForUser($user)->where('afficher_banniere', true);
    }

    /**
     * Couleur CSS selon le type
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'info' => 'blue',
            'warning' => 'yellow',
            'success' => 'green',
            'danger' => 'red',
            default => 'slate',
        };
    }

    /**
     * Icône selon le type
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'success' => '✅',
            'danger' => '🚨',
            default => '📢',
        };
    }
}
