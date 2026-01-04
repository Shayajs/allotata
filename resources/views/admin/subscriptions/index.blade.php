@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">💳 Gestion des abonnements</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Consultez et gérez tous les abonnements actifs (utilisateurs et entreprises).
            </p>
        </div>
        <form action="{{ route('admin.subscriptions.sync') }}" method="POST" id="sync-form">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition" id="sync-btn">
                <svg class="w-5 h-5" id="sync-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span id="sync-text">Synchroniser depuis Stripe</span>
            </button>
        </form>
    </div>

    @if(session('sync_success'))
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-400">🔄 {{ session('sync_success') }}</p>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-sm text-red-800 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filtres -->
    <div class="mb-6 flex gap-3">
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'all']) }}" 
           class="px-4 py-2 rounded-lg transition {{ $filter === 'all' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Tous
        </a>
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'users']) }}" 
           class="px-4 py-2 rounded-lg transition {{ $filter === 'users' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Utilisateurs
        </a>
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'entreprises']) }}" 
           class="px-4 py-2 rounded-lg transition {{ $filter === 'entreprises' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Entreprises
        </a>
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'stripe']) }}" 
           class="px-4 py-2 rounded-lg transition {{ $filter === 'stripe' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Stripe
        </a>
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'manual']) }}" 
           class="px-4 py-2 rounded-lg transition {{ $filter === 'manual' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Manuels
        </a>
    </div>

    <!-- Abonnements utilisateurs Stripe -->
    @if($userSubscriptions->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">👤 Abonnements utilisateurs (Stripe)</h2>
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Prix Stripe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Date création</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($userSubscriptions as $subscription)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $subscription->user->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $subscription->user->email ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $subscription->stripe_status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : '' }}
                                        {{ $subscription->stripe_status === 'trialing' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : '' }}
                                        {{ $subscription->stripe_status === 'past_due' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : '' }}">
                                        {{ ucfirst($subscription->stripe_status) }}
                                    </span>
                                    @if($subscription->ends_at)
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($subscription->stripe_price === config('services.stripe.price_id'))
                                        <span class="text-sm text-slate-900 dark:text-white font-medium">15.00 €</span>
                                        <span class="text-xs text-slate-500">/mois</span>
                                    @else
                                        <code class="text-xs text-slate-600 dark:text-slate-400">{{ $subscription->stripe_price ?? 'N/A' }}</code>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                    {{ $subscription->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="{{ route('admin.subscriptions.user.sync', $subscription) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-xs font-medium mr-2" title="Vérifier l'état sur Stripe">
                                            🔄 Sync
                                        </button>
                                    </form>
                                    
                                    <span class="text-slate-400 cursor-not-allowed text-xs font-medium mr-2" title="Géré par Stripe">
                                        🔒 Stripe
                                    </span>

                                    <a href="https://dashboard.stripe.com/{{ str_starts_with(config('services.stripe.key'), 'pk_test') ? 'test/' : '' }}subscriptions/{{ $subscription->stripe_id }}" 
                                       target="_blank" 
                                       class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-300 text-xs font-medium inline-flex items-center">
                                        Stripe
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Abonnements entreprises Stripe -->
    @if($entrepriseSubscriptions->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">🏢 Abonnements entreprises (Stripe)</h2>
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Entreprise</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Prix Stripe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Date création</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($entrepriseSubscriptions as $subscription)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $subscription->entreprise->nom ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $subscription->entreprise->email ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                        @if($subscription->type === 'site_web')
                                            Site Web Vitrine
                                        @elseif($subscription->type === 'multi_personnes')
                                            Multi-Personnes
                                        @else
                                            {{ $subscription->type }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $subscription->stripe_status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : '' }}
                                        {{ $subscription->stripe_status === 'trialing' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : '' }}
                                        {{ $subscription->stripe_status === 'past_due' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : '' }}">
                                        {{ ucfirst($subscription->stripe_status) }}
                                    </span>
                                    @if($subscription->ends_at)
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($subscription->stripe_price === config('services.stripe.price_id_site_web'))
                                        <span class="text-sm text-slate-900 dark:text-white font-medium">2.00 €</span>
                                        <span class="text-xs text-slate-500">/mois</span>
                                    @elseif($subscription->stripe_price === config('services.stripe.price_id_multi_personnes'))
                                        <span class="text-sm text-slate-900 dark:text-white font-medium">20.00 €</span>
                                        <span class="text-xs text-slate-500">/mois</span>
                                    @else
                                        <code class="text-xs text-slate-600 dark:text-slate-400">{{ $subscription->stripe_price ?? 'N/A' }}</code>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                    {{ $subscription->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="{{ route('admin.subscriptions.entreprise.sync', $subscription) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-xs font-medium mr-2" title="Vérifier l'état sur Stripe">
                                            🔄 Sync
                                        </button>
                                    </form>
                                    
                                    <span class="text-slate-400 cursor-not-allowed text-xs font-medium mr-2" title="Géré par Stripe">
                                        🔒 Stripe
                                    </span>

                                    <a href="https://dashboard.stripe.com/{{ str_starts_with(config('services.stripe.key'), 'pk_test') ? 'test/' : '' }}subscriptions/{{ $subscription->stripe_id }}" 
                                       target="_blank" 
                                       class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-300 text-xs font-medium inline-flex items-center">
                                        Stripe
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Abonnements manuels utilisateurs -->
    @if($manualUserSubscriptions->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">👤 Abonnements utilisateurs (Manuels)</h2>
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actif jusqu'au</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($manualUserSubscriptions as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $user->name }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                    {{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $user->abonnement_manuel_notes ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="{{ route('admin.users.subscription.toggle-manual', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Arrêter l\'abonnement manuel ?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
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
    @endif

    <!-- Abonnements manuels entreprises -->
    @if($manualEntrepriseSubscriptions->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">🏢 Abonnements entreprises (Manuels)</h2>
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Entreprise</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actif jusqu'au</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($manualEntrepriseSubscriptions as $subscription)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $subscription->entreprise->nom ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $subscription->entreprise->email ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                        @if($subscription->type === 'site_web')
                                            Site Web Vitrine
                                        @elseif($subscription->type === 'multi_personnes')
                                            Multi-Personnes
                                        @else
                                            {{ $subscription->type }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                    @if($subscription->actif_jusqu)
                                        {{ $subscription->actif_jusqu->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $subscription->notes_manuel ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="{{ route('admin.subscriptions.stop_manual', $subscription->id) }}" method="POST" onsubmit="return confirm('Arrêter cet abonnement manuel ?');" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Arrêter
                                        </button>
                                    </form>
                                    
                                    <!-- Bouton Modale Edit (à implémenter si besoin) -->
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($userSubscriptions->count() === 0 && $entrepriseSubscriptions->count() === 0 && $manualUserSubscriptions->count() === 0 && $manualEntrepriseSubscriptions->count() === 0)
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 text-center">
            <p class="text-slate-600 dark:text-slate-400">Aucun abonnement actif pour le moment.</p>
        </div>
    @endif

    <!-- Section Ajout Manuel -->
    <div class="mt-8 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">✨ Forcer un abonnement manuel (Entreprise)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
            Utilisez cette section pour offrir ou prolonger manuellement un abonnement à une entreprise, indépendamment de Stripe.
            <strong class="text-red-600 dark:text-red-400">Attention : L'abonnement manuel est PRIORITAIRE sur Stripe.</strong>
        </p>
        
        <form action="{{ route('admin.subscriptions.force_manual') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            
            <div>
                <label for="entreprise_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ID Entreprise</label>
                <input type="number" name="entreprise_id" id="entreprise_id" required placeholder="Ex: 42"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type d'abonnement</label>
                <select name="type" id="type" required
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="site_web">Site Web Vitrine</option>
                    <option value="multi_personnes">Gestion Multi-Personnes</option>
                </select>
            </div>
            
            <div>
                <label for="date_fin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de fin</label>
                <input type="date" name="date_fin" id="date_fin" required value="{{ now()->addYear()->format('Y-m-d') }}"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Activer Abonnement
                </button>
            </div>
            
            <div class="md:col-span-4 mt-2">
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes (Raison du geste commercial)</label>
                <input type="text" name="notes" id="notes" placeholder="Ex: Geste commercial suite au bug..." 
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </form>
    </div>
</div>
@endsection
