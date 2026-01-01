<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteWebVersion extends Model
{
    protected $fillable = [
        'entreprise_id',
        'contenu',
        'version_number',
        'description',
        'is_auto_save',
    ];

    protected function casts(): array
    {
        return [
            'contenu' => 'array',
            'is_auto_save' => 'boolean',
            'version_number' => 'integer',
        ];
    }

    /**
     * Relation avec l'entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Créer une nouvelle version pour une entreprise
     */
    public static function createVersion(Entreprise $entreprise, bool $isAutoSave = true, ?string $description = null): self
    {
        // Récupérer le numéro de version le plus élevé
        $lastVersion = self::where('entreprise_id', $entreprise->id)
            ->max('version_number') ?? 0;

        return self::create([
            'entreprise_id' => $entreprise->id,
            'contenu' => $entreprise->contenu_site_web,
            'version_number' => $lastVersion + 1,
            'description' => $description,
            'is_auto_save' => $isAutoSave,
        ]);
    }

    /**
     * Nettoyer les anciennes versions (garder les N dernières)
     */
    public static function cleanOldVersions(Entreprise $entreprise, int $keepCount = 50): void
    {
        $versionsToDelete = self::where('entreprise_id', $entreprise->id)
            ->orderBy('version_number', 'desc')
            ->skip($keepCount)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($versionsToDelete->isNotEmpty()) {
            self::whereIn('id', $versionsToDelete)->delete();
        }
    }
}
