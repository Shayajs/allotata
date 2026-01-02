<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\StripeSubscriptionSyncService;
use Illuminate\Support\Facades\Log;

class SyncAllStripeSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-all-subscriptions {--user-id= : Synchroniser uniquement un utilisateur spécifique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise tous les abonnements Stripe avec la base de données locale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Début de la synchronisation des abonnements Stripe...');

        $userId = $this->option('user-id');

        if ($userId) {
            // Synchroniser un utilisateur spécifique
            $user = User::find($userId);
            if (!$user) {
                $this->error("Utilisateur #{$userId} non trouvé.");
                return 1;
            }

            $this->info("Synchronisation de l'utilisateur: {$user->name} ({$user->email})");
            $result = StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
            
            if ($result['user_subscriptions']['synced']) {
                $this->info("✓ Abonnements utilisateur synchronisés: " . count($result['user_subscriptions']['subscriptions']));
            } else {
                $this->warn("⚠ Impossible de synchroniser les abonnements utilisateur");
            }

            $entrepriseCount = 0;
            foreach ($result['entreprise_subscriptions'] as $entrepriseId => $entrepriseResult) {
                if ($entrepriseResult['synced']) {
                    $entrepriseCount += count($entrepriseResult['subscriptions']);
                }
            }
            $this->info("✓ Abonnements entreprises synchronisés: {$entrepriseCount}");

        } else {
            // Synchroniser tous les utilisateurs avec un stripe_id
            $users = User::whereNotNull('stripe_id')->get();
            $this->info("Trouvé {$users->count()} utilisateurs avec un stripe_id");

            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            $totalUserSubscriptions = 0;
            $totalEntrepriseSubscriptions = 0;
            $errors = 0;

            foreach ($users as $user) {
                try {
                    $result = StripeSubscriptionSyncService::syncAllUserSubscriptions($user);
                    
                    if ($result['user_subscriptions']['synced']) {
                        $totalUserSubscriptions += count($result['user_subscriptions']['subscriptions']);
                    }

                    foreach ($result['entreprise_subscriptions'] as $entrepriseId => $entrepriseResult) {
                        if ($entrepriseResult['synced']) {
                            $totalEntrepriseSubscriptions += count($entrepriseResult['subscriptions']);
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Erreur lors de la synchronisation de l'utilisateur #{$user->id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ Synchronisation terminée !");
            $this->info("  - Abonnements utilisateurs: {$totalUserSubscriptions}");
            $this->info("  - Abonnements entreprises: {$totalEntrepriseSubscriptions}");
            
            if ($errors > 0) {
                $this->warn("  - Erreurs: {$errors}");
            }
        }

        return 0;
    }
}
