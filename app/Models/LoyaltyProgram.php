<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'points',
        'total_points_earned',
        'total_points_spent',
        'level',
        'badges',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'total_points_earned' => 'integer',
            'total_points_spent' => 'integer',
            'level' => 'integer',
            'badges' => 'array',
        ];
    }

    /**
     * Relation : Un programme de fidélité appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un programme de fidélité appartient à un client
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un programme de fidélité a plusieurs transactions
     */
    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Ajouter des points
     */
    public function addPoints(int $points, string $reason = ''): void
    {
        $this->points += $points;
        $this->total_points_earned += $points;
        $this->updateLevel();
        $this->save();

        // Créer un historique
        LoyaltyTransaction::create([
            'loyalty_program_id' => $this->id,
            'points' => $points,
            'type' => 'earned',
            'reason' => $reason,
        ]);
    }

    /**
     * Dépenser des points
     */
    public function spendPoints(int $points, string $reason = ''): bool
    {
        if ($this->points < $points) {
            return false;
        }

        $this->points -= $points;
        $this->total_points_spent += $points;
        $this->save();

        // Créer un historique
        LoyaltyTransaction::create([
            'loyalty_program_id' => $this->id,
            'points' => -$points,
            'type' => 'spent',
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Mettre à jour le niveau en fonction des points
     */
    private function updateLevel(): void
    {
        $newLevel = 1;
        if ($this->total_points_earned >= 1000) {
            $newLevel = 5; // VIP
        } elseif ($this->total_points_earned >= 500) {
            $newLevel = 4; // Gold
        } elseif ($this->total_points_earned >= 250) {
            $newLevel = 3; // Silver
        } elseif ($this->total_points_earned >= 100) {
            $newLevel = 2; // Bronze
        }

        if ($newLevel > $this->level) {
            $this->level = $newLevel;
        }
    }

    /**
     * Obtenir ou créer un programme de fidélité pour un client
     */
    public static function getOrCreate(int $entrepriseId, int $userId): self
    {
        return self::firstOrCreate(
            [
                'entreprise_id' => $entrepriseId,
                'user_id' => $userId,
            ],
            [
                'points' => 0,
                'total_points_earned' => 0,
                'total_points_spent' => 0,
                'level' => 1,
                'badges' => [],
            ]
        );
    }
}
