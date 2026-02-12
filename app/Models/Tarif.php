<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Tarif extends Model
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_SITE_WEB = 'site_web';
    public const TYPE_MULTI_PERSONNES = 'multi_personnes';

    protected $fillable = ['type', 'amount', 'currency', 'label'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /**
     * Montant par défaut pour un type (depuis la table tarifs).
     */
    public static function getAmount(string $type): float
    {
        $t = self::get($type);
        return $t ? (float) $t->amount : 0.0;
    }

    /**
     * Tarif par type (cached court).
     */
    public static function get(string $type): ?self
    {
        return Cache::remember("tarif.{$type}", 300, fn () => self::where('type', $type)->first());
    }

    /**
     * Tous les tarifs (admin, pas de cache).
     */
    public static function allForAdmin(): \Illuminate\Support\Collection
    {
        return self::orderBy('type')->get();
    }

    /**
     * Devise (celle du premier tarif ou eur).
     */
    public static function currency(): string
    {
        $t = self::get(self::TYPE_DEFAULT);
        return $t ? strtolower($t->currency) : 'eur';
    }

    /**
     * Invalider le cache après mise à jour.
     */
    public static function clearCache(): void
    {
        foreach ([self::TYPE_DEFAULT, self::TYPE_SITE_WEB, self::TYPE_MULTI_PERSONNES] as $type) {
            Cache::forget("tarif.{$type}");
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }

    public function getFormattedAttribute(): string
    {
        $s = strtoupper($this->currency ?? 'eur') === 'EUR' ? '€' : ($this->currency ?? '€');
        return number_format((float) $this->amount, 2, ',', ' ') . $s;
    }

    /**
     * Prix affiché pour un user (CustomPrice écrase si présent).
     * @return array{amount: float, currency: string, formatted: string, period: string}
     */
    public static function displayForUser(?\App\Models\User $user, string $type): array
    {
        $amount = 0.0;
        $currency = 'eur';
        if ($user) {
            $custom = CustomPrice::getForUser($user, $type);
            if ($custom && $custom->isValid()) {
                $amount = (float) $custom->amount;
                $currency = $custom->currency ?? 'eur';
            } else {
                $t = self::get($type);
                $amount = $t ? (float) $t->amount : 0;
                $currency = $t ? ($t->currency ?? 'eur') : 'eur';
            }
        } else {
            $amount = self::getAmount($type);
            $t = self::get($type);
            $currency = $t ? ($t->currency ?? 'eur') : 'eur';
        }
        $sym = strtoupper($currency) === 'EUR' ? '€' : $currency;
        return [
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'formatted' => number_format($amount, 2, ',', ' ') . $sym,
            'period' => '/mois',
        ];
    }

    /**
     * Prix affiché pour une entreprise (CustomPrice écrase si présent).
     * @return array{amount: float, currency: string, formatted: string, period: string}
     */
    public static function displayForEntreprise(?\App\Models\Entreprise $entreprise, string $type): array
    {
        $amount = 0.0;
        $currency = 'eur';
        if ($entreprise) {
            $custom = CustomPrice::getForEntreprise($entreprise, $type);
            if ($custom && $custom->isValid()) {
                $amount = (float) $custom->amount;
                $currency = $custom->currency ?? 'eur';
            } else {
                $t = self::get($type);
                $amount = $t ? (float) $t->amount : 0;
                $currency = $t ? ($t->currency ?? 'eur') : 'eur';
            }
        } else {
            $amount = self::getAmount($type);
            $t = self::get($type);
            $currency = $t ? ($t->currency ?? 'eur') : 'eur';
        }
        $sym = strtoupper($currency) === 'EUR' ? '€' : $currency;
        return [
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'formatted' => number_format($amount, 2, ',', ' ') . $sym,
            'period' => '/mois',
        ];
    }

    /**
     * Prix par défaut (sans user/entreprise).
     * @return array{amount: float, currency: string, formatted: string, period: string}
     */
    public static function displayDefault(string $type): array
    {
        return self::displayForUser(null, $type);
    }
}
