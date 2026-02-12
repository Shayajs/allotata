<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'numero_ticket',
        'sujet',
        'description',
        'statut',
        'priorite',
        'categorie',
        'user_id',
        'assigne_a',
        'resolu_at',
    ];

    protected $casts = [
        'resolu_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigneA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function derniersMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'desc');
    }

    public static function generateNumeroTicket(): string
    {
        $prefix = 'TKT-';
        $date = now()->format('Ymd');
        $lastTicket = self::where('numero_ticket', 'like', $prefix . $date . '%')
            ->orderBy('numero_ticket', 'desc')
            ->first();
        
        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->numero_ticket, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $date . '-' . $newNumber;
    }
}
