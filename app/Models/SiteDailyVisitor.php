<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteDailyVisitor extends Model
{
    public const TYPE_MEMBER = 'member';

    public const TYPE_GUEST = 'guest';

    public const TYPE_BOT = 'bot';

    protected $fillable = [
        'visit_date',
        'session_id',
        'user_id',
        'visitor_type',
        'page_views',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'page_views' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
