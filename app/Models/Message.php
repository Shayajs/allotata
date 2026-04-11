<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'contenu',
        'image',
        'est_lu',
        'type_message', // 'texte', 'proposition_rdv'
        'proposition_rdv_id',
    ];

    protected function casts(): array
    {
        return [
            'est_lu' => 'boolean',
        ];
    }

    /**
     * Relation : Un message appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation : Un message appartient à un utilisateur (expéditeur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le message contient une image
     */
    public function aImage(): bool
    {
        return ! empty($this->image);
    }

    /**
     * Nettoie le texte du message : retire les collages d'image (data:image…;base64,…)
     * et le HTML, qui s'affichaient comme du « code » dans la bulle (échappement Blade).
     */
    public static function sanitizeContenuForStorage(?string $contenu): ?string
    {
        if ($contenu === null) {
            return null;
        }

        $contenu = trim($contenu);
        if ($contenu === '') {
            return null;
        }

        // Collage navigateur / mobile dans le textarea → blob base64 illisible
        $contenu = preg_replace('/data:image\/[\w.+-]+;base64,[\r\n\sA-Za-z0-9+\/=]*+/i', '', $contenu);
        $contenu = strip_tags($contenu);
        $contenu = trim(preg_replace('/\s+/u', ' ', $contenu));

        return $contenu === '' ? null : $contenu;
    }

    /**
     * Texte affichable dans la bulle (hors champ image joint).
     */
    public function contenuPourAffichage(): ?string
    {
        return self::sanitizeContenuForStorage($this->contenu);
    }

    /**
     * Aperçu du dernier message dans la liste des conversations (pas de base64 / HTML brut).
     */
    public function apercuListeConversation(): string
    {
        $t = $this->contenuPourAffichage();
        if ($t !== null && $t !== '') {
            return $t;
        }
        if (! empty($this->image)) {
            return '📷 Photo';
        }
        if ($this->doitAfficherAideCollePhoto()) {
            return '📷 Photo (à renvoyer via le bouton image)';
        }

        return 'Message';
    }

    /**
     * Message illisible : collage image en base64 ou HTML dans le texte, sans pièce jointe image.
     */
    public function doitAfficherAideCollePhoto(): bool
    {
        if (empty($this->contenu) || ! empty($this->image)) {
            return false;
        }
        if (preg_match('/data:image\//i', $this->contenu)) {
            return true;
        }

        return $this->contenuPourAffichage() === null && (bool) preg_match('/<[^>]+>/', $this->contenu);
    }

    /**
     * Relation : Un message peut avoir une proposition de rendez-vous (via message_id dans proposition_rendez_vouses)
     */
    public function propositionRendezVous()
    {
        return $this->hasOne(PropositionRendezVous::class, 'message_id');
    }

    /**
     * Relation : Un message peut référencer une proposition de rendez-vous (via proposition_rdv_id)
     */
    public function propositionRdv()
    {
        return $this->belongsTo(PropositionRendezVous::class, 'proposition_rdv_id');
    }

    /**
     * Vérifie si le message est une proposition de rendez-vous
     */
    public function estPropositionRendezVous(): bool
    {
        return $this->type_message === 'proposition_rdv' || ! empty($this->proposition_rdv_id);
    }
}
