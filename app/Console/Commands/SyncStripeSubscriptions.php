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
    protected $signature = 'stripe:sync-subscriptions 
        {--subscription-id= : ID Stripe spécifique à synchroniser}
        {--user= : ID de l\'utilisateur à synchroniser depuis Stripe}
        {--from-stripe : Synchroniser TOUS les abonnements depuis Stripe pour tous les utilisateurs}
        {--active-only : Synchroniser uniquement les abonnements actifs (par défaut: tous)}';

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
        $userId = $this->option('user');
        $fromStripe = $this->option('from-stripe');
        $activeOnly = $this->option('active-only');
        
        // Mode 1: Synchroniser TOUT depuis Stripe
        if ($fromStripe) {
            return $this->syncAllFromStripe();
        }
        
        // Mode 2: Synchroniser depuis Stripe pour un utilisateur spécifique
        if ($userId) {
            return $this->syncFromStripeForUser($userId);
        }
        
        // Mode 3: Synchroniser un abonnement spécifique
        if ($subscriptionId) {
            $subscriptions = Subscription::where('stripe_id', $subscriptionId)->get();
        } else {
            // Mode 4: Synchroniser tous les abonnements locaux (par défaut: tous les statuts)
            if ($activeOnly) {
                $subscriptions = Subscription::where('stripe_status', 'active')->get();
            } else {
                $subscriptions = Subscription::all();
            }
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

    /**
     * Synchroniser TOUS les abonnements depuis Stripe pour tous les utilisateurs.
     */
    private function syncAllFromStripe(): int
    {
        $this->info("🔄 Synchronisation complète depuis Stripe...\n");
        
        // Récupérer tous les utilisateurs avec un compte Stripe
        $users = \App\Models\User::whereNotNull('stripe_id')->get();
        
        $this->info("📊 {$users->count()} utilisateur(s) avec compte Stripe trouvé(s)\n");
        
        $totalSynced = 0;
        $totalErrors = 0;
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        foreach ($users as $user) {
            try {
                $this->syncUserSubscriptionsFromStripe($user);
                $totalSynced++;
            } catch (\Exception $e) {
                $totalErrors++;
                Log::error('Erreur sync Stripe pour utilisateur', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Synchronisation terminée: {$totalSynced} utilisateur(s) synchronisé(s), {$totalErrors} erreur(s)");
        
        return Command::SUCCESS;
    }

    /**
     * Synchronise les abonnements Stripe d'un utilisateur (version silencieuse pour batch).
     */
    private function syncUserSubscriptionsFromStripe(\App\Models\User $user): void
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        
        $stripeSubscriptions = \Stripe\Subscription::all([
            'customer' => $user->stripe_id,
            'status' => 'all',
            'limit' => 100,
        ]);
        
        foreach ($stripeSubscriptions->data as $stripeSub) {
            $this->syncSingleStripeSubscription($user, $stripeSub);
        }
        
        // Marquer les abonnements orphelins comme annulés
        $stripeIds = collect($stripeSubscriptions->data)->pluck('id')->toArray();
        Subscription::where('user_id', $user->id)
            ->whereNotIn('stripe_id', $stripeIds)
            ->whereNotNull('stripe_id')
            ->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
    }

    /**
     * Identifie le type d'abonnement par son price_id.
     * @return array{type: string, entreprise_id: int|null, entreprise_type: string|null}
     */
    private function getSubscriptionTypeByPriceId(string $priceId): array
    {
        // Prix Premium utilisateur (abonnement principal)
        $premiumPriceId = config('services.stripe.price_id');
        if ($priceId === $premiumPriceId) {
            return ['type' => 'default', 'entreprise_id' => null, 'entreprise_type' => null];
        }
        
        // Prix Site Web Vitrine
        $siteWebPriceId = config('services.stripe.price_id_site_web');
        if ($priceId === $siteWebPriceId) {
            return ['type' => 'entreprise_site_web', 'entreprise_id' => null, 'entreprise_type' => 'site_web'];
        }
        
        // Prix Multi-Personnes
        $multiPersonnesPriceId = config('services.stripe.price_id_multi_personnes');
        if ($priceId === $multiPersonnesPriceId) {
            return ['type' => 'entreprise_multi_personnes', 'entreprise_id' => null, 'entreprise_type' => 'multi_personnes'];
        }
        
        // Vérifier les prix personnalisés
        $customPrice = \App\Models\CustomPrice::where('stripe_price_id', $priceId)->first();
        if ($customPrice) {
            $entrepriseId = $customPrice->entreprise_id;
            $type = $customPrice->type; // 'site_web' ou 'multi_personnes'
            return [
                'type' => "entreprise_{$type}_{$entrepriseId}",
                'entreprise_id' => $entrepriseId,
                'entreprise_type' => $type,
            ];
        }
        
        // Type inconnu
        return ['type' => 'unknown', 'entreprise_id' => null, 'entreprise_type' => null];
    }

    /**
     * Synchroniser tous les abonnements depuis Stripe pour un utilisateur.
     * Cette méthode récupère les données directement depuis l'API Stripe.
     */
    private function syncFromStripeForUser(int $userId): int
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            $this->error("Utilisateur #{$userId} non trouvé.");
            return Command::FAILURE;
        }
        
        if (!$user->stripe_id) {
            $this->error("L'utilisateur n'a pas de compte Stripe.");
            return Command::FAILURE;
        }
        
        $this->info("🔄 Synchronisation depuis Stripe pour {$user->name} (ID: {$user->id})");
        $this->info("   Customer Stripe: {$user->stripe_id}");
        
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Récupérer TOUS les abonnements depuis Stripe (y compris canceled)
            $stripeSubscriptions = \Stripe\Subscription::all([
                'customer' => $user->stripe_id,
                'status' => 'all', // all = active, canceled, past_due, etc.
                'limit' => 100,
            ]);
            
            $this->info("   → {$stripeSubscriptions->count()} abonnement(s) trouvé(s) sur Stripe\n");
            
            foreach ($stripeSubscriptions->data as $stripeSub) {
                $this->syncSingleStripeSubscription($user, $stripeSub);
            }
            
            // Vérifier s'il y a des abonnements locaux qui n'existent plus sur Stripe
            $stripeIds = collect($stripeSubscriptions->data)->pluck('id')->toArray();
            $orphanedSubscriptions = Subscription::where('user_id', $user->id)
                ->whereNotIn('stripe_id', $stripeIds)
                ->get();
            
            foreach ($orphanedSubscriptions as $orphan) {
                $this->warn("   ⚠️ Abonnement local {$orphan->stripe_id} non trouvé sur Stripe - marquage comme annulé");
                $orphan->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => $orphan->ends_at ?? now(),
                ]);
            }
            
            $this->info("\n✅ Synchronisation terminée pour {$user->name}");
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            Log::error('Erreur sync Stripe pour utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Synchronise un seul abonnement depuis Stripe.
     */
    private function syncSingleStripeSubscription(\App\Models\User $user, \Stripe\Subscription $stripeSub): void
    {
        $stripeId = $stripeSub->id;
        $status = $stripeSub->status;
        $priceId = $stripeSub->items->data[0]->price->id ?? null;
        $description = $stripeSub->description ?? '';
        $metadata = $stripeSub->metadata->toArray() ?? [];
        $cancelAtPeriodEnd = $stripeSub->cancel_at_period_end;
        $currentPeriodEnd = $stripeSub->current_period_end;
        $cancelAt = $stripeSub->cancel_at;
        $endedAt = $stripeSub->ended_at;
        
        // Calculer ends_at
        $endsAt = null;
        if ($status === 'canceled') {
            // Si annulé, on doit avoir une date de fin. Si Stripe ne la donne pas (ended_at), on met maintenant.
            $endsAt = $endedAt ? \Carbon\Carbon::createFromTimestamp($endedAt) : now();
        } elseif ($cancelAtPeriodEnd && $currentPeriodEnd) {
            $endsAt = \Carbon\Carbon::createFromTimestamp($currentPeriodEnd);
        } elseif ($cancelAt) {
            $endsAt = \Carbon\Carbon::createFromTimestamp($cancelAt);
        }
        
        // Identifier le type d'abonnement
        $subscriptionType = 'default';
        $entrepriseId = null;
        $entrepriseType = null;
        
        if (isset($metadata['entreprise_id'])) {
            $entrepriseId = $metadata['entreprise_id'];
            $entrepriseType = $metadata['type'] ?? null;
            if ($entrepriseType) {
                $subscriptionType = "entreprise_{$entrepriseType}_{$entrepriseId}";
            }
        } elseif (preg_match('/\[ENTREPRISE_ID:(\d+)\]/', $description, $matches)) {
            // Méthode 2: Description (format: [ENTREPRISE_ID:123])
            $entrepriseId = $matches[1];
            if (str_contains(strtolower($description), 'site web')) {
                $entrepriseType = 'site_web';
            } elseif (str_contains(strtolower($description), 'multi')) {
                $entrepriseType = 'multi_personnes';
            }
            if ($entrepriseType) {
                $subscriptionType = "entreprise_{$entrepriseType}_{$entrepriseId}";
            }
        }
        
        // Méthode 3: Identification par price_id (fallback si type non identifié)
        if ($subscriptionType === 'default' && $priceId) {
            $priceInfo = $this->getSubscriptionTypeByPriceId($priceId);
            if ($priceInfo['type'] !== 'unknown' && $priceInfo['type'] !== 'default') {
                $subscriptionType = $priceInfo['type'];
                $entrepriseType = $priceInfo['entreprise_type'];
                // Note: entreprise_id n'est pas identifiable par price_id standard (site_web/multi_personnes)
                // seulement par prix personnalisé
                if ($priceInfo['entreprise_id']) {
                    $entrepriseId = $priceInfo['entreprise_id'];
                }
            }
        }
        
        $this->line("   📋 {$stripeId}");
        $this->line("      Statut: {$status}" . ($cancelAtPeriodEnd ? ' (annulation à la fin de la période)' : ''));
        
        // 1. Mettre à jour la table subscriptions (Cashier)
        $subscription = Subscription::where('stripe_id', $stripeId)->first();
        
        if ($subscription) {
            $oldStatus = $subscription->stripe_status;
            $subscription->update([
                'stripe_status' => $status,
                'stripe_price' => $priceId,
                'ends_at' => $endsAt,
            ]);
            
            if ($oldStatus !== $status) {
                $this->info("      ✏️ Cashier: {$oldStatus} → {$status}");
            }
        } else {
            $subscription = $user->subscriptions()->create([
                'type' => $subscriptionType,
                'stripe_id' => $stripeId,
                'stripe_status' => $status,
                'stripe_price' => $priceId,
                'quantity' => 1,
                'ends_at' => $endsAt,
            ]);
            $this->info("      ➕ Cashier: Abonnement créé");
        }
        
        // 2. Mettre à jour entreprise_subscriptions si c'est un abonnement entreprise
        if ($entrepriseId && $entrepriseType) {
            $entrepriseSubscription = EntrepriseSubscription::where('entreprise_id', $entrepriseId)
                ->where('type', $entrepriseType)
                ->first();
            
            if ($entrepriseSubscription) {
                $oldStatus = $entrepriseSubscription->stripe_status;
                $entrepriseSubscription->update([
                    'stripe_id' => $stripeId,
                    'stripe_status' => $status,
                    'stripe_price' => $priceId,
                    'ends_at' => $endsAt,
                ]);
                
                if ($oldStatus !== $status) {
                    $this->info("      ✏️ Entreprise: {$oldStatus} → {$status}");
                }
            } else {
                EntrepriseSubscription::create([
                    'entreprise_id' => $entrepriseId,
                    'name' => $subscriptionType,
                    'type' => $entrepriseType,
                    'stripe_id' => $stripeId,
                    'stripe_status' => $status,
                    'stripe_price' => $priceId,
                    'est_manuel' => false,
                    'ends_at' => $endsAt,
                ]);
                $this->info("      ➕ Entreprise: Abonnement créé");
            }
        }
    }
}
