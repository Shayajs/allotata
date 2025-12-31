<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $user->name }} - Admin</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ $user->name }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $user->email }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Informations -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Informations</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Email</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Inscrit le</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Entreprises -->
                    @if($user->entreprises->count() > 0)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Entreprises ({{ $user->entreprises->count() }})</h2>
                            <div class="space-y-3">
                                @foreach($user->entreprises as $entreprise)
                                    <div class="p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">{{ $entreprise->nom }}</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                                            </div>
                                            <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                Voir →
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Réservations -->
                    @if($user->reservations->count() > 0)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Réservations ({{ $user->reservations->count() }})</h2>
                            <div class="space-y-3">
                                @foreach($user->reservations->take(5) as $reservation)
                                    <div class="p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">{{ $reservation->entreprise->nom }}</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    {{ $reservation->date_reservation->format('d/m/Y à H:i') }} - {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                Voir →
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="space-y-6">
                    @if($user->est_gerant)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">💳 Abonnement</h2>
                            <div class="space-y-3">
                                @php
                                    $hasActiveSubscription = $user->aAbonnementActif();
                                    $subscription = $user->subscription('default');
                                @endphp
                                
                                @if($hasActiveSubscription)
                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <p class="text-sm font-medium text-green-800 dark:text-green-400 mb-1">Abonnement actif</p>
                                        @if($subscription && $subscription->valid() && !$subscription->onGracePeriod())
                                            <p class="text-xs text-green-700 dark:text-green-500">
                                                Stripe (actif)
                                                @if($subscription->asStripeSubscription())
                                                    - Jusqu'au {{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }}
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                ⚠️ L'abonnement manuel est désactivé automatiquement
                                            </p>
                                        @elseif($subscription && $subscription->onGracePeriod())
                                            <p class="text-xs text-yellow-700 dark:text-yellow-500">
                                                Stripe (annulé - actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }})
                                            </p>
                                        @elseif($user->abonnement_manuel)
                                            <p class="text-xs text-green-700 dark:text-green-500">
                                                Manuel jusqu'au {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                        <p class="text-sm font-medium text-red-800 dark:text-red-400">Aucun abonnement actif</p>
                                    </div>
                                @endif
                                
                                <a href="{{ route('admin.users.subscription.show', $user) }}" class="block w-full px-4 py-2 text-center bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Gérer l'abonnement
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Modifier les rôles</h2>
                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                            @csrf
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="est_client" value="1" {{ $user->est_client ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Client</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="est_gerant" value="1" {{ $user->est_gerant ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Gérant</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Administrateur</span>
                                </label>
                                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

