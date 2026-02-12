<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EntrepriseVisite extends Model
{
    use HasFactory;

    protected $table = 'entreprise_visites';

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'page_type',
        'duree_seconde',
        'a_quitte_rapidement',
        'a_quitte_apres_exploration',
        'nb_clics_services',
        'nb_clics_produits',
        'a_passe_commande',
        'date_reservation',
        'temps_avant_reservation_secondes',
    ];

    protected function casts(): array
    {
        return [
            'a_quitte_rapidement' => 'boolean',
            'a_quitte_apres_exploration' => 'boolean',
            'a_passe_commande' => 'boolean',
            'date_reservation' => 'datetime',
            'duree_seconde' => 'integer',
            'nb_clics_services' => 'integer',
            'nb_clics_produits' => 'integer',
            'temps_avant_reservation_secondes' => 'integer',
        ];
    }

    /**
     * Relation : Une visite appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une visite appartient à un utilisateur (si connecté)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une visite peut avoir plusieurs clics
     */
    public function clics(): HasMany
    {
        return $this->hasMany(VisiteClic::class, 'visite_id');
    }

    /**
     * Enregistrer une nouvelle visite
     */
    public static function enregistrerVisite(Entreprise $entreprise, string $pageType = 'accueil', ?User $user = null): self
    {
        $sessionId = Session::getId();
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        return self::create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'page_type' => $pageType,
        ]);
    }

    /**
     * Mettre à jour la durée de visite
     */
    public function mettreAJourDuree(int $dureeSecondes): void
    {
        $this->update([
            'duree_seconde' => $dureeSecondes,
            'a_quitte_rapidement' => $dureeSecondes < 3,
            'a_quitte_apres_exploration' => $dureeSecondes >= 7 && !$this->a_passe_commande,
        ]);
    }

    /**
     * Marquer un clic sur un service ou produit
     */
    public function marquerClic(string $type, int $itemId, string $itemNom): VisiteClic
    {
        // Incrémenter le compteur
        if ($type === 'service') {
            $this->increment('nb_clics_services');
        } else {
            $this->increment('nb_clics_produits');
        }

        // Créer l'enregistrement de clic
        return VisiteClic::create([
            'visite_id' => $this->id,
            'type' => $type,
            'item_id' => $itemId,
            'item_nom' => $itemNom,
            'clicked_at' => now(),
        ]);
    }

    /**
     * Marquer qu'une réservation a été créée
     */
    public function marquerReservation(): void
    {
        $this->update([
            'a_passe_commande' => true,
            'date_reservation' => now(),
            'temps_avant_reservation_secondes' => $this->duree_seconde ?? 0,
            'a_quitte_apres_exploration' => false, // Plus une visite abandonnée
        ]);
    }

    /**
     * Récupérer les visiteurs connectés sans réservation
     */
    public static function visiteursSansReservation(int $entrepriseId, int $periodDays = 30): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('entreprise_id', $entrepriseId)
            ->whereNotNull('user_id')
            ->where('a_passe_commande', false)
            ->where('a_quitte_apres_exploration', true) // > 7 secondes sans réservation
            ->where('created_at', '>=', now()->subDays($periodDays))
            ->with(['user', 'clics'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('user_id') // Un visiteur par utilisateur
            ->values()
            ->sortByDesc('created_at')
            ->values();
    }
}
