<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Récupérer une valeur de paramètre
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Définir une valeur de paramètre
     */
    public static function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
                'type' => $type ?? 'string',
                'label' => ucfirst(str_replace('_', ' ', $key)),
            ]);
        }

        Cache::forget("setting.{$key}");
    }

    /**
     * Convertir la valeur selon le type
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match($type) {
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Récupérer tous les paramètres d'un groupe
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }
        
        return $result;
    }

    /**
     * Récupérer tous les paramètres groupés
     */
    public static function getAllGrouped(): array
    {
        $settings = self::orderBy('group')->orderBy('id')->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->group][] = $setting;
        }
        
        return $result;
    }

    /**
     * Initialiser les paramètres par défaut
     */
    public static function initDefaults(): void
    {
        $defaults = [
            // Général
            ['key' => 'site_name', 'value' => 'Allo Tata', 'type' => 'string', 'group' => 'general', 'label' => 'Nom du site'],
            ['key' => 'contact_email', 'value' => 'contact@allotata.fr', 'type' => 'string', 'group' => 'general', 'label' => 'Email de contact'],
            
            // Abonnements
            ['key' => 'subscription_price', 'value' => '29.99', 'type' => 'float', 'group' => 'subscription', 'label' => 'Prix abonnement mensuel (€)'],
            ['key' => 'trial_days', 'value' => '14', 'type' => 'integer', 'group' => 'subscription', 'label' => 'Durée période d\'essai (jours)'],
            
            // Commission
            ['key' => 'commission_percentage', 'value' => '10', 'type' => 'float', 'group' => 'commission', 'label' => 'Commission plateforme (%)'],
        ];

        foreach ($defaults as $setting) {
            if (!self::where('key', $setting['key'])->exists()) {
                self::create($setting);
            }
        }
    }
}
