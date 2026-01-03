<h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">💳 Gestion de l'abonnement</h2>
                            
@php
    $hasActiveSubscription = $user->aAbonnementActif();
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <div class="text-center mb-6">
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
            Abonnement Premium
        </h3>
        <div class="flex items-baseline justify-center gap-2 mb-4">
            @php
                // Récupérer le prix actuel depuis Stripe
                $currentPriceAmount = null;
                try {
                    $customPrice = \App\Models\CustomPrice::getForUser($user, 'default');
                    $priceId = $customPrice ? $customPrice->stripe_price_id : config('services.stripe.price_id');
                    
                    if ($priceId) {
                        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                        $price = \Stripe\Price::retrieve($priceId);
                        $currentPriceAmount = $price->unit_amount / 100;
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, on n'affiche rien
                }
            @endphp
            @if($currentPriceAmount)
                <span class="text-5xl font-bold text-green-600 dark:text-green-400">{{ number_format($currentPriceAmount, 2, ',', ' ') }}€</span>
            @else
                <span class="text-5xl font-bold text-green-600 dark:text-green-400">-</span>
            @endif
            <span class="text-xl text-slate-600 dark:text-slate-400">/mois</span>
        </div>
        <p class="text-slate-600 dark:text-slate-400">
            Accès complet à toutes les fonctionnalités • Sans engagement • Annulation à tout moment
        </p>
    </div>

    @if($hasActiveSubscription)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-bold text-green-800 dark:text-green-400">
                    Abonnement actif
                </h3>
            </div>
            
            @if($subscription && $subscription->valid())
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                            <p class="font-semibold text-slate-900 dark:text-white">Abonnement Stripe</p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Statut</p>
                            @if($subscription->onGracePeriod())
                                <p class="font-semibold text-yellow-600 dark:text-yellow-400">Annulé - Actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}</p>
                            @else
                                <p class="font-semibold text-green-600 dark:text-green-400">Actif</p>
                            @endif
                        </div>
                        @php
                            $stripeSubscription = null;
                            try {
                                $stripeSubscription = $subscription->asStripeSubscription();
                            } catch (\Exception $e) {
                                // Ignorer l'erreur si l'abonnement n'existe plus chez Stripe
                            }
                        @endphp
                        @if($stripeSubscription)
                            @if(isset($stripeSubscription->current_period_end))
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400 mb-1">Prochain paiement</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                    </p>
                                </div>
                            @endif
                            @if(isset($stripeSubscription->current_period_start) && isset($stripeSubscription->current_period_end))
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400 mb-1">Période actuelle</p>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        Du {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)->format('d/m/Y') }}
                                        au {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>
                    
                    <div class="flex gap-3 mt-4">
                        @if($subscription->onGracePeriod())
                            <form action="{{ route('subscription.resume') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                    Reprendre l'abonnement
                                </button>
                            </form>
                        @else
                            <form action="{{ route('subscription.cancel') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                    Gérer l'abonnement sur Stripe
                                </button>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                    Vous serez redirigé vers le portail Stripe pour gérer votre abonnement.
                                </p>
                            </form>
                        @endif
                    </div>
                </div>
            @elseif($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                            <p class="font-semibold text-slate-900 dark:text-white">Abonnement manuel (géré par l'administrateur)</p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 mb-1">Actif jusqu'au</p>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</p>
                        </div>
                        @if($user->abonnement_manuel_notes)
                            <div class="md:col-span-2">
                                <p class="text-slate-600 dark:text-slate-400 mb-1">Note</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_notes }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-400">
                            ℹ️ Vous avez un abonnement manuel actif. Vous ne pouvez pas souscrire à un abonnement Stripe tant que l'abonnement manuel est actif.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Historique des factures (uniquement pour Stripe) -->
        @if($subscription && $subscription->valid() && isset($invoices) && $invoices->count() > 0)
            <div class="mt-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">📄 Historique des factures</h3>
                <div class="space-y-3">
                    @foreach($invoices->take(10) as $invoice)
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    @if(isset($invoice->created))
                                        Facture du {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('d/m/Y') }}
                                    @else
                                        Facture
                                    @endif
                                </p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ number_format($invoice->amount_paid / 100, 2, ',', ' ') }} €
                                    @if($invoice->status === 'paid')
                                        <span class="ml-2 text-green-600 dark:text-green-400">✓ Payée</span>
                                    @elseif($invoice->status === 'open')
                                        <span class="ml-2 text-yellow-600 dark:text-yellow-400">En attente</span>
                                    @else
                                        <span class="ml-2 text-red-600 dark:text-red-400">Impayée</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('subscription.invoice.download', $invoice->id) }}" 
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                    📥 Télécharger
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
            <div class="mb-4">
                <p class="text-yellow-800 dark:text-yellow-400 font-semibold mb-2">
                    ⚠️ Vous n'avez pas d'abonnement actif
                </p>
                <p class="text-sm text-yellow-700 dark:text-yellow-500">
                    Sans abonnement actif, vos entreprises ne seront pas visibles en ligne. Souscrivez maintenant pour accéder à toutes les fonctionnalités.
                </p>
            </div>
            @php
                $priceId = config('services.stripe.price_id');
            @endphp
            @if(empty($priceId))
                <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400 text-sm">
                        ⚠️ <strong>Configuration incomplète :</strong> Le STRIPE_PRICE_ID n'est pas configuré. Veuillez contacter l'administrateur.
                    </p>
                </div>
            @else
                <form action="{{ route('subscription.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        @if($currentPriceAmount)
                            Souscrire à l'abonnement ({{ number_format($currentPriceAmount, 2, ',', ' ') }}€/mois)
                        @else
                            Souscrire à l'abonnement
                        @endif
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

<!-- Abonnements des entreprises -->
@if($entreprises->count() > 0)
    <div class="mt-8 border-t border-slate-200 dark:border-slate-700 pt-8">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">📦 Abonnements de vos entreprises</h3>
        
        <div class="space-y-4">
            @foreach($entreprises as $entreprise)
                @php
                    $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
                    $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
                    $aSiteWebActif = $entreprise->aSiteWebActif();
                    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                @endphp
                
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $entreprise->nom }}</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                        </div>
                        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'abonnements']) }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                            Voir détails
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Site Web Vitrine -->
                        <div class="p-4 border border-slate-200 dark:border-slate-600 rounded-lg {{ $aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-semibold text-slate-900 dark:text-white">🌐 Site Web Vitrine</h5>
                                @if($aSiteWebActif)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Actif</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">2€/mois</p>
                            
                            @if($aSiteWebActif)
                                @if($abonnementSiteWeb && !$abonnementSiteWeb->est_manuel)
                                    <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'site_web']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                            Gérer sur Stripe
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Abonnement manuel</p>
                                @endif
                            @else
                                <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="site_web">
                                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                        S'abonner
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Gestion Multi-Personnes -->
                        <div class="p-4 border border-slate-200 dark:border-slate-600 rounded-lg {{ $aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-semibold text-slate-900 dark:text-white">👥 Gestion Multi-Personnes</h5>
                                @if($aGestionMultiPersonnes)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Actif</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">20€/mois</p>
                            
                            @if($aGestionMultiPersonnes)
                                @if($abonnementMultiPersonnes && !$abonnementMultiPersonnes->est_manuel)
                                    <form action="{{ route('entreprise.subscriptions.cancel', [$entreprise->slug, 'multi_personnes']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                            Gérer sur Stripe
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Abonnement manuel</p>
                                @endif
                            @else
                                <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="multi_personnes">
                                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                        S'abonner
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
