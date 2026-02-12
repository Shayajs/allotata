<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_service_id',
        'image_path',
        'est_couverture',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'est_couverture' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    /**
     * Relation : Une image appartient à un type de service
     */
    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }
}
