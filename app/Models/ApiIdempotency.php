<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiIdempotency extends Model
{
    protected $table = 'api_idempotency';

    protected $fillable = [
        'user_id',
        'cle',
        'status',
        'reponse',
    ];

    protected function casts(): array
    {
        return [
            'reponse' => 'array',
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
