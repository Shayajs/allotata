<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntrepriseFinance extends Model
{
    protected $fillable = [
        'entreprise_id',
        'type',
        'category',
        'amount',
        'description',
        'date_record',
        'metadata',
    ];

    protected $casts = [
        'date_record' => 'date',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
}
