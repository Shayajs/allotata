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
        'entreprise_subscription_id',
        'type_facture',
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
     * Relation : Une facture peut appartenir à un abonnement entreprise
     */
    public function entrepriseSubscription(): BelongsTo
    {
        return $this->belongsTo(EntrepriseSubscription::class);
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
     * Génère une facture pour un abonnement manuel utilisateur
     */
    public static function generateFromManualSubscription(User $user, \Carbon\Carbon $dateFacture = null): ?Facture
    {
        if (!$user->abonnement_manuel || !$user->abonnement_manuel_montant) {
            return null;
        }

        $dateFacture = $dateFacture ?? now();
        
        // Vérifier si une facture existe déjà pour cette période
        $periodeDebut = $dateFacture->copy()->startOfMonth();
        $periodeFin = $dateFacture->copy()->endOfMonth();
        
        if ($user->abonnement_manuel_type_renouvellement === 'annuel') {
            $periodeDebut = $dateFacture->copy()->startOfYear();
            $periodeFin = $dateFacture->copy()->endOfYear();
        }

        $factureExistante = self::where('user_id', $user->id)
            ->where('type_facture', 'abonnement_manuel')
            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
            ->first();

        if ($factureExistante) {
            return $factureExistante;
        }

        // Calculer les montants (TVA à 0% par défaut)
        $montantHT = $user->abonnement_manuel_montant;
        $tauxTVA = 0;
        $montantTVA = 0;
        $montantTTC = $montantHT;

        // Créer la facture
        // Note: Pour les abonnements utilisateurs, on doit créer une entreprise virtuelle ou utiliser null
        // Mais la table factures nécessite une entreprise_id, donc on va créer une facture sans entreprise
        // en modifiant la contrainte ou en utilisant une entreprise système
        
        // Pour l'instant, on va utiliser l'entreprise du user s'il en a une, sinon null (nécessitera une migration)
        $entrepriseId = $user->entreprises()->first()?->id ?? null;
        
        $facture = self::create([
            'user_id' => $user->id,
            'entreprise_id' => $entrepriseId, // Utiliser la première entreprise de l'utilisateur si disponible
            'reservation_id' => null,
            'type_facture' => 'abonnement_manuel',
            'numero_facture' => self::generateNumeroFacture(),
            'date_facture' => $dateFacture,
            'date_echeance' => $dateFacture->copy()->addDays(30), // Échéance 30 jours
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => 'emise',
            'notes' => 'Facture d\'abonnement ' . ($user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'mensuel' : 'annuel') . ' - Période du ' . $periodeDebut->format('d/m/Y') . ' au ' . $periodeFin->format('d/m/Y'),
        ]);

        \Log::info('Facture d\'abonnement manuel générée', [
            'facture_id' => $facture->id,
            'user_id' => $user->id,
            'montant' => $montantTTC,
        ]);

        return $facture;
    }

    /**
     * Génère une facture pour un abonnement manuel entreprise
     */
    public static function generateFromManualEntrepriseSubscription(EntrepriseSubscription $subscription, \Carbon\Carbon $dateFacture = null): ?Facture
    {
        if (!$subscription->est_manuel || !$subscription->montant) {
            return null;
        }

        $dateFacture = $dateFacture ?? now();
        $entreprise = $subscription->entreprise;
        
        // Vérifier si une facture existe déjà pour cette période
        $periodeDebut = $dateFacture->copy()->startOfMonth();
        $periodeFin = $dateFacture->copy()->endOfMonth();
        
        if ($subscription->type_renouvellement === 'annuel') {
            $periodeDebut = $dateFacture->copy()->startOfYear();
            $periodeFin = $dateFacture->copy()->endOfYear();
        }

        $factureExistante = self::where('entreprise_subscription_id', $subscription->id)
            ->where('type_facture', 'abonnement_manuel')
            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
            ->first();

        if ($factureExistante) {
            return $factureExistante;
        }

        // Calculer les montants (TVA à 0% par défaut)
        $montantHT = $subscription->montant;
        $tauxTVA = 0;
        $montantTVA = 0;
        $montantTTC = $montantHT;

        // Créer la facture
        $facture = self::create([
            'user_id' => $entreprise->user_id,
            'entreprise_id' => $entreprise->id,
            'entreprise_subscription_id' => $subscription->id,
            'reservation_id' => null,
            'type_facture' => 'abonnement_manuel',
            'numero_facture' => self::generateNumeroFacture(),
            'date_facture' => $dateFacture,
            'date_echeance' => $dateFacture->copy()->addDays(30), // Échéance 30 jours
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => 'emise',
            'notes' => 'Facture d\'abonnement ' . ($subscription->type === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes') . ' (' . ($subscription->type_renouvellement === 'mensuel' ? 'mensuel' : 'annuel') . ') - Période du ' . $periodeDebut->format('d/m/Y') . ' au ' . $periodeFin->format('d/m/Y'),
        ]);

        \Log::info('Facture d\'abonnement manuel entreprise générée', [
            'facture_id' => $facture->id,
            'entreprise_id' => $entreprise->id,
            'subscription_id' => $subscription->id,
            'montant' => $montantTTC,
        ]);

        return $facture;
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
