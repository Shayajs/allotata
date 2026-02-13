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
        'surname',
        'email',
        'password',
        'est_client',
        'est_gerant',
        // 'is_admin' — EXCLUS : assignation explicite uniquement (sécurité mass assignment)
        'photo_profil',
        'abonnement_manuel',
        'abonnement_manuel_actif_jusqu',
        'abonnement_manuel_notes',
        'abonnement_manuel_type_renouvellement',
        'abonnement_manuel_jour_renouvellement',
        'jour_facturation',
        'abonnement_manuel_date_debut',
        'abonnement_manuel_montant',
        'notifications_erreurs_actives',
        'tracking_consent',
        'interbloquer_entreprises',
        'telephone',
        'bio',
        'date_naissance',
        'adresse',
        'ville',
        'code_postal',
        'latitude',
        'longitude',
        'statut_compte',
        'a2f_enabled',
        'a2f_method',
        'a2f_method_email',
        'a2f_method_sms',
        'recovery_method_email',
        'recovery_method_sms',
        'google2fa_enabled',
        // 'google2fa_secret' — EXCLUS : assignation explicite uniquement (sécurité mass assignment)
        'google2fa_recovery_codes',
        'stripe_payment_method_id',
        // 'stripe_id' — EXCLUS : géré par Cashier (forceFill) ou assignation explicite
        'pm_type',
        'pm_last_four',
        'client_guide_dismissed_at',
        // Acceptation CGU / CGV / Politique de confidentialité
        'cgu_accepted_at',
        'cgv_accepted_at',
        'confidentialite_accepted_at',
        // Préférences de notifications push
        'notifications_reservations',
        'notifications_paiements',
        'notifications_messages',
        'notifications_rappels',
        'notifications_promotions',
        'notifications_mises_a_jour',
        'push_banner_dismissed_at',
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
            'a2f_method_email' => 'boolean',
            'a2f_method_sms' => 'boolean',
            'recovery_method_email' => 'boolean',
            'recovery_method_sms' => 'boolean',
            'est_gerant' => 'boolean',
            'is_admin' => 'boolean',
            'trial_ends_at' => 'datetime',
            'abonnement_manuel' => 'boolean',
            'abonnement_manuel_actif_jusqu' => 'date',
            'abonnement_manuel_date_debut' => 'date',
            'abonnement_manuel_montant' => 'decimal:2',
            'notifications_erreurs_actives' => 'boolean',
            'tracking_consent' => 'boolean',
            'interbloquer_entreprises' => 'boolean',
            'date_naissance' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'a2f_enabled' => 'boolean',
            'google2fa_enabled' => 'boolean',
            'google2fa_recovery_codes' => 'array',
            'client_guide_dismissed_at' => 'datetime',
            'cgu_accepted_at' => 'datetime',
            'cgv_accepted_at' => 'datetime',
            'confidentialite_accepted_at' => 'datetime',
            'notifications_reservations' => 'boolean',
            'notifications_paiements' => 'boolean',
            'notifications_messages' => 'boolean',
            'notifications_rappels' => 'boolean',
            'notifications_promotions' => 'boolean',
            'notifications_mises_a_jour' => 'boolean',
            'push_banner_dismissed_at' => 'datetime',
        ];
    }

    /**
     * Vérifie si l'utilisateur a déjà masqué le guide client.
     */
    public function hasSeenClientGuide(): bool
    {
        return $this->client_guide_dismissed_at !== null;
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
     * Relation : Un utilisateur peut avoir plusieurs échéances
     */
    public function echeances()
    {
        return $this->hasMany(Echeance::class);
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
     * Relation : Un utilisateur peut avoir plusieurs avis sur des services
     */
    public function serviceAvis()
    {
        return $this->hasMany(ServiceAvis::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs avis sur des produits
     */
    public function produitAvis()
    {
        return $this->hasMany(ProduitAvis::class);
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
     * Relation : Un utilisateur peut avoir plusieurs souscriptions push
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs hash de vérification d'email
     */
    public function emailVerifications()
    {
        return $this->hasMany(EmailVerification::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs codes A2F
     */
    public function twoFactorCodes()
    {
        return $this->hasMany(TwoFactorCode::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs périphériques approuvés
     */
    public function trustedDevices()
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs utilisations de codes de récupération
     */
    public function recoveryCodeUsages()
    {
        return $this->hasMany(Google2faRecoveryCodeUsage::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs progressions de leçons
     */
    public function lessonProgress()
    {
        return $this->hasMany(UserLessonProgress::class);
    }

    /**
     * Relation : Un utilisateur peut avoir plusieurs progressions de modules
     */
    public function moduleProgress()
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    /**
     * Relation : Un utilisateur a une présence
     */
    public function presence()
    {
        return $this->hasOne(UserPresence::class);
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

    /**
     * Générer un nouveau secret pour Google 2FA
     */
    public function generateGoogle2faSecret(): string
    {
        try {
            // Essayer d'utiliser le package google2fa via le service container
            if (class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
                $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
            } else {
                // Fallback : utiliser directement la classe si le package est installé différemment
                $google2fa = new \PragmaRX\Google2FA\Google2FA();
            }
            
            $this->google2fa_secret = encrypt($google2fa->generateSecretKey(32));
            $this->save();
            return decrypt($this->google2fa_secret);
        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible de générer le secret Google2FA. Assurez-vous que le package pragmarx/google2fa est installé.');
        }
    }

    /**
     * Vérifier un code TOTP
     */
    public function verifyGoogle2faCode(string $code): bool
    {
        if (!$this->google2fa_enabled || !$this->google2fa_secret) {
            return false;
        }

        try {
            // Essayer d'utiliser le package google2fa via le service container
            if (class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
                $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
            } else {
                // Fallback : utiliser directement la classe si le package est installé différemment
                $google2fa = new \PragmaRX\Google2FA\Google2FA();
            }
            
            $secret = decrypt($this->google2fa_secret);
            
            // Vérifier le code actuel et les deux fenêtres de temps précédentes/suivantes
            return $google2fa->verifyKey($secret, $code, 2);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification du code Google2FA: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un code de récupération est valide
     */
    public function verifyRecoveryCode(string $code, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $recoveryCodes = $this->google2fa_recovery_codes ?? [];
        
        if (empty($recoveryCodes)) {
            return false;
        }

        // Normaliser le code (uppercase)
        $code = strtoupper(trim($code));
        
        // Vérifier si le code existe dans la liste
        if (!in_array($code, $recoveryCodes)) {
            return false;
        }

        // Vérifier si le code a déjà été utilisé
        $alreadyUsed = \App\Models\Google2faRecoveryCodeUsage::where('user_id', $this->id)
            ->where('code', $code)
            ->exists();

        if ($alreadyUsed) {
            return false; // Code déjà utilisé
        }

        // Enregistrer l'utilisation du code (mais ne pas le retirer de la liste)
        \App\Models\Google2faRecoveryCodeUsage::create([
            'user_id' => $this->id,
            'code' => $code,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'used_at' => now(),
        ]);

        return true;
    }

    /**
     * Obtenir tous les codes de récupération avec leur statut (utilisé ou non)
     */
    public function getRecoveryCodesWithStatus(): array
    {
        $recoveryCodes = $this->google2fa_recovery_codes ?? [];
        $usedCodes = \App\Models\Google2faRecoveryCodeUsage::where('user_id', $this->id)
            ->pluck('code')
            ->toArray();

        $codesWithStatus = [];
        foreach ($recoveryCodes as $code) {
            $codesWithStatus[] = [
                'code' => $code,
                'used' => in_array(strtoupper($code), array_map('strtoupper', $usedCodes)),
                'used_at' => null,
            ];
        }

        // Ajouter la date d'utilisation pour les codes utilisés
        $usages = \App\Models\Google2faRecoveryCodeUsage::where('user_id', $this->id)
            ->get()
            ->keyBy(function ($usage) {
                return strtoupper($usage->code);
            });

        foreach ($codesWithStatus as &$codeData) {
            if ($codeData['used']) {
                $usage = $usages->get(strtoupper($codeData['code']));
                $codeData['used_at'] = $usage ? $usage->used_at : null;
            }
        }

        return $codesWithStatus;
    }

    /**
     * Générer de nouveaux codes de récupération
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        
        $this->google2fa_recovery_codes = $codes;
        $this->save();
        
        return $codes;
    }

    /**
     * Activer Google 2FA
     */
    public function enableGoogle2fa(): void
    {
        $this->google2fa_enabled = true;
        $this->save();
    }

    /**
     * Désactiver Google 2FA
     */
    public function disableGoogle2fa(): void
    {
        $this->google2fa_enabled = false;
        $this->google2fa_secret = null;
        $this->google2fa_recovery_codes = null;
        $this->save();
    }

    /**
     * Vérifie si l'utilisateur a l'A2F TOTP activé
     */
    public function hasGoogle2faEnabled(): bool
    {
        // Vérifier d'abord si le package est installé
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return false;
        }
        
        return $this->google2fa_enabled && !empty($this->google2fa_secret);
    }

    /**
     * Obtenir le prénom (extrait du nom complet si surname est vide)
     */
    public function getFirstNameAttribute(): string
    {
        if (!empty($this->surname)) {
            // Si on a un surname, le name contient soit juste le prénom, soit le nom complet
            // On extrait le premier mot du name
            $parts = explode(' ', trim($this->name), 2);
            return $parts[0];
        }
        // Si pas de surname, extraire le prénom du nom complet
        $parts = explode(' ', trim($this->name), 2);
        return $parts[0] ?? $this->name;
    }

    /**
     * Obtenir le nom de famille (surname ou extrait du nom complet)
     */
    public function getLastNameAttribute(): string
    {
        if (!empty($this->surname)) {
            return $this->surname;
        }
        // Si pas de surname, extraire le nom de famille du nom complet
        $parts = explode(' ', trim($this->name), 2);
        return $parts[1] ?? '';
    }

    /**
     * Obtenir le nom formaté en version courte (P. Nom)
     * Pour affichage sur mobile/petits écrans
     */
    public function getShortNameAttribute(): string
    {
        $firstName = $this->first_name;
        $lastName = $this->last_name;
        
        if (empty($lastName)) {
            // Si pas de nom de famille, retourner le nom complet tel quel
            return $this->name;
        }
        
        // Prendre la première lettre du prénom
        $firstInitial = !empty($firstName) ? strtoupper(substr(trim($firstName), 0, 1)) . '.' : '';
        
        $result = trim($firstInitial . ' ' . $lastName);
        return !empty($result) ? $result : $this->name;
    }

    /**
     * Obtenir le nom formaté en version complète (Prénom Nom)
     * Pour affichage sur desktop/grands écrans
     */
    public function getFullNameAttribute(): string
    {
        $firstName = $this->first_name;
        $lastName = $this->last_name;
        
        if (empty($lastName)) {
            // Si pas de nom de famille, retourner le nom complet tel quel
            return $this->name;
        }
        
        $result = trim($firstName . ' ' . $lastName);
        return !empty($result) ? $result : $this->name;
    }
}
