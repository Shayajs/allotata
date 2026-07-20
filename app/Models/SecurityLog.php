<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'location',
        'metadata',
        'severity',
        'is_suspicious',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_suspicious' => 'boolean',
        ];
    }

    /**
     * Relation : Un log appartient à un utilisateur (optionnel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crée un log de sécurité
     */
    public static function log(
        ?int $userId,
        string $eventType,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $location = null,
        array $metadata = [],
        string $severity = 'low',
        bool $isSuspicious = false,
        ?string $description = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'location' => $location,
            'metadata' => $metadata,
            'severity' => $severity,
            'is_suspicious' => $isSuspicious,
            'description' => $description,
        ]);
    }

    public static function labelForEvent(string $eventType): string
    {
        return match ($eventType) {
            'admin_account_access_view' => 'Consultation admin (lecture seule)',
            'admin_account_access_edit' => 'Accès admin (mode édition)',
            'admin_account_access_support' => 'Accès admin (mode support)',
            'admin_account_access_billing' => 'Accès admin (mode facturation)',
            'admin_entreprise_profile_updated' => 'Profil entreprise modifié par un admin',
            'admin_entreprise_logo_updated' => 'Logo entreprise modifié par un admin',
            'admin_entreprise_logo_deleted' => 'Logo entreprise supprimé par un admin',
            'admin_entreprise_image_fond_updated' => 'Image de fond modifiée par un admin',
            'admin_entreprise_image_fond_deleted' => 'Image de fond supprimée par un admin',
            'admin_entreprise_photo_added' => 'Photo entreprise ajoutée par un admin',
            'admin_entreprise_photo_deleted' => 'Photo entreprise supprimée par un admin',
            'admin_account_action' => 'Action admin sur votre compte',
            'admin_password_reset' => 'Mot de passe réinitialisé par un admin',
            'admin_email_change' => 'Email modifié par un admin',
            'admin_account_blocked' => 'Compte bloqué par un admin',
            'admin_account_unblocked' => 'Compte débloqué par un admin',
            'admin_account_archived' => 'Compte archivé par un admin',
            'account_status_changed' => 'Statut du compte modifié',
            'login_success' => 'Connexion réussie',
            'login_failed' => 'Échec de connexion',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }

    public function isAdminAccountEvent(): bool
    {
        return str_starts_with($this->event_type, 'admin_account_');
    }
}
