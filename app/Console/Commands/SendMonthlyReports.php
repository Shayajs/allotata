<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Services\EmailReportService;
use Illuminate\Console\Command;

class SendMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer les rapports mensuels aux gérants d\'entreprises';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(EmailReportService::class);
        
        // Récupérer toutes les entreprises actives avec abonnement actif
        $entreprises = Entreprise::whereHas('user')
            ->get()
            ->filter(function($entreprise) {
                return $entreprise->aAbonnementActif();
            });

        $sentCount = 0;
        $errorCount = 0;

        foreach ($entreprises as $entreprise) {
            if ($service->sendMonthlyReport($entreprise)) {
                $sentCount++;
                $this->info("Rapport mensuel envoyé pour l'entreprise: {$entreprise->nom}");
            } else {
                $errorCount++;
                $this->error("Erreur lors de l'envoi du rapport pour l'entreprise: {$entreprise->nom}");
            }
        }

        $this->info("Rapports envoyés: {$sentCount}, Erreurs: {$errorCount}");
        
        return Command::SUCCESS;
    }
}
