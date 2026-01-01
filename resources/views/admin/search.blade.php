@extends('admin.layout')

@section('title', 'Recherche')
@section('header', '🔍 Recherche globale')
@section('subheader', $query ? "Résultats pour \"$query\"" : 'Recherchez dans toutes les données')

@section('content')
@if(!$query)
    <div class="text-center py-12">
        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="text-5xl">🔍</span>
        </div>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Recherche globale</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6">Utilisez la barre de recherche en haut de page pour trouver des utilisateurs, entreprises, réservations ou tickets.</p>
    </div>
@else
    <!-- Résumé -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $counts['total'] }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $counts['users'] }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">Utilisateurs</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $counts['entreprises'] }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">Entreprises</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-3xl font-bold text-purple-600">{{ $counts['reservations'] }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">Réservations</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-3xl font-bold text-orange-600">{{ $counts['tickets'] }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">Tickets</p>
        </div>
    </div>

    @if($counts['total'] === 0)
        <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <span class="text-5xl mb-4 block">😕</span>
            <p class="text-slate-600 dark:text-slate-400">Aucun résultat trouvé pour "{{ $query }}"</p>
        </div>
    @else
        <!-- Utilisateurs -->
        @if($results['users']->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>👥</span> Utilisateurs ({{ $results['users']->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($results['users'] as $user)
                        <a href="{{ route('admin.users.show', $user) }}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $user->email }}</p>
                                </div>
                            </div>
                            <span class="text-green-600 hover:text-green-700">Voir →</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Entreprises -->
        @if($results['entreprises']->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏢</span> Entreprises ({{ $results['entreprises']->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($results['entreprises'] as $entreprise)
                        <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $entreprise->nom }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->ville ?? '-' }} • {{ $entreprise->user?->name ?? '-' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($entreprise->est_verifiee)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Vérifiée</span>
                                @endif
                                <span class="text-green-600 hover:text-green-700">Voir →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Réservations -->
        @if($results['reservations']->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📅</span> Réservations ({{ $results['reservations']->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($results['reservations'] as $reservation)
                        <a href="{{ route('admin.reservations.show', $reservation) }}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $reservation->type_service ?? 'Réservation' }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $reservation->user?->name ?? 'Client' }} • {{ $reservation->entreprise?->nom ?? '-' }} • {{ $reservation->date_reservation?->format('d/m/Y') }}
                                </p>
                            </div>
                            <span class="text-green-600 hover:text-green-700">Voir →</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Tickets -->
        @if($results['tickets']->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎫</span> Tickets ({{ $results['tickets']->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($results['tickets'] as $ticket)
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $ticket->numero_ticket }} - {{ Str::limit($ticket->sujet, 50) }}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $ticket->user?->name ?? '-' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-xs rounded {{ $ticket->statut === 'ouvert' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400' }}">
                                    {{ ucfirst($ticket->statut) }}
                                </span>
                                <span class="text-green-600 hover:text-green-700">Voir →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
@endif
@endsection
