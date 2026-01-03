@extends('admin.layout')

@section('title', $user->name . ' - Administration')
@section('header', 'Fiche Utilisateur')
@section('subheader', 'Consultation et modification des informations de ' . $user->name)

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <x-avatar :user="$user" size="2xl" />
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ $user->name }}</h1>
            <p class="text-slate-600 dark:text-slate-400">{{ $user->email }}</p>
        </div>
    </div>
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
        ← Retour à la liste
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informations -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white font-medium">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Inscrit le</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white font-medium">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">ID Utilisateur</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white font-mono">{{ $user->id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Dernière modification</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white font-medium">{{ $user->updated_at->format('d/m/Y à H:i') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Entreprises -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 italic flex items-center gap-2">
                <span>🏢</span> Entreprises liées ({{ $user->entreprises->count() }})
            </h2>
            @if($user->entreprises->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($user->entreprises as $entreprise)
                        <div class="p-4 border border-slate-100 dark:border-slate-700 rounded-xl hover:border-green-300 dark:hover:border-green-800 transition-all bg-slate-50/50 dark:bg-slate-900/20 group">
                            <div class="flex items-center justify-between">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 dark:text-white truncate group-hover:text-green-600 transition-colors">{{ $entreprise->nom }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ $entreprise->type_activite }}</p>
                                </div>
                                <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-green-600 shadow-sm hover:scale-110 transition-transform">
                                    →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400 italic">Aucune entreprise associée.</p>
            @endif
        </div>

        <!-- Réservations -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 italic flex items-center gap-2">
                <span>📅</span> Réservations récentes ({{ $user->reservations->count() }})
            </h2>
            @if($user->reservations->count() > 0)
                <div class="space-y-3">
                    @foreach($user->reservations->sortByDesc('date_reservation')->take(5) as $reservation)
                        <div class="flex items-center justify-between p-3 border border-slate-100 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                    {{ $loop->iteration }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $reservation->entreprise->nom }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $reservation->date_reservation->format('d/m/Y à H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($reservation->prix, 2, ',', ' ') }} €</p>
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-xs text-green-600 hover:underline">Détails →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400 italic">Aucune réservation trouvée.</p>
            @endif
        </div>
    </div>

    <!-- Sidebar Actions -->
    <div class="space-y-6">
        @if($user->est_gerant)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>💳</span> Abonnement Premium
                </h2>
                <div class="space-y-4">
                    @php
                        $hasActiveSubscription = $user->aAbonnementActif();
                        $subscription = $user->subscription('default');
                    @endphp
                    
                    @if($hasActiveSubscription)
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl relative overflow-hidden">
                            <div class="absolute right-[-10px] top-[-10px] text-green-200 dark:text-green-800/20 text-5xl">✅</div>
                            <p class="text-sm font-bold text-green-800 dark:text-green-400 mb-2 relative">Statut : ACTIF</p>
                            @if($subscription && $subscription->valid() && !$subscription->onGracePeriod())
                                <p class="text-xs text-green-700 dark:text-green-500">
                                    Mode : Stripe
                                    @if($subscription->asStripeSubscription() && isset($subscription->asStripeSubscription()->current_period_end))
                                        <br>Prochaine facture : {{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('d/m/Y') }}
                                    @endif
                                </p>
                            @elseif($subscription && $subscription->onGracePeriod())
                                <p class="text-xs text-yellow-700 dark:text-yellow-500">
                                    Mode : Annulé (finit le {{ $subscription->ends_at->format('d/m/Y') }})
                                </p>
                            @elseif($user->abonnement_manuel)
                                <p class="text-xs text-green-700 dark:text-green-500 font-medium">
                                    Mode : Manuel<br>Fin : {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}
                                </p>
                                @if($user->abonnement_manuel_notes)
                                    <p class="text-[10px] text-slate-500 mt-2 bg-white/50 p-1 rounded">{{ $user->abonnement_manuel_notes }}</p>
                                @endif
                            @endif
                        </div>
                    @else
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-center">
                            <p class="text-sm font-bold text-red-800 dark:text-red-400">Aucun abonnement</p>
                        </div>
                    @endif
                    
                    <a href="{{ route('admin.users.subscription.show', $user) }}" class="block w-full px-4 py-3 text-center bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-xl hover:scale-[1.02] transition-transform shadow-lg">
                        Gérer l'abonnement
                    </a>
                </div>
            </div>
        @endif
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Modifier les accès</h2>
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                <div class="space-y-3">
                    <label class="flex items-center p-3 border border-slate-100 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <input type="checkbox" name="est_client" value="1" {{ $user->est_client ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Client</span>
                            <span class="text-[10px] text-slate-500">Peut réserver des services</span>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-slate-100 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <input type="checkbox" name="est_gerant" value="1" {{ $user->est_gerant ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Gérant</span>
                            <span class="text-[10px] text-slate-500">Peut créer et gérer une entreprise</span>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-slate-100 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <div class="ml-3 text-red-600 dark:text-red-400">
                            <span class="block text-sm font-bold">Administrateur</span>
                            <span class="text-[10px]">Accès complet au panel admin</span>
                        </div>
                    </label>
                    
                    <button type="submit" class="w-full mt-4 px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-xl shadow-md transition-all">
                        ⚙️ Mettre à jour les rôles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

