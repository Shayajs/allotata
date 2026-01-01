<?php

namespace App\Console\Commands;

use App\Models\EntrepriseMembre;
use App\Models\MembreStatistique;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CalculateMembreStatistiques extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membres:calculate-stats 
                            {--days=30 : Nombre de jours à calculer en arrière}
                            {--membre= : ID du membre spécifique à calculer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcule les statistiques des membres (réservations, revenus, charge de travail)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $membreId = $this->option('membre');

        $this->info("Calcul des statistiques pour les {$days} derniers jours...");

        // Récupérer les membres à traiter
        if ($membreId) {
            $membres = EntrepriseMembre::where('id', $membreId)
                ->where('est_actif', true)
                ->get();
        } else {
            $membres = EntrepriseMembre::where('est_actif', true)->get();
        }

        if ($membres->isEmpty()) {
            $this->warn('Aucun membre actif trouvé.');
            return 0;
        }

        $this->info("Traitement de {$membres->count()} membre(s)...");

        $dateDebut = Carbon::now()->subDays($days);
        $dateFin = Carbon::now();

        $bar = $this->output->createProgressBar($membres->count());
        $bar->start();

        $totalCalculated = 0;

        foreach ($membres as $membre) {
            // Calculer les stats pour chaque jour de la période
            $currentDate = $dateDebut->copy();
            
            while ($currentDate->lte($dateFin)) {
                MembreStatistique::calculerPourMembre($membre, $currentDate);
                $currentDate->addDay();
                $totalCalculated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Statistiques calculées pour {$totalCalculated} jour(s) sur {$membres->count()} membre(s).");

        return 0;
    }
}
