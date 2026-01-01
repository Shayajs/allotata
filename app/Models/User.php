<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

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
        'notifications_erreurs_actives',
        'telephone',
        'bio',
        'date_naissance',
        'adresse',
        'ville',
        'code_postal',
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
        // Vérifier l'abonnement manuel
        if ($this->abonnement_manuel && $this->abonnement_manuel_actif_jusqu) {
            return $this->abonnement_manuel_actif_jusqu->isFuture() || $this->abonnement_manuel_actif_jusqu->isToday();
        }

        // Vérifier l'abonnement Stripe
        return $this->subscribed('default');
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
}
