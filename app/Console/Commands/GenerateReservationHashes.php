<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateReservationHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:generate-hashes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les hashs manquants pour toutes les réservations existantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recherche des réservations sans hash...');

        try {
            $reservations = Reservation::whereNull('hash')->orWhere('hash', '')->get();

            if ($reservations->isEmpty()) {
                $this->info('Toutes les réservations ont déjà un hash.');
                return 0;
            }

            $this->info("Trouvé {$reservations->count()} réservation(s) sans hash.");

            $bar = $this->output->createProgressBar($reservations->count());
            $bar->start();

            $generated = 0;
            $errors = 0;

            foreach ($reservations as $reservation) {
                try {
                    // Générer un hash unique
                    do {
                        $hash = $reservation->generateHash();
                    } while (Reservation::where('hash', $hash)->where('id', '!=', $reservation->id)->exists());

                    $reservation->hash = $hash;
                    $reservation->save();

                    $generated++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\n✗ Erreur pour la réservation #{$reservation->id} : " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ {$generated} hash(s) généré(s) avec succès.");
            if ($errors > 0) {
                $this->warn("⚠ {$errors} erreur(s) rencontrée(s).");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
            return 1;
        }
    }
}