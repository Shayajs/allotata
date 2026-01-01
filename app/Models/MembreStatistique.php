<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreStatistique extends Model
{
    use HasFactory;

    protected $table = 'membre_statistiques';

    protected $fillable = [
        'membre_id',
        'date',
        'nombre_reservations',
        'revenu_total',
        'duree_totale_minutes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'nombre_reservations' => 'integer',
            'revenu_total' => 'decimal:2',
            'duree_totale_minutes' => 'integer',
        ];
    }

    /**
     * Relation : Une statistique appartient à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    /**
     * Calculer ou mettre à jour les statistiques pour un membre à une date donnée
     */
    public static function calculerPourMembre(EntrepriseMembre $membre, \Carbon\Carbon $date): self
    {
        $debutJour = $date->copy()->startOfDay();
        $finJour = $date->copy()->endOfDay();

        // Compter les réservations confirmées ou terminées pour ce membre ce jour
        $reservations = \App\Models\Reservation::where('membre_id', $membre->id)
            ->whereBetween('date_reservation', [$debutJour, $finJour])
            ->whereIn('statut', ['confirmee', 'terminee'])
            ->get();

        $nombreReservations = $reservations->count();
        $revenuTotal = $reservations->sum('prix');
        $dureeTotale = $reservations->sum('duree_minutes');

        return self::updateOrCreate(
            [
                'membre_id' => $membre->id,
                'date' => $date->format('Y-m-d'),
            ],
            [
                'nombre_reservations' => $nombreReservations,
                'revenu_total' => $revenuTotal,
                'duree_totale_minutes' => $dureeTotale,
            ]
        );
    }

    /**
     * Calculer la charge de travail sur une période
     */
    public static function calculerChargeTravail(EntrepriseMembre $membre, \Carbon\Carbon $dateDebut, \Carbon\Carbon $dateFin): array
    {
        $stats = self::where('membre_id', $membre->id)
            ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
            ->get();

        return [
            'nombre_reservations' => $stats->sum('nombre_reservations'),
            'revenu_total' => $stats->sum('revenu_total'),
            'duree_totale_minutes' => $stats->sum('duree_totale_minutes'),
            'nombre_jours' => $stats->count(),
        ];
    }
}
