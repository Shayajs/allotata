<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Facture;
use Illuminate\Console\Command;

class GenerateMissingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factures:generate-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les factures manquantes pour les réservations payées qui n\'ont pas encore de facture';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recherche des réservations payées sans facture...');

        try {
            // Trouver toutes les réservations payées
            $reservationsPayees = Reservation::where('est_paye', true)
                ->with(['entreprise', 'user'])
                ->get();

            if ($reservationsPayees->isEmpty()) {
                $this->info('Aucune réservation payée trouvée.');
                return 0;
            }

            // Filtrer celles qui n'ont pas de facture
            $reservations = $reservationsPayees->filter(function($reservation) {
                // Vérifier directement si une facture existe pour cette réservation
                return !Facture::where('reservation_id', $reservation->id)->exists();
            });

            if ($reservations->isEmpty()) {
                $this->info('Toutes les réservations payées ont déjà une facture.');
                return 0;
            }

            $this->info("Trouvé {$reservations->count()} réservation(s) payée(s) sans facture.");

            $bar = $this->output->createProgressBar($reservations->count());
            $bar->start();

            $generated = 0;
            $errors = 0;

            foreach ($reservations as $reservation) {
                try {
                    // Vérifier à nouveau avant de générer (au cas où une facture aurait été créée entre temps)
                    $factureExistante = Facture::where('reservation_id', $reservation->id)->first();
                    if ($factureExistante) {
                        $bar->advance();
                        continue;
                    }

                    $facture = Facture::generateFromReservation($reservation);
                    if ($facture) {
                        $generated++;
                        $this->line("\n✓ Facture générée pour la réservation #{$reservation->id} : {$facture->numero_facture}");
                    } else {
                        $errors++;
                        $this->line("\n✗ Impossible de générer la facture pour la réservation #{$reservation->id}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\n✗ Erreur pour la réservation #{$reservation->id} : " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ {$generated} facture(s) générée(s) avec succès.");
            if ($errors > 0) {
                $this->warn("⚠ {$errors} erreur(s) rencontrée(s).");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur de connexion à la base de données : " . $e->getMessage());
            $this->warn("Vérifiez votre configuration de base de données dans le fichier .env");
            return 1;
        }
    }
}
