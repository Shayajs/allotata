<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;

class RenewGoogleWatches extends Command
{
    protected $signature = 'google-calendar:renew-watches';

    protected $description = 'Renouvelle les webhooks Google Calendar qui expirent bientot';

    public function handle(GoogleCalendarService $service): int
    {
        // Trouver les entreprises dont le watch expire dans les 2 prochains jours,
        // ou qui ont un refresh_token mais pas de watch actif
        $entreprises = Entreprise::whereNotNull('google_refresh_token')
            ->where(function ($query) {
                $query->whereNull('google_watch_channel_id')
                    ->orWhere('google_watch_expiration', '<', now()->addDays(2));
            })
            ->get();

        $this->info("Entreprises a renouveler : {$entreprises->count()}");

        $renewed = 0;
        $errors = 0;

        foreach ($entreprises as $entreprise) {
            try {
                $service->setupWatch($entreprise);
                $renewed++;
                $this->line("  [OK] Entreprise #{$entreprise->id} ({$entreprise->nom})");
            } catch (\Exception $e) {
                $errors++;
                $this->error("  [ERREUR] Entreprise #{$entreprise->id} : {$e->getMessage()}");
            }
        }

        $this->info("Termine : {$renewed} renouveles, {$errors} erreurs.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
