<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropositionRendezVous extends Model
{
    protected $fillable = [
        'conversation_id',
        'message_id',
        'auteur_user_id',
        'auteur_type',
        'entreprise_id',
        'type_service_id',
        'date_rdv',
        'heure_debut',
        'heure_fin',
        'duree_minutes',
        'prix_propose',
        'prix_final',
        'statut',
        'notes',
        'lieu',
        'reservation_id',
    ];

    protected function casts(): array
    {
        return [
            'date_rdv' => 'date',
            'heure_debut' => 'datetime',
            'heure_fin' => 'datetime',
            'duree_minutes' => 'integer',
            'prix_propose' => 'decimal:2',
            'prix_final' => 'decimal:2',
        ];
    }

    /**
     * Relation : Une proposition appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation : Une proposition appartient à un message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Relation : Une proposition appartient à un utilisateur (auteur - client ou gérant)
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_user_id');
    }
    
    /**
     * Relation : Une proposition appartient à un utilisateur (pour compatibilité)
     * @deprecated Utiliser auteur() à la place
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_user_id');
    }

    /**
     * Relation : Une proposition appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une proposition peut avoir une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation : Une proposition peut avoir un type de service
     */
    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }

    /**
     * Vérifie si la proposition peut être négociée
     */
    public function peutEtreNegociee(): bool
    {
        return $this->entreprise->prix_negociables && $this->statut === 'proposee';
    }

    /**
     * Vérifie si la proposition est acceptée
     */
    public function estAcceptee(): bool
    {
        return $this->statut === 'acceptee';
    }

    /**
     * Vérifie si l'utilisateur donné est l'auteur de la proposition
     */
    public function estAuteurPar($user): bool
    {
        return $this->auteur_user_id === $user->id;
    }

    /**
     * Vérifie si l'utilisateur donné est le destinataire de la proposition
     */
    public function estDestinatairePar($user): bool
    {
        // Si c'est l'auteur, ce n'est pas le destinataire
        if ($this->estAuteurPar($user)) {
            return false;
        }
        
        // Le destinataire est l'autre partie de la conversation
        // Si l'auteur est le client, le destinataire est le gérant
        // Si l'auteur est le gérant, le destinataire est le client
        
        // Vérifier si l'utilisateur est le gérant de l'entreprise
        $estGerant = $this->entreprise->user_id === $user->id;
        
        // Vérifier si l'utilisateur est le client de la conversation
        $estClient = $this->conversation->user_id === $user->id;
        
        // Utiliser auteur_type pour déterminer le destinataire
        if ($this->auteur_type === 'client') {
            // L'auteur est le client, le destinataire est le gérant
            return $estGerant;
        }
        
        if ($this->auteur_type === 'gerant') {
            // L'auteur est le gérant, le destinataire est le client
            return $estClient;
        }
        
        // Fallback : logique basée sur les IDs (pour compatibilité avec anciennes données)
        if ($this->auteur_user_id === $this->conversation->user_id) {
            return $estGerant;
        }
        
        if ($this->auteur_user_id === $this->entreprise->user_id) {
            return $estClient;
        }
        
        return false;
    }

    /**
     * Retourne le nom de l'auteur de la proposition
     */
    public function getNomAuteurAttribute(): string
    {
        // Charger les relations si nécessaire
        $this->loadMissing(['user', 'entreprise']);
        
        if (!$this->user) {
            return 'Inconnu';
        }
        
        // Si l'auteur est le gérant de l'entreprise, retourner le nom de l'entreprise
        if ($this->entreprise && $this->entreprise->user_id === $this->user_id) {
            return $this->entreprise->nom;
        }
        
        // Sinon, retourner le nom de l'utilisateur (client)
        return $this->user->name;
    }

    /**
     * Retourne le nom du destinataire de la proposition
     */
    public function getNomDestinataireAttribute(): string
    {
        // Charger les relations si nécessaire
        $this->loadMissing(['conversation.user', 'entreprise']);
        
        // Utiliser auteur_type pour déterminer le destinataire
        if ($this->auteur_type === 'client') {
            // L'auteur est le client, le destinataire est l'entreprise
            return $this->entreprise->nom ?? 'Inconnu';
        }
        
        if ($this->auteur_type === 'gerant') {
            // L'auteur est le gérant, le destinataire est le client
            if ($this->conversation->user) {
                return $this->conversation->user->name;
            }
        }
        
        // Fallback : logique basée sur les IDs (pour compatibilité avec anciennes données)
        if ($this->auteur_user_id === $this->conversation->user_id) {
            return $this->entreprise->nom ?? 'Inconnu';
        }
        
        if ($this->entreprise && $this->entreprise->user_id === $this->auteur_user_id) {
            if ($this->conversation->user) {
                return $this->conversation->user->name;
            }
        }
        
        return 'Inconnu';
    }
}
