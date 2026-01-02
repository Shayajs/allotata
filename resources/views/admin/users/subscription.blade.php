<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion abonnement - {{ $user->name }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 mb-4 inline-block">
                    ← Retour à l'utilisateur
                </a>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Gestion de l'abonnement manuel
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    {{ $user->name }} ({{ $user->email }})
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

            <!-- Statut actuel -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Statut actuel</h2>
                
                @php
                    $subscription = $user->subscription('default');
                    $hasActiveSubscription = $user->aAbonnementActif();
                @endphp

                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Abonnement Stripe</p>
                        @if($subscription && $subscription->valid())
                            <div class="space-y-2">
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    ✓ Actif
                                    @if($subscription->asStripeSubscription() && isset($subscription->asStripeSubscription()->current_period_end))
                                        (jusqu'au {{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }})
                                    @endif
                                </p>
                                @if(!$subscription->onGracePeriod())
                                    <form action="{{ route('admin.users.subscription.cancel-stripe', $user) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler l\'abonnement Stripe de cet utilisateur ? L\'abonnement restera actif jusqu\'à la fin de la période payée.');" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                            Annuler l'abonnement Stripe
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                        Annulé - Actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-slate-500 dark:text-slate-400">Aucun abonnement Stripe actif</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Abonnement manuel</p>
                        @if($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                            <p class="text-sm text-green-600 dark:text-green-400">
                                ✓ Actif jusqu'au {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}
                            </p>
                            @if($user->abonnement_manuel_type_renouvellement)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Renouvellement {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'mensuel' : 'annuel' }} le {{ $user->abonnement_manuel_jour_renouvellement }} de chaque {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'mois' : 'année' }}
                                </p>
                                @if($user->abonnement_manuel_montant)
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Montant : {{ number_format($user->abonnement_manuel_montant, 2, ',', ' ') }}€
                                    </p>
                                @endif
                            @endif
                            @if($user->abonnement_manuel_notes)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Note : {{ $user->abonnement_manuel_notes }}
                                </p>
                            @endif
                        @else
                            <p class="text-sm text-slate-500 dark:text-slate-400">Aucun abonnement manuel</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Statut global</p>
                        @if($hasActiveSubscription)
                            <p class="text-sm font-semibold text-green-600 dark:text-green-400">✓ Abonnement actif</p>
                        @else
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">✗ Aucun abonnement actif</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Gestion de l'abonnement manuel -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">
                    {{ $user->abonnement_manuel ? 'Modifier' : 'Activer' }} un abonnement manuel
                </h2>
                
                @php
                    $hasActiveStripeSubscription = $subscription && $subscription->valid() && !$subscription->onGracePeriod();
                @endphp

                @if($hasActiveStripeSubscription)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-400 mb-1">
                                    Abonnement Stripe actif
                                </h3>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    L'utilisateur a un abonnement Stripe actif. Vous ne pouvez pas activer ou modifier un abonnement manuel tant que l'abonnement Stripe est actif. Vous pouvez uniquement consulter les informations de l'abonnement Stripe.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                        Utilisez cette fonctionnalité pour gérer manuellement l'abonnement d'un utilisateur (paiement direct, ristourne, etc.).
                    </p>

                    @if($user->abonnement_manuel)
                        <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="mb-6" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver l\'abonnement manuel ?');">
                            @csrf
                            <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                Désactiver l'abonnement manuel
                            </button>
                        </form>
                    @endif
                @endif

                <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" @if($hasActiveStripeSubscription) onsubmit="event.preventDefault(); alert('Impossible d\'activer un abonnement manuel : l\'utilisateur a un abonnement Stripe actif.'); return false;" @endif>
                    @csrf
                    <input type="hidden" name="activer" value="1">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Type de renouvellement *
                                </label>
                                <select 
                                    name="type_renouvellement" 
                                    required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                    <option value="">Sélectionner...</option>
                                    <option value="mensuel" {{ $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                                    <option value="annuel" {{ $user->abonnement_manuel_type_renouvellement === 'annuel' ? 'selected' : '' }}>Annuel</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Jour de renouvellement *
                                </label>
                                <input 
                                    type="number" 
                                    name="jour_renouvellement" 
                                    required
                                    min="1"
                                    max="31"
                                    value="{{ $user->abonnement_manuel_jour_renouvellement ?? date('d') }}"
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Jour du mois où la facture sera générée (1-31).
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Date de début *
                                </label>
                                <input 
                                    type="date" 
                                    name="date_debut" 
                                    required
                                    value="{{ $user->abonnement_manuel_date_debut ? $user->abonnement_manuel_date_debut->format('Y-m-d') : date('Y-m-d') }}"
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Date de début de l'abonnement (première facture générée à cette date).
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Date de fin *
                                </label>
                                <input 
                                    type="date" 
                                    name="date_fin" 
                                    required
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    value="{{ $user->abonnement_manuel_actif_jusqu ? $user->abonnement_manuel_actif_jusqu->format('Y-m-d') : '' }}"
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    L'abonnement sera actif jusqu'à cette date incluse.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Montant de l'abonnement (€) *
                            </label>
                            <input 
                                type="number" 
                                name="montant" 
                                step="0.01"
                                min="0.01"
                                required
                                value="{{ $user->abonnement_manuel_montant ?? '' }}"
                                placeholder="15.00"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Montant qui sera facturé à chaque renouvellement.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea 
                                name="notes" 
                                rows="3"
                                placeholder="Ex: Ristourne de 50%, paiement direct, etc."
                                maxlength="500"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >{{ $user->abonnement_manuel_notes ?? '' }}</textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Notes internes pour référence (non visibles par l'utilisateur).
                            </p>
                        </div>

                        <div class="flex gap-4">
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all {{ $hasActiveStripeSubscription ? 'opacity-50 cursor-not-allowed' : '' }}"
                                @if($hasActiveStripeSubscription) disabled @endif
                            >
                                {{ $user->abonnement_manuel ? 'Modifier' : 'Activer' }} l'abonnement
                            </button>
                            <a href="{{ route('admin.users.show', $user) }}" class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                Annuler
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>

