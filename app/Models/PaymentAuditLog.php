<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Journal d'audit verbose des actions paiement (charge, save PM, webhook, réconciliation).
 * Protection légale : traçabilité complète, IP, user-agent, context.
 */
class PaymentAuditLog extends Model
{
    protected $table = 'payment_audit_log';

    protected $fillable = [
        'user_id',
        'action',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_setup_intent_id',
        'stripe_payment_method_id',
        'echeance_id',
        'amount',
        'currency',
        'status',
        'ip_address',
        'user_agent',
        'request_id',
        'context',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        ?int $userId = null,
        array $extra = [],
        ?string $message = null
    ): self {
        $req = app()->runningInConsole() ? null : Request::instance();
        $data = array_merge([
            'user_id' => $userId ?? $req?->user()?->id,
            'action' => $action,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'request_id' => $req?->header('X-Request-ID') ?: Str::uuid()->toString(),
            'message' => $message,
        ], $extra);

        return self::create($data);
    }
}
