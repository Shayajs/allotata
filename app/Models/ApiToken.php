<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Jeton personnel d'acces a l'API de gestion (api.allotata.*).
 *
 * Le jeton en clair n'existe qu'une fois, au moment de sa creation : la base ne
 * garde que son empreinte SHA-256.
 */
class ApiToken extends Model
{
    /** Prefixe visible du jeton, pour le reconnaitre dans un fichier de config. */
    public const PREFIXE = 'alto_';

    protected $fillable = [
        'user_id',
        'nom',
        'token_hash',
        'apercu',
        'derniere_utilisation_at',
        'expire_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'derniere_utilisation_at' => 'datetime',
            'expire_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cree un jeton et retourne sa version en clair, la seule fois ou elle existe.
     *
     * @return array{jeton: string, modele: self}
     */
    public static function creerPour(User $user, string $nom, ?Carbon $expireAt = null): array
    {
        $secret = Str::random(48);
        $jeton = self::PREFIXE.$secret;

        $modele = self::create([
            'user_id' => $user->id,
            'nom' => $nom,
            'token_hash' => self::empreinte($jeton),
            'apercu' => substr($secret, 0, 6),
            'expire_at' => $expireAt,
        ]);

        return ['jeton' => $jeton, 'modele' => $modele];
    }

    /**
     * Retrouve le jeton valide correspondant a une chaine presentee par un client.
     */
    public static function resoudre(string $jeton): ?self
    {
        if (! str_starts_with($jeton, self::PREFIXE)) {
            return null;
        }

        $modele = self::with('user')->where('token_hash', self::empreinte($jeton))->first();

        return $modele && ! $modele->estExpire() ? $modele : null;
    }

    public function estExpire(): bool
    {
        return $this->expire_at !== null && $this->expire_at->isPast();
    }

    /**
     * Horodate l'usage sans ecrire a chaque appel : la minute suffit a diagnostiquer.
     */
    public function marquerUtilise(): void
    {
        if ($this->derniere_utilisation_at?->gt(now()->subMinute())) {
            return;
        }

        $this->forceFill(['derniere_utilisation_at' => now()])->saveQuietly();
    }

    private static function empreinte(string $jeton): string
    {
        return hash('sha256', $jeton);
    }
}
