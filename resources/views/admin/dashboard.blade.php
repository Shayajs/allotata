@extends('admin.layout')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('subheader', 'Vue d\'ensemble de la plateforme')

@section('content')
<!-- Alertes prioritaires -->
@if($alertes['entreprises_en_attente'] > 0 || $alertes['tickets_urgents'] > 0 || $alertes['contacts_non_lus'] > 0)
<div class="mb-6 space-y-3">
    @if($alertes['entreprises_en_attente'] > 0)
        <a href="{{ route('admin.entreprises.index') }}?statut=en_attente" class="block p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <div>
                    <p class="font-semibold text-orange-800 dark:text-orange-300">{{ $alertes['entreprises_en_attente'] }} entreprise(s) en attente de validation</p>
                    <p class="text-sm text-orange-600 dark:text-orange-400">Cliquez pour valider les entreprises</p>
                </div>
            </div>
        </a>
    @endif
    @if($alertes['tickets_urgents'] > 0)
        <a href="{{ route('admin.tickets.index') }}?priorite=urgente&statut=ouvert" class="block p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-300">{{ $alertes['tickets_urgents'] }} ticket(s) urgent(s) non traité(s)</p>
                    <p class="text-sm text-red-600 dark:text-red-400">Nécessite une attention immédiate</p>
                </div>
            </div>
        </a>
    @endif
    @if($alertes['contacts_non_lus'] > 0)
        <a href="{{ route('admin.contacts.index') }}?est_lu=0" class="block p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-blue-800 dark:text-blue-300">{{ $alertes['contacts_non_lus'] }} message(s) de contact non lu(s)</p>
                    <p class="text-sm text-blue-600 dark:text-blue-400">Cliquez pour consulter les messages</p>
                </div>
            </div>
        </a>
    @endif
</div>
@endif

<!-- Statistiques principales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Utilisateurs</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_users']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex gap-2 text-xs">
            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">{{ $stats['total_clients'] }} clients</span>
            <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">{{ $stats['total_gerants'] }} gérants</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Entreprises</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_entreprises']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 flex-shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex gap-2 text-xs">
            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">{{ $stats['entreprises_verifiees'] }} vérifiées</span>
            <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">{{ $stats['entreprises_en_attente'] }} en attente</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_reservations']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 flex-shrink-0 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $stats['reservations_payees'] }} payées ({{ $stats['total_reservations'] > 0 ? round(($stats['reservations_payees'] / $stats['total_reservations']) * 100) : 0 }}%)</span>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-32 h-32 bg-gradient-to-br from-green-500/10 to-emerald-600/10 rounded-bl-full -mr-8 -mt-8 pointer-events-none transition-transform group-hover:scale-110"></div>
        
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">MRR (Revenu Récurrent)</p>
                <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600">
                    {{ number_format($stats['mrr'], 0, ',', ' ') }} €
                </p>
            </div>
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg shadow-green-500/30 flex items-center justify-center text-white transform group-hover:rotate-12 transition-transform">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2 relative z-10">
             <div class="flex justify-between items-end text-xs">
                <span class="text-slate-600 dark:text-slate-400 font-medium">{{ $stats['abonnements_actifs'] }} abonnés actifs</span>
                <span class="text-green-600 dark:text-green-400 font-bold bg-green-50 dark:bg-green-900/40 px-2 py-0.5 rounded-full border border-green-200 dark:border-green-800">
                    {{ number_format($stats['total_entreprises'] > 0 ? ($stats['abonnements_actifs'] / $stats['total_entreprises']) * 100 : 0, 1) }}% conv.
                </span>
             </div>
             <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-1.5 rounded-full shadow-[0_0_10px_rgba(34,197,94,0.5)]" style="width: {{ $stats['total_entreprises'] > 0 ? ($stats['abonnements_actifs'] / $stats['total_entreprises']) * 100 : 0 }}%"></div>
             </div>
             <p class="text-[10px] text-slate-400 leading-tight">
                {{ $stats['abonnements_stripe'] }} Stripe • {{ $stats['abonnements_manuels'] }} Manuels
             </p>
        </div>
    </div>
</div>

