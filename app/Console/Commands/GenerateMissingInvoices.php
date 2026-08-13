<?php

namespace App\Console\Commands;

use App\Models\Facture;
use App\Models\Reservation;
use Illuminate\Console\Command;

class GenerateMissingInvoices extends Command
{
    protected $signature = 'factures:generate-missing';

    protected $description = 'Émet les factures manquantes pour les prestations terminées';

    public function handle()
    {
        $this->info('Recherche des réservations terminées sans facture...');

        try {
            $reservations = Reservation::where('statut', 'terminee')
                ->whereDoesntHave('facture')
                ->with(['entreprise', 'user'])
                ->get()
                ->filter(fn (Reservation $reservation) => ! $reservation->aDejaFacture());

            if ($reservations->isEmpty()) {
                $this->info('Toutes les prestations terminées ont déjà une facture.');

                return 0;
            }

            $this->info("Trouvé {$reservations->count()} réservation(s) terminée(s) sans facture.");

            $generated = 0;
            $errors = 0;

            foreach ($reservations as $reservation) {
                try {
                    $facture = Facture::generateFromReservation($reservation);
                    if ($facture) {
                        $generated++;
                        $this->line("✓ Facture {$facture->numero_facture} pour la réservation #{$reservation->id}");
                    } else {
                        $errors++;
                        $this->line("✗ Impossible d'émettre la facture pour la réservation #{$reservation->id}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Réservation #{$reservation->id} : ".$e->getMessage());
                }
            }

            $this->info("{$generated} facture(s) émise(s).");
            if ($errors > 0) {
                $this->warn("{$errors} erreur(s).");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur : '.$e->getMessage());

            return 1;
        }
    }
}
