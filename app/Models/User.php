<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use App\Traits\HasEssaisGratuits;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable, HasEssaisGratuits;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'est_client',
        'est_gerant',
        'is_admin',
        'photo_profil',
        'abonnement_manuel',
        'abonnement_manuel_actif_jusqu',
        'abonnement_manuel_notes',
        'abonnement_manuel_type_renouvellement',
        'abonnement_manuel_jour_renouvellement',
        'abonnement_manuel_date_debut',
        'abonnement_manuel_montant',
        'notifications_erreurs_actives',
        'telephone',
        'bio',
        'date_naissance',
        'adresse',
        'ville',
        'code_postal',
        'statut_compte',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'est_client' => 'boolean',
            'est_gerant' => 'boolean',
            'is_admin' => 'boolean',
            'trial_ends_at' => 'datetime',
            'abonnement_manuel' => 'boolean',
            'abonnement_manuel_actif_jusqu' => 'date',
            'abonnement_manuel_date_debut' => 'date',
            'abonnement_manuel_montant' => 'decimal:2',
            'notifications_erreurs_actives' => 'boolean',
            'date_naissance' => 'date',
        ];
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs entreprises (s'il est gérant)
     */
    public function entreprises()
    {
        return $this->hasMany(Entreprise::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs prix personnalisés
     */
    public function customPrices()
    {
        return $this->hasMany(CustomPrice::class);
    }

    /**
     * Relation : Un utilisateur (client) peut avoir plusieurs réservations
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Relation : Un utilisateur (client) peut avoir plusieurs factures
     */
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs avis
     */
    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs conversations (en tant que client)
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs messages
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relation : Un utilisateur peut être membre de plusieurs entreprises
     */
    public function entreprisesMembres()
    {
        return $this->hasMany(EntrepriseMembre::class);
    }

    /**
     * Relation : Un utilisateur (client) peut avoir plusieurs notes
     */
    public function clientNotes()
    {
        return $this->hasMany(ClientNote::class, 'user_id');
    }

    /**
     * Notifications non lues
     */
    public function notificationsNonLues()
    {
        return $this->hasMany(Notification::class)->where('est_lue', false)->orderBy('created_at', 'desc');
    }

    /**
     * Nombre de notifications non lues
     */
    public function getNombreNotificationsNonLuesAttribute(): int
    {
        return $this->notificationsNonLues()->count();
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif
     */
    public function aAbonnementActif(): bool
    {
        return \App\Services\SubscriptionService::checkSubscriptionStatus($this);
    }

    /**
     * Vérifie si l'utilisateur est un client
     */
    public function isClient(): bool
    {
        return $this->est_client === true;
    }

    /**
     * Vérifie si l'utilisateur est un gérant
     */
    public function isGerant(): bool
    {
        return $this->est_gerant === true;
    }

    /**
     * Vérifie si l'utilisateur peut acheter (client OU gérant)
     */
    public function canPurchase(): bool
    {
        return $this->est_client === true || $this->est_gerant === true;
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs tentatives de connexion
     */
    public function loginAttempts()
    {
        return $this->hasMany(LoginAttempt::class);
    }

    /**
     * Relation : Un utilisateur peut avoir un blocage de compte
     */
    public function accountLockout()
    {
        return $this->hasOne(AccountLockout::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs codes de réinitialisation
     */
    public function passwordResetCodes()
    {
        return $this->hasMany(PasswordResetCode::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs logs de sécurité
     */
    public function securityLogs()
    {
        return $this->hasMany(SecurityLog::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs IPs dans l'historique
     */
    public function ipHistory()
    {
        return $this->hasMany(UserIpHistory::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs hash de vérification d'email
     */
    public function emailVerifications()
    {
        return $this->hasMany(EmailVerification::class);
    }

    /**
     * Vérifie si le compte est actuellement verrouillé
     */
    public function isAccountLocked(): bool
    {
        $lockout = $this->accountLockout;
        return $lockout && $lockout->isCurrentlyLocked();
    }

    /**
     * Vérifie si le compte est dans un état normal
     */
    public function isNormal(): bool
    {
        return $this->statut_compte === 'normal' || $this->statut_compte === null;
    }

    /**
     * Vérifie si le compte est limité
     */
    public function isLimite(): bool
    {
        return $this->statut_compte === 'limite';
    }

    /**
     * Vérifie si le compte est interdit (ne peut pas se connecter)
     */
    public function isInterdit(): bool
    {
        return $this->statut_compte === 'interdit';
    }

    /**
     * Vérifie si le compte est supprimé (archivé)
     */
    public function isSupprime(): bool
    {
        return $this->statut_compte === 'supprime';
    }

    /**
     * Vérifie si le compte peut se connecter
     */
    public function canLogin(): bool
    {
        return !$this->isInterdit() && !$this->isSupprime();
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutCompteLabelAttribute(): string
    {
        return match($this->statut_compte) {
            'normal' => 'Normal',
            'limite' => 'Limité',
            'interdit' => 'Interdit',
            'supprime' => 'Supprimé',
            default => 'Normal',
        };
    }
}
