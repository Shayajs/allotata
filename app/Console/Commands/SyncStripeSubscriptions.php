<?php

namespace App\Console\Commands;

use App\Models\EntrepriseSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

class SyncStripeSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-subscriptions {--subscription-id= : ID Stripe spécifique à synchroniser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les abonnements depuis Stripe (mise à jour de ends_at, status, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptionId = $this->option('subscription-id');
        
        if ($subscriptionId) {
            $subscriptions = Subscription::where('stripe_id', $subscriptionId)->get();
        } else {
            $subscriptions = Subscription::where('stripe_status', 'active')->get();
        }
        
        $this->info("Synchronisation de {$subscriptions->count()} abonnement(s)...\n");
        
        $updated = 0;
        $errors = 0;
        
        foreach ($subscriptions as $subscription) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();
                
                $needsUpdate = false;
                $updates = [];
                
                // Vérifier cancel_at_period_end
                if ($stripeSubscription->cancel_at_period_end) {
                    $expectedEndsAt = null;
                    
                    if (isset($stripeSubscription->cancel_at)) {
                        $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at);
                    } elseif (isset($stripeSubscription->current_period_end)) {
                        $expectedEndsAt = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
                    }
                    
                    if ($expectedEndsAt) {
                        if (!$subscription->ends_at || !$subscription->ends_at->equalTo($expectedEndsAt)) {
                            $updates['ends_at'] = $expectedEndsAt;
                            $needsUpdate = true;
                        }
                    }
                } else {
                    // Si cancel_at_period_end est false, supprimer ends_at
                    if ($subscription->ends_at) {
                        $updates['ends_at'] = null;
                        $needsUpdate = true;
                    }
                }
                
                // Vérifier le statut
                if ($subscription->stripe_status !== $stripeSubscription->status) {
                    $updates['stripe_status'] = $stripeSubscription->status;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    $subscription->update($updates);
                    $updated++;
                    
                    $this->line("✅ {$subscription->stripe_id} mis à jour");
                    foreach ($updates as $key => $value) {
                        if ($value instanceof \Carbon\Carbon) {
                            $this->line("   {$key}: {$value->format('Y-m-d H:i:s')}");
                        } else {
                            $this->line("   {$key}: " . ($value ?? 'NULL'));
                        }
                    }
                    
                    // Mettre à jour aussi dans entreprise_subscriptions si c'est un abonnement d'entreprise
                    $entrepriseSubscription = EntrepriseSubscription::where('stripe_id', $subscription->stripe_id)->first();
                    
                    // Si pas trouvé par stripe_id, chercher par le type de l'abonnement
                    if (!$entrepriseSubscription && (str_starts_with($subscription->type ?? '', 'entreprise_') || str_starts_with($subscription->name ?? '', 'entreprise_'))) {
                        $name = $subscription->type ?? $subscription->name ?? '';
                        if (preg_match('/entreprise_(\w+)_(\d+)/', $name, $matches)) {
                            $type = $matches[1];
                            $entrepriseId = $matches[2];
                            
                            $entrepriseSubscription = EntrepriseSubscription::where('entreprise_id', $entrepriseId)
                                ->where('type', $type)
                                ->first();
                            
                            // Si trouvé, mettre à jour le stripe_id
                            if ($entrepriseSubscription && !$entrepriseSubscription->stripe_id) {
                                $entrepriseSubscription->stripe_id = $subscription->stripe_id;
                            }
                        }
                    }
                    
                    if ($entrepriseSubscription) {
                        $entrepriseSubscription->update([
                            'stripe_id' => $subscription->stripe_id, // S'assurer que le stripe_id est bien défini
                            'stripe_status' => $subscription->stripe_status,
                            'stripe_price' => $subscription->stripe_price,
                            'ends_at' => $subscription->ends_at,
                            'trial_ends_at' => $subscription->trial_ends_at,
                        ]);
                        $this->line("   → Abonnement d'entreprise également mis à jour (Entreprise {$entrepriseSubscription->entreprise_id}, Type: {$entrepriseSubscription->type})");
                    }
                } else {
                    $this->line("✓ {$subscription->stripe_id} déjà à jour");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Erreur pour {$subscription->stripe_id}: {$e->getMessage()}");
                Log::error('Erreur lors de la synchronisation de l\'abonnement', [
                    'subscription_id' => $subscription->stripe_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->info("\nSynchronisation terminée: {$updated} mis à jour, {$errors} erreur(s)");
        
        return Command::SUCCESS;
    }
}
