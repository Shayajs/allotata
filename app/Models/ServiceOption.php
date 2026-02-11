<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_service_id',
        'nom',
        'type',
        'obligatoire',
        'ordre',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'ordre' => 'integer',
    ];

    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ServiceOptionChoice::class)->orderBy('ordre');
    }
}
