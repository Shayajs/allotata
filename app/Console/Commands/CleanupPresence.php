<?php

namespace App\Console\Commands;

use App\Services\PresenceService;
use Illuminate\Console\Command;

class CleanupPresence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presence:cleanup {--minutes=10 : Nombre de minutes avant de considérer une présence comme obsolète}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les entrées de présence obsolètes (utilisateurs déconnectés)';

    /**
     * Execute the console command.
     */
    public function handle(PresenceService $presenceService)
    {
        $minutes = (int) $this->option('minutes');
        
        $this->info("Nettoyage des présences obsolètes (plus de {$minutes} minutes)...");
        
        $deleted = $presenceService->cleanup($minutes);
        
        $this->info("✓ {$deleted} entrée(s) de présence supprimée(s).");
        
        return Command::SUCCESS;
    }
}