<!-- Activité du jour (site vivant ?) -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Connexions aujourd'hui</p>
        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($stats['today_connexions'] ?? 0) }}</p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Membres uniques (max 1 / jour)</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Visiteurs membres</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['today_visitors_members'] ?? 0) }}</p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Sessions connectées</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Visiteurs invités</p>
        <p class="text-2xl font-bold text-sky-600 dark:text-sky-400">{{ number_format($stats['today_visitors_guests'] ?? 0) }}</p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Non connectés</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Bots & robots</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['today_visitors_bots'] ?? 0) }}</p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ number_format($stats['today_page_views'] ?? 0) }} pages vues au total</p>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Inscriptions par jour -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            Nouvelles inscriptions (30 derniers jours)
        </h2>
        <div class="relative h-[250px]">
            <canvas id="inscriptionsChart"></canvas>
        </div>
    </div>

    <!-- Réservations par jour -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Réservations (30 derniers jours)
        </h2>
        <div class="relative h-[250px]">
            <canvas id="reservationsChart"></canvas>
        </div>
    </div>
</div>

<!-- Connexions & trafic site -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            Connexions (30 derniers jours)
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Membres distincts ayant ouvert une session — 1 seul comptage par jour, même après 40 reconnexions</p>
        <div class="relative h-[250px]">
            <canvas id="connexionsChart"></canvas>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Trafic site (30 derniers jours)
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Visiteurs uniques par session / jour — membres, invités et bots détectés</p>
        <div class="relative h-[250px]">
            <canvas id="traficChart"></canvas>
        </div>
    </div>
</div>

