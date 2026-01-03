<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Abonnement - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    💳 Gestion de l'abonnement
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez votre abonnement pour accéder à toutes les fonctionnalités de gestion d'entreprise.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Carte d'abonnement -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 mb-6">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                        Abonnement Premium
                    </h2>
                    <div class="flex items-baseline justify-center gap-2 mb-4">
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

                <!-- Statut de l'abonnement -->
                @php
                    $essaiPremium = $user->essaiActif('premium');
                    $peutEssayerPremium = $user->peutDemarrerEssai('premium');
                    
                    // Vérifier les sources d'abonnement séparément
                    $hasStripeSubscription = $subscription && $subscription->valid();
                    $hasManualSubscription = $user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu && ($user->abonnement_manuel_actif_jusqu->isFuture() || $user->abonnement_manuel_actif_jusqu->isToday());
                    $hasTrialSubscription = $essaiPremium && $essaiPremium->estEnCours();
                    
                    // Abonnement actif si l'une des sources est valide
                    $hasActiveSubscription = $hasStripeSubscription || $hasManualSubscription || $hasTrialSubscription;
                @endphp

                @if($hasActiveSubscription)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @if($hasTrialSubscription)
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                        🎁 Essai gratuit actif
                                    </h3>
                                    <span class="inline-block px-3 py-1 text-sm bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded-full mt-1">
                                        {{ $essaiPremium->joursRestants() }} jour(s) restant(s)
                                    </span>
                                </div>
                            @else
                                <h3 class="text-xl font-bold text-green-800 dark:text-green-400">
                                    Abonnement actif
                                </h3>
                            @endif
                        </div>
                        
                        @if($hasTrialSubscription)
                            <div class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/10 rounded-lg border border-orange-200 dark:border-orange-800">
                                <p class="text-sm text-orange-800 dark:text-orange-400 mb-3">
                                    <strong>Votre essai expire le {{ $essaiPremium->date_fin->format('d/m/Y à H:i') }}</strong><br>
                                    Abonnez-vous maintenant pour continuer à profiter de toutes les fonctionnalités !
                                </p>
                                @php
                                    $priceId = config('services.stripe.price_id');
                                @endphp
                                @if(!empty($priceId))
                                    <form action="{{ route('subscription.checkout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            @if($currentPriceAmount)
                                                S'abonner maintenant ({{ number_format($currentPriceAmount, 2, ',', ' ') }}€/mois)
                                            @else
                                                S'abonner maintenant
                                            @endif
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @elseif($hasStripeSubscription)
                            <div class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                <p><strong>Type :</strong> Abonnement Stripe</p>
                                <p><strong>Statut Stripe :</strong> {{ $subscription->stripe_status }}</p>
                                @if($subscription->onGracePeriod())
                                    <p><strong>Statut :</strong> <span class="text-yellow-600 dark:text-yellow-400">Annulé - Actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}</span></p>
                                    <form action="{{ route('subscription.resume') }}" method="POST" class="mt-4">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                            Reprendre l'abonnement
                                        </button>
                                    </form>
                                @else
                                    <p><strong>Statut :</strong> <span class="text-green-600 dark:text-green-400">Actif</span></p>
                                    @php
                                        $stripeSubData = null;
                                        try {
                                            $stripeSubData = $subscription->asStripeSubscription();
                                        } catch (\Exception $e) {
                                            // L'abonnement n'existe plus sur Stripe
                                        }
                                    @endphp
                                    @if($stripeSubData && isset($stripeSubData->current_period_end))
                                        <p><strong>Prochain paiement :</strong> {{ \Carbon\Carbon::createFromTimestamp($stripeSubData->current_period_end)->format('d/m/Y') }}</p>
                                    @endif
                                    <form action="{{ route('subscription.cancel') }}" method="POST" class="mt-4">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                            Gérer l'abonnement sur Stripe
                                        </button>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                            Vous serez redirigé vers le portail Stripe pour gérer votre abonnement (annulation, reprise, méthode de paiement, factures).
                                        </p>
                                    </form>
                                @endif
                            </div>
                        @elseif($hasManualSubscription)
                            <div class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                <p><strong>Type :</strong> Abonnement manuel (géré par l'administrateur)</p>
                                <p><strong>Actif jusqu'au :</strong> {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</p>
                                @if($user->abonnement_manuel_notes)
                                    <p><strong>Note :</strong> {{ $user->abonnement_manuel_notes }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
                        <p class="text-yellow-800 dark:text-yellow-400 mb-4">
                            Vous n'avez pas d'abonnement actif. Souscrivez maintenant pour accéder à toutes les fonctionnalités.
                        </p>
                        @php
                            $priceId = config('services.stripe.price_id');
                        @endphp
                        
                        <div class="space-y-4">
                            @if($peutEssayerPremium)
                                <form action="{{ route('essai-gratuit.utilisateur') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                                        <span class="text-lg">🎁</span>
                                        Essayer gratuitement pendant 7 jours
                                    </button>
                                </form>
                                <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                                    Sans engagement • Sans carte bancaire
                                </p>
                                <div class="relative flex items-center justify-center py-2">
                                    <span class="absolute inset-x-0 h-px bg-slate-300 dark:bg-slate-600"></span>
                                    <span class="relative px-4 bg-yellow-50 dark:bg-yellow-900/20 text-xs text-slate-500 dark:text-slate-400">ou</span>
                                </div>
                            @endif
                            
                            @if(empty($priceId))
                                <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <p class="text-red-800 dark:text-red-400 text-sm">
                                        ⚠️ <strong>Configuration incomplète :</strong> Le STRIPE_PRICE_ID n'est pas configuré. Veuillez contacter l'administrateur pour activer les abonnements Stripe.
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
                    </div>
                @endif

            </div>
        </div>
    </body>
</html>

