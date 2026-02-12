@extends('admin.layout')

@section('title', 'Gestion des abonnements')
@section('header', 'Abonnements')
@section('subheader', 'Consultez et gérez tous les abonnements actifs')

@section('content')
{{-- En-tête --}}
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-3">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Gestion des abonnements
        </h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Consultez et gérez tous les abonnements actifs (utilisateurs et entreprises).</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <form action="{{ route('admin.subscriptions.sync') }}" method="POST" id="sync-form">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all text-sm" id="sync-btn">
                <svg class="w-4 h-4" id="sync-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span id="sync-text">Synchroniser Stripe</span>
            </button>
        </form>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition border border-slate-200 dark:border-slate-700 rounded-lg">
            &larr; Retour au Dashboard
        </a>
    </div>
</div>

{{-- Alertes --}}
@if(session('sync_success'))
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <p class="text-sm font-medium text-blue-800 dark:text-blue-400">{{ session('sync_success') }}</p>
    </div>
@endif

@if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center gap-2">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <p class="text-sm font-medium text-green-800 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <ul class="text-sm text-red-800 dark:text-red-400 space-y-1">
            @foreach($errors->all() as $error)
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Filtres --}}
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 mr-2">Filtrer :</span>
        @php
            $filters = [
                'all' => ['label' => 'Tous', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                'users' => ['label' => 'Utilisateurs', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                'entreprises' => ['label' => 'Entreprises', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                'stripe' => ['label' => 'Stripe', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                'manual' => ['label' => 'Manuels', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ];
        @endphp
        @foreach($filters as $key => $f)
            <a href="{{ route('admin.subscriptions.index', ['filter' => $key]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all
                   {{ $filter === $key 
                       ? 'bg-green-600 text-white shadow-sm' 
                       : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"></path>
                </svg>
                {{ $f['label'] }}
            </a>
        @endforeach
    </div>
</div>

{{-- Compteurs rapides --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $userSubscriptions->count() }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Users Stripe</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $entrepriseSubscriptions->count() }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Entreprises Stripe</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $manualUserSubscriptions->count() }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Users manuels</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $manualEntrepriseSubscriptions->count() }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Entreprises manuels</p>
            </div>
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- Abonnements utilisateurs (Stripe) --}}
{{-- ============================================ --}}
@if($userSubscriptions->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Abonnements utilisateurs (Stripe)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $userSubscriptions->count() }} abonnement(s) actif(s)</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Cr&eacute;&eacute; le</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($userSubscriptions as $subscription)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Utilisateur">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $subscription->user->name ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $subscription->user->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                            @php
                                $statusConfig = match($subscription->stripe_status) {
                                    'active' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
                                    'trialing' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                                    'past_due' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
                                    'canceled' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
                                    default => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-700 dark:text-slate-400', 'dot' => 'bg-slate-500'],
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                {{ ucfirst($subscription->stripe_status) }}
                            </span>
                            @if($subscription->ends_at)
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Expire le {{ $subscription->ends_at->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Prix">
                            @if($subscription->stripe_price === config('services.stripe.price_id'))
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">15,00 &euro;</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">/mois</span>
                            @else
                                <code class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded">{{ $subscription->stripe_price ?? 'N/A' }}</code>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Créé le">
                            {{ $subscription->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Actions">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.subscriptions.user.sync', $subscription) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors" title="Synchroniser avec Stripe">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Sync
                                    </button>
                                </form>
                                <a href="https://dashboard.stripe.com/{{ str_starts_with(config('services.stripe.key'), 'pk_test') ? 'test/' : '' }}subscriptions/{{ $subscription->stripe_id }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">
                                    Stripe
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ============================================ --}}
{{-- Abonnements entreprises (Stripe) --}}
{{-- ============================================ --}}
@if($entrepriseSubscriptions->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Abonnements entreprises (Stripe)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $entrepriseSubscriptions->count() }} abonnement(s) actif(s)</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprise</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Cr&eacute;&eacute; le</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($entrepriseSubscriptions as $subscription)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Entreprise">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $subscription->entreprise->nom ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $subscription->entreprise->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                            @php
                                $typeLabel = match($subscription->type) {
                                    'site_web' => 'Site Web Vitrine',
                                    'multi_personnes' => 'Multi-Personnes',
                                    default => $subscription->type,
                                };
                                $typeColor = match($subscription->type) {
                                    'site_web' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                                    'multi_personnes' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
                                    default => 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $typeColor }}">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                            @php
                                $statusConfig = match($subscription->stripe_status) {
                                    'active' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
                                    'trialing' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                                    'past_due' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
                                    'canceled' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
                                    default => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-700 dark:text-slate-400', 'dot' => 'bg-slate-500'],
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                {{ ucfirst($subscription->stripe_status) }}
                            </span>
                            @if($subscription->ends_at)
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Expire le {{ $subscription->ends_at->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Prix">
                            @if($subscription->stripe_price === config('services.stripe.price_id_site_web'))
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">2,00 &euro;</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">/mois</span>
                            @elseif($subscription->stripe_price === config('services.stripe.price_id_multi_personnes'))
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">20,00 &euro;</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">/mois</span>
                            @else
                                <code class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded">{{ $subscription->stripe_price ?? 'N/A' }}</code>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Créé le">
                            {{ $subscription->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Actions">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.subscriptions.entreprise.sync', $subscription) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors" title="Synchroniser avec Stripe">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Sync
                                    </button>
                                </form>
                                <a href="https://dashboard.stripe.com/{{ str_starts_with(config('services.stripe.key'), 'pk_test') ? 'test/' : '' }}subscriptions/{{ $subscription->stripe_id }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">
                                    Stripe
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ============================================ --}}
{{-- Abonnements utilisateurs (Manuels) --}}
{{-- ============================================ --}}
@if($manualUserSubscriptions->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Abonnements utilisateurs (Manuels)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $manualUserSubscriptions->count() }} abonnement(s) manuel(s)</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actif jusqu'au</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($manualUserSubscriptions as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Utilisateur">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $user->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Actif jusqu'au">
                            @php
                                $expireSoon = $user->abonnement_manuel_actif_jusqu && $user->abonnement_manuel_actif_jusqu->diffInDays(now()) <= 7;
                                $expired = $user->abonnement_manuel_actif_jusqu && $user->abonnement_manuel_actif_jusqu->isPast();
                            @endphp
                            <span class="text-sm font-medium {{ $expired ? 'text-red-600 dark:text-red-400' : ($expireSoon ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white') }}">
                                {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}
                            </span>
                            @if($expired)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">EXPIRÉ</span>
                            @elseif($expireSoon)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">BIENTÔT</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate" data-label="Notes">
                            {{ $user->abonnement_manuel_notes ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Actions">
                            <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Arrêter l\'abonnement manuel ?');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Arrêter
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ============================================ --}}
{{-- Abonnements entreprises (Manuels) --}}
{{-- ============================================ --}}
@if($manualEntrepriseSubscriptions->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Abonnements entreprises (Manuels)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $manualEntrepriseSubscriptions->count() }} abonnement(s) manuel(s)</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprise</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actif jusqu'au</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($manualEntrepriseSubscriptions as $subscription)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Entreprise">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $subscription->entreprise->nom ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $subscription->entreprise->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                            @php
                                $typeLabel = match($subscription->type) {
                                    'site_web' => 'Site Web Vitrine',
                                    'multi_personnes' => 'Multi-Personnes',
                                    default => $subscription->type,
                                };
                                $typeColor = match($subscription->type) {
                                    'site_web' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                                    'multi_personnes' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
                                    default => 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $typeColor }}">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Actif jusqu'au">
                            @if($subscription->actif_jusqu)
                                @php
                                    $expireSoon = $subscription->actif_jusqu->diffInDays(now()) <= 7;
                                    $expired = $subscription->actif_jusqu->isPast();
                                @endphp
                                <span class="text-sm font-medium {{ $expired ? 'text-red-600 dark:text-red-400' : ($expireSoon ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white') }}">
                                    {{ $subscription->actif_jusqu->format('d/m/Y') }}
                                </span>
                                @if($expired)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">EXPIRÉ</span>
                                @elseif($expireSoon)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">BIENTÔT</span>
                                @endif
                            @else
                                <span class="text-sm text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate" data-label="Notes">
                            {{ $subscription->notes_manuel ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Actions">
                            <form action="{{ route('admin.subscriptions.stop_manual', $subscription->id) }}" method="POST" onsubmit="return confirm('Arrêter cet abonnement manuel ?');" class="inline-block">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Arrêter
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- État vide --}}
@if($userSubscriptions->count() === 0 && $entrepriseSubscriptions->count() === 0 && $manualUserSubscriptions->count() === 0 && $manualEntrepriseSubscriptions->count() === 0)
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
    </svg>
    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-1">Aucun abonnement</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">Aucun abonnement actif pour le filtre sélectionné.</p>
</div>
@endif

{{-- ============================================ --}}
{{-- Formulaire - Forcer un abonnement manuel --}}
{{-- ============================================ --}}
<div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Forcer un abonnement manuel (Entreprise)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Offrir ou prolonger manuellement un abonnement, indépendamment de Stripe.</p>
            </div>
        </div>
    </div>
    <div class="p-6">
        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-lg flex items-start gap-2">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <p class="text-sm text-amber-800 dark:text-amber-400">
                <strong>Attention :</strong> L'abonnement manuel est PRIORITAIRE sur Stripe.
            </p>
        </div>

        <form action="{{ route('admin.subscriptions.force_manual') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="entreprise_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ID Entreprise</label>
                    <input type="number" name="entreprise_id" id="entreprise_id" required placeholder="Ex: 42"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type d'abonnement</label>
                    <select name="type" id="type" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <option value="site_web">Site Web Vitrine</option>
                        <option value="multi_personnes">Gestion Multi-Personnes</option>
                    </select>
                </div>
                <div>
                    <label for="date_fin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                    <input type="date" name="date_fin" id="date_fin" required value="{{ now()->addYear()->format('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all text-sm">
                        Activer Abonnement
                    </button>
                </div>
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes (Raison du geste commercial)</label>
                <input type="text" name="notes" id="notes" placeholder="Ex: Geste commercial suite au bug..."
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
        </form>
    </div>
</div>
@endsection
