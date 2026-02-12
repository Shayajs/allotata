<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Google2faRecoveryCodeUsage extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'ip_address',
        'user_agent',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un usage appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
