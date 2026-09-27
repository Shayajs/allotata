<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonnementManuelEvenement extends Model
{
    public const STARTED = 'started';

    public const DUE_CREATED = 'due_created';

    public const CONFIRMED = 'confirmed';

    public const REFUSED = 'refused';

    public const DECLARED = 'declared';

    public const REACTIVATED = 'reactivated';

    public const REVOKED = 'revoked';

    protected $table = 'abonnement_manuel_evenements';

    protected $fillable = [
        'user_id',
        'abonnement_manuel_periode_id',
        'actor_id',
        'type',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(AbonnementManuelPeriode::class, 'abonnement_manuel_periode_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
