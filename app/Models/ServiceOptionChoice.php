<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOptionChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_option_id',
        'nom',
        'prix_supplementaire',
        'temps_supplementaire',
        'ordre',
    ];

    protected $casts = [
        'prix_supplementaire' => 'decimal:2',
        'temps_supplementaire' => 'integer',
        'ordre' => 'integer',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ServiceOption::class, 'service_option_id');
    }
}
