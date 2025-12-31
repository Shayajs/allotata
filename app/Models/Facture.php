<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'entreprise_id',
        'user_id',
        'numero_facture',
        'date_facture',
        'date_echeance',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'statut',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_facture' => 'date',
            'date_echeance' => 'date',
            'montant_ht' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'montant_ttc' => 'decimal:2',
        ];
    }

    /**
     * Relation : Une facture appartient à une réservation (pour compatibilité avec factures simples)
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation : Une facture peut avoir plusieurs réservations (factures groupées)
     */
    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'facture_reservation')
            ->withTimestamps();
    }

    /**
     * Vérifie si la facture est groupée (plusieurs réservations)
     */
    public function estGroupee(): bool
    {
        return $this->reservations()->count() > 0;
    }

    /**
     * Relation : Une facture appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une facture appartient à un client (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Génère un numéro de facture unique
     */
    public static function generateNumeroFacture(): string
    {
        $year = date('Y');
        $lastFacture = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastFacture ? (int) substr($lastFacture->numero_facture, -6) + 1 : 1;
        
        return 'FAC-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Génère automatiquement une facture à partir d'une réservation payée
     */
    public static function generateFromReservation(Reservation $reservation): ?Facture
    {
        // Recharger la réservation pour avoir les relations à jour
        $reservation->refresh();
        
        // Vérifier si une facture existe déjà pour cette réservation
        $factureExistante = self::where('reservation_id', $reservation->id)->first();
        if ($factureExistante) {
            return $factureExistante;
        }

        // Recharger l'entreprise pour avoir les dernières valeurs
        $reservation->load('entreprise');
        $entreprise = $reservation->entreprise;
        
        // Vérifier que la réservation a un prix valide
        if (!$reservation->prix || $reservation->prix <= 0) {
            \Log::warning("Impossible de générer une facture pour la réservation #{$reservation->id} : prix invalide ou nul");
            return null;
        }

        // Calculer les montants (TVA à 0% par défaut pour les auto-entrepreneurs)
        $montantHT = $reservation->prix;
        
        // Déterminer le taux de TVA selon le statut juridique
        $tauxTVA = 0; // Par défaut 0% (auto-entrepreneur, micro-entreprise)
        if ($entreprise->status_juridique === 'sarl' || $entreprise->status_juridique === 'eurl' || $entreprise->status_juridique === 'sas') {
            // Pour les sociétés, on peut appliquer la TVA standard (20%)
            // Mais on garde 0% par défaut, l'admin peut modifier si nécessaire
            $tauxTVA = 0;
        }
        
        $montantTVA = $montantHT * ($tauxTVA / 100);
        $montantTTC = $montantHT + $montantTVA;

        // Créer la facture (même sans SIREN vérifié, pour permettre la facturation aux auto-entrepreneurs)
        $facture = self::create([
            'reservation_id' => $reservation->id,
            'entreprise_id' => $reservation->entreprise_id,
            'user_id' => $reservation->user_id,
            'numero_facture' => self::generateNumeroFacture(),
            'date_facture' => now(),
            'date_echeance' => now()->addDays(30), // 30 jours par défaut
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => 'emise',
        ]);

        return $facture;
    }

    /**
     * Génère une facture groupée à partir de plusieurs réservations
     */
    public static function generateFromReservations(array $reservationIds, $entrepriseId, $userId, $tauxTVA = 0): ?Facture
    {
        if (empty($reservationIds)) {
            return null;
        }

        $reservations = Reservation::whereIn('id', $reservationIds)
            ->where('entreprise_id', $entrepriseId)
            ->where('user_id', $userId)
            ->where('est_paye', true)
            ->with(['entreprise'])
            ->get();

        if ($reservations->isEmpty()) {
            return null;
        }

        // Vérifier qu'aucune de ces réservations n'a déjà une facture (simple ou groupée)
        foreach ($reservations as $reservation) {
            if ($reservation->aDejaFacture()) {
                \Log::warning("La réservation #{$reservation->id} a déjà une facture");
                return null;
            }
        }

        $entreprise = $reservations->first()->entreprise;

        // Calculer le montant total HT
        $montantHT = $reservations->sum('prix');
        $montantTVA = $montantHT * ($tauxTVA / 100);
        $montantTTC = $montantHT + $montantTVA;

        // Créer la facture groupée
        $facture = self::create([
            'reservation_id' => null, // Null pour les factures groupées
            'entreprise_id' => $entrepriseId,
            'user_id' => $userId,
            'numero_facture' => self::generateNumeroFacture(),
            'date_facture' => now(),
            'date_echeance' => now()->addDays(30),
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => 'emise',
        ]);

        // Attacher les réservations à la facture
        $facture->reservations()->attach($reservationIds);

        return $facture;
    }
}
