<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteWebPage extends Model
{
    use HasFactory;

    /**
     * Types d'onglets systeme (non modifiables via l'editeur de blocs).
     */
    public const SYSTEM_TYPES = ['reservation', 'agenda', 'contact', 'services'];

    /**
     * Icones par defaut pour les types systeme.
     */
    public const DEFAULT_ICONS = [
        'reservation' => 'calendar',
        'agenda'      => 'clock',
        'contact'     => 'mail',
        'services'    => 'briefcase',
        'custom'      => 'file-text',
    ];

    protected $fillable = [
        'entreprise_id',
        'nom',
        'slug',
        'type',
        'blocs',
        'ordre',
        'est_actif',
        'icone',
    ];

    protected $casts = [
        'blocs'    => 'array',
        'est_actif' => 'boolean',
        'ordre'    => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Uniquement les pages actives.
     */
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    /**
     * Tri par ordre d'affichage.
     */
    public function scopeOrdonne($query)
    {
        return $query->orderBy('ordre', 'asc');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Est-ce un onglet systeme ?
     */
    public function isSystemTab(): bool
    {
        return in_array($this->type, self::SYSTEM_TYPES);
    }

    /**
     * Retourne les blocs de la page (tableau vide pour les systeme).
     */
    public function getBlocks(): array
    {
        if ($this->isSystemTab()) {
            return [];
        }

        return $this->blocs ?? [];
    }

    /**
     * Nom du composant Blade a utiliser pour le rendu.
     */
    public function getViewName(): string
    {
        if ($this->isSystemTab()) {
            return 'components.site-web.system-tabs.' . $this->type;
        }

        return 'public.site-web'; // Utilise le rendu blocs standard
    }

    /**
     * Icone effective (custom ou par defaut selon le type).
     */
    public function getEffectiveIconAttribute(): string
    {
        return $this->icone ?? (self::DEFAULT_ICONS[$this->type] ?? 'file-text');
    }
}
