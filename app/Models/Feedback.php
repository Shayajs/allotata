<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'categorie',
        'statut',
        'votes_count',
        'commentaires_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class);
    }

    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function toggleVote(int $userId): bool
    {
        $vote = $this->votes()->where('user_id', $userId)->first();
        
        if ($vote) {
            $vote->delete();
            $this->decrement('votes_count');
            return false; // Vote retiré
        } else {
            $this->votes()->create(['user_id' => $userId]);
            $this->increment('votes_count');
            return true; // Vote ajouté
        }
    }
}