<!-- Répartition et activité récente -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Répartition des tickets -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            Tickets par statut
        </h2>
        <div class="relative h-[220px]">
            <canvas id="ticketsChart"></canvas>
        </div>
    </div>

    <!-- Flux d'activité (War Room) -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-0 overflow-hidden lg:col-span-2 flex flex-col h-full">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                Activité en Direct
            </h2>
            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-200 dark:bg-slate-700 px-2 py-1 rounded border border-slate-300 dark:border-slate-600">LIVE FEED</span>
        </div>
        
        <div class="flex-1 overflow-y-auto p-0 min-h-[300px] max-h-[500px]">
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($activityFeed as $activity)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group relative border-l-4 {{ $activity['type'] == 'reservation' ? 'border-blue-500' : ($activity['type'] == 'finance' ? 'border-yellow-500' : 'border-green-500') }}">
                        <div class="flex gap-4">
                            <!-- Icone -->
                            <div class="flex-shrink-0 mt-0.5">
                                <span class="flex items-center justify-center w-10 h-10 rounded-xl {{ 'bg-'.$activity['color'].'-100 dark:bg-'.$activity['color'].'-900/30 text-'.$activity['color'].'-600 dark:text-'.$activity['color'].'-400' }} border {{ 'border-'.$activity['color'].'-200 dark:border-'.$activity['color'].'-800' }} shadow-sm">
                                    {!! $activity['icon'] !!}
                                </span>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate pr-4">
                                        {{ $activity['text'] }}
                                    </p>
                                    <span class="text-xs font-medium text-slate-400 flex-shrink-0 whitespace-nowrap bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                        {{ $activity['time']->diffForHumans(null, true, true) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 capitalize">
                                        {{ $activity['time']->translatedFormat('d F H:i') }}
                                    </p>
                                    @if($activity['subtext'])
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ 'bg-'.$activity['color'].'-50 dark:bg-'.$activity['color'].'-900/20 text-'.$activity['color'].'-700 dark:text-'.$activity['color'].'-300' }}">
                                            {{ $activity['subtext'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="p-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700 text-center">
            <a href="{{ route('admin.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 uppercase tracking-wider transition-colors">
                Voir tout l'historique
            </a>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <a href="{{ route('admin.users.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <svg class="w-8 h-8 mx-auto flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Utilisateurs</p>
    </a>
    <a href="{{ route('admin.entreprises.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <svg class="w-8 h-8 mx-auto flex-shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Entreprises</p>
    </a>
    <a href="{{ route('admin.tickets.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <svg class="w-8 h-8 mx-auto flex-shrink-0 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
        </svg>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Tickets</p>
    </a>
    <a href="{{ route('admin.exports.index') }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:border-green-500 dark:hover:border-green-500 transition-all group text-center">
        <svg class="w-8 h-8 mx-auto flex-shrink-0 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        <p class="mt-2 font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Exports</p>
    </a>
</div>
@endsection

@push('scripts')
<script>
    // Configuration dynamique des couleurs
    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            text: isDark ? '#e2e8f0' : '#1e293b',
            grid: isDark ? '#334155' : '#e2e8f0',
            inscriptionsBg: isDark ? 'rgba(34, 197, 94, 0.2)' : 'rgba(34, 197, 94, 0.1)',
            reservationsBg: isDark ? 'rgba(168, 85, 247, 0.6)' : 'rgba(168, 85, 247, 0.7)'
        };
    }

    let inscriptionsChart = null;
    let reservationsChart = null;
    let connexionsChart = null;
    let traficChart = null;
    let ticketsChart = null;

    // Données (injectées par Blade)
    const inscriptionsData = {
        labels: {!! json_encode($chartData['inscriptions']['labels']) !!},
        data: {!! json_encode($chartData['inscriptions']['data']) !!}
    };
    
    const reservationsData = {
        labels: {!! json_encode($chartData['reservations']['labels']) !!},
        data: {!! json_encode($chartData['reservations']['data']) !!}
    };

    const connexionsData = {
        labels: {!! json_encode($chartData['connexions']['labels']) !!},
        data: {!! json_encode($chartData['connexions']['data']) !!}
    };

    const traficData = {!! json_encode($chartData['trafic']) !!};

    const ticketsData = {!! json_encode($chartData['tickets']) !!};

    function initCharts() {
        const colors = getThemeColors();
        const isDark = document.documentElement.classList.contains('dark');

        // 1. Inscriptions
        const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
        if (inscriptionsChart) inscriptionsChart.destroy();
        
        inscriptionsChart = new Chart(inscriptionsCtx, {
            type: 'line',
            data: {
                labels: inscriptionsData.labels,
                datasets: [{
                    label: 'Inscriptions',
                    data: inscriptionsData.data,
                    borderColor: '#22c55e',
                    backgroundColor: colors.inscriptionsBg,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text },
                        beginAtZero: true
                    }
                }
            }
        });

        // 2. Réservations
        const reservationsCtx = document.getElementById('reservationsChart').getContext('2d');
        if (reservationsChart) reservationsChart.destroy();

        reservationsChart = new Chart(reservationsCtx, {
            type: 'bar',
            data: {
                labels: reservationsData.labels,
                datasets: [{
                    label: 'Réservations',
                    data: reservationsData.data,
                    backgroundColor: colors.reservationsBg,
                    borderColor: '#a855f7',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: { 
                        grid: { color: colors.grid },
                        ticks: { color: colors.text },
                        beginAtZero: true
                    }
                }
            }
        });

        // 3. Connexions (membres uniques / jour)
        const connexionsCtx = document.getElementById('connexionsChart').getContext('2d');
        if (connexionsChart) connexionsChart.destroy();

        connexionsChart = new Chart(connexionsCtx, {
            type: 'line',
            data: {
                labels: connexionsData.labels,
                datasets: [{
                    label: 'Connexions',
                    data: connexionsData.data,
                    borderColor: '#6366f1',
                    backgroundColor: isDark ? 'rgba(99, 102, 241, 0.2)' : 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: {
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });

        // 4. Trafic site (empilé)
        const traficCtx = document.getElementById('traficChart').getContext('2d');
        if (traficChart) traficChart.destroy();

        traficChart = new Chart(traficCtx, {
            type: 'bar',
            data: {
                labels: traficData.labels,
                datasets: [
                    {
                        label: 'Membres',
                        data: traficData.members,
                        backgroundColor: 'rgba(34, 197, 94, 0.85)',
                        stack: 'trafic'
                    },
                    {
                        label: 'Invités',
                        data: traficData.guests,
                        backgroundColor: 'rgba(14, 165, 233, 0.85)',
                        stack: 'trafic'
                    },
                    {
                        label: 'Bots',
                        data: traficData.bots,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        stack: 'trafic'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: colors.text }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, maxRotation: 45, minRotation: 45 }
                    },
                    y: {
                        stacked: true,
                        grid: { color: colors.grid },
                        ticks: { color: colors.text, stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });

        // 5. Tickets
        const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
        if (ticketsChart) ticketsChart.destroy();

        ticketsChart = new Chart(ticketsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ouverts', 'En cours', 'Résolus', 'Fermés'],
                datasets: [{
                    data: ticketsData,
                    backgroundColor: [
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(148, 163, 184, 0.8)'
                    ],
                    borderColor: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: colors.text }
                    }
                }
            }
        });
    }

    // Initialisation
    initCharts();

    // Observer les changements de thème
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                initCharts();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true, 
        attributeFilter: ['class']
    });
</script>
@endpush
