@extends('admin.layout')

@section('title', 'Webhooks Stripe')
@section('header', 'Gestion des webhooks Stripe')
@section('subheader', 'Surveillance et diagnostic des événements Stripe')

@section('content')
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

    <div class="bg-white dark:bg-slate-800 rounded-[32px] shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <!-- Tab Navigation -->
        <div class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
            <nav class="flex overflow-x-auto scrollbar-hide px-6" aria-label="Tabs">
                @php
                    $tabs = [
                        ['id' => 'configuration', 'label' => 'Configuration', 'icon' => '⚙️'],
                        ['id' => 'statistiques', 'label' => 'Statistiques', 'icon' => '📊', 'count' => $stats['total']],
                        ['id' => 'evenements', 'label' => 'Événements', 'icon' => '📋'],
                        ['id' => 'en-attente', 'label' => 'En attente', 'icon' => '⏳', 'count' => $stats['pending']],
                    ];
                @endphp
                @foreach($tabs as $tab)
                    <button 
                        onclick="showWebhookTab('{{ $tab['id'] }}')"
                        data-webhook-tab="{{ $tab['id'] }}"
                        class="webhook-tab-btn flex items-center gap-2 px-8 py-6 text-sm font-bold whitespace-nowrap border-b-2 transition-all {{ $loop->first ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
                    >
                        <span class="text-xl">{{ $tab['icon'] }}</span>
                        {{ $tab['label'] }}
                        @if(isset($tab['count']))
                            <span class="ml-1 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-[10px] rounded-md font-extrabold">{{ $tab['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-8 md:p-12">
            <!-- Onglet Configuration -->
            <div id="webhook-tab-configuration" class="webhook-tab-content">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Configuration du webhook</h2>
                        <p class="text-slate-600 dark:text-slate-400">Paramètres et informations de connexion Stripe</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                            <div class="flex items-center gap-2">
                                @if($webhookConfigured)
                                    <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        <span class="mr-2">✓</span> Configuré
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="mr-2">✗</span> Non configuré
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tolérance (secondes)</label>
                            <span class="text-lg font-semibold text-slate-900 dark:text-white">{{ $webhookTolerance }}</span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600 md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL du webhook</label>
                            <div class="flex items-center gap-2">
                                <code class="px-4 py-3 bg-white dark:bg-slate-800 rounded-lg text-sm text-slate-900 dark:text-white flex-1 font-mono border border-slate-200 dark:border-slate-600">
                                    {{ $webhookUrl }}
                                </code>
                                <button 
                                    onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); this.textContent='✓ Copié!'; setTimeout(() => this.textContent='📋', 2000);"
                                    class="px-4 py-3 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-300 rounded-lg transition font-medium"
                                    title="Copier l'URL"
                                >
                                    📋
                                </button>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600 md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Secret du webhook</label>
                            <div class="flex items-center gap-2">
                                @if($webhookSecret)
                                    <code class="px-4 py-3 bg-white dark:bg-slate-800 rounded-lg text-sm text-slate-900 dark:text-white flex-1 font-mono border border-slate-200 dark:border-slate-600">
                                        {{ substr($webhookSecret, 0, 30) }}...
                                    </code>
                                    <button 
                                        onclick="navigator.clipboard.writeText('{{ $webhookSecret }}'); this.textContent='✓ Copié!'; setTimeout(() => this.textContent='📋', 2000);"
                                        class="px-4 py-3 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-300 rounded-lg transition font-medium"
                                        title="Copier le secret"
                                    >
                                        📋
                                    </button>
                                @else
                                    <span class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-600 dark:text-red-400 font-medium">Non configuré</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if(!$webhookConfigured)
                        <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div>
                                    <h3 class="font-semibold text-yellow-900 dark:text-yellow-400 mb-2">Configuration requise</h3>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-2">
                                        Le secret du webhook n'est pas configuré. Les webhooks Stripe ne pourront pas être vérifiés.
                                    </p>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                        Ajoutez <code class="bg-yellow-100 dark:bg-yellow-900/40 px-2 py-1 rounded font-mono">STRIPE_WEBHOOK_SECRET</code> dans votre fichier <code class="bg-yellow-100 dark:bg-yellow-900/40 px-2 py-1 rounded font-mono">.env</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Onglet Statistiques -->
            <div id="webhook-tab-statistiques" class="webhook-tab-content hidden">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Statistiques</h2>
                        <p class="text-slate-600 dark:text-slate-400">Vue d'ensemble des webhooks reçus</p>
                    </div>

                    <!-- Statistiques globales -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-blue-500 dark:bg-blue-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">📊</span>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ number_format($stats['total'], 0, ',', ' ') }}</p>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-green-500 dark:bg-green-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">✓</span>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ number_format($stats['processed'], 0, ',', ' ') }}</p>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Traités</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-6 border border-orange-200 dark:border-orange-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-orange-500 dark:bg-orange-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">⏳</span>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ number_format($stats['pending'], 0, ',', ' ') }}</p>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">En attente</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-purple-500 dark:bg-purple-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">📅</span>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ number_format($stats['today'], 0, ',', ' ') }}</p>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aujourd'hui</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-yellow-500 dark:bg-yellow-600 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">🕐</span>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ number_format($stats['last_24h'], 0, ',', ' ') }}</p>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Dernières 24h</p>
                        </div>
                    </div>

                    <!-- Types d'événements -->
                    @if($eventTypes->count() > 0)
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600">
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Types d'événements reçus</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($eventTypes as $eventType)
                                    <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-300 dark:hover:border-green-700 transition">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $eventType->event_type }}</span>
                                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-lg text-sm font-bold">
                                            {{ $eventType->count }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Onglet Événements -->
            <div id="webhook-tab-evenements" class="webhook-tab-content hidden">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Événements reçus</h2>
                        <p class="text-slate-600 dark:text-slate-400">Liste complète des webhooks Stripe</p>
                    </div>

                    <!-- Filtres -->
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 border border-slate-200 dark:border-slate-600">
                        <form method="GET" action="{{ route('admin.stripe-webhooks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="search" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Recherche</label>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    value="{{ request('search') }}"
                                    placeholder="ID événement, customer..."
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                                >
                            </div>
                            
                            <div>
                                <label for="event_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type d'événement</label>
                                <select 
                                    id="event_type" 
                                    name="event_type" 
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                                >
                                    <option value="">Tous</option>
                                    @foreach($eventTypes as $eventType)
                                        <option value="{{ $eventType->event_type }}" {{ request('event_type') == $eventType->event_type ? 'selected' : '' }}>
                                            {{ $eventType->event_type }} ({{ $eventType->count }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="processed" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                                <select 
                                    id="processed" 
                                    name="processed" 
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                                >
                                    <option value="">Tous</option>
                                    <option value="1" {{ request('processed') == '1' ? 'selected' : '' }}>Traités</option>
                                    <option value="0" {{ request('processed') == '0' ? 'selected' : '' }}>Non traités</option>
                                </select>
                            </div>
                            
                            <div class="flex items-end gap-2">
                                <button 
                                    type="submit" 
                                    class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition text-sm"
                                >
                                    🔍 Filtrer
                                </button>
                                <a 
                                    href="{{ route('admin.stripe-webhooks.index') }}" 
                                    class="px-4 py-2 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm"
                                >
                                    ✕
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Liste des webhooks -->
                    @if($transactions->count() > 0)
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">ID Événement</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                        @foreach($transactions as $transaction)
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                        {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                                                    </div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $transaction->created_at->diffForHumans() }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-3 py-1 text-xs font-medium rounded-lg bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                        {{ $transaction->event_type }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <code class="text-xs text-slate-600 dark:text-slate-400 font-mono">
                                                        {{ Str::limit($transaction->stripe_event_id, 25) }}
                                                    </code>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($transaction->user)
                                                        <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                            {{ $transaction->user->name }}
                                                        </div>
                                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                                            {{ Str::limit($transaction->user->email, 30) }}
                                                        </div>
                                                    @elseif($transaction->stripe_customer_id)
                                                        <code class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                                            {{ Str::limit($transaction->stripe_customer_id, 20) }}
                                                        </code>
                                                    @else
                                                        <span class="text-sm text-slate-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($transaction->amount)
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency ?? 'EUR' }}
                                                        </span>
                                                    @else
                                                        <span class="text-sm text-slate-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($transaction->processed)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                            ✓ Traité
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                            ⏳ En attente
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <button 
                                                        onclick="showTransactionDetails({{ $transaction->id }})"
                                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition"
                                                    >
                                                        Détails
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                                {{ $transactions->links() }}
                            </div>
                        </div>
                    @else
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-600">
                            <p class="text-slate-500 dark:text-slate-400">Aucun webhook reçu pour le moment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Onglet En attente -->
            <div id="webhook-tab-en-attente" class="webhook-tab-content hidden">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Événements en attente</h2>
                        <p class="text-slate-600 dark:text-slate-400">Webhooks reçus mais non traités</p>
                    </div>

                    @if($recentPending->count() > 0)
                        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="text-3xl">⚠️</span>
                                <div>
                                    <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-400">Événements non traités</h3>
                                    <p class="text-sm text-orange-800 dark:text-orange-300">Ces webhooks nécessitent une attention</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                @foreach($recentPending as $transaction)
                                    <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-lg border border-orange-200 dark:border-orange-800">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="px-3 py-1 text-xs font-medium rounded-lg bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                    {{ $transaction->event_type }}
                                                </span>
                                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                                    {{ $transaction->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <code class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                                {{ $transaction->stripe_event_id }}
                                            </code>
                                        </div>
                                        <button 
                                            onclick="showTransactionDetails({{ $transaction->id }})"
                                            class="ml-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition text-sm"
                                        >
                                            Voir détails
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-12 text-center">
                            <span class="text-4xl mb-4 block">✓</span>
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-400 mb-2">Aucun événement en attente</h3>
                            <p class="text-sm text-green-800 dark:text-green-300">Tous les webhooks ont été traités avec succès !</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour les détails -->
    <div id="transaction-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Détails du webhook</h3>
                <button onclick="closeTransactionModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-2xl">
                    ✕
                </button>
            </div>
            <div id="transaction-details" class="p-6 overflow-y-auto flex-1">
                <!-- Le contenu sera chargé via JavaScript -->
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function showWebhookTab(tabId) {
            // Hide all contents
            document.querySelectorAll('.webhook-tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Reset all buttons
            document.querySelectorAll('.webhook-tab-btn').forEach(btn => {
                btn.classList.remove('border-green-500', 'text-green-600', 'dark:text-green-400');
                btn.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            });

            // Show selected content
            document.getElementById('webhook-tab-' + tabId)?.classList.remove('hidden');

            // Activate selected button
            const activeBtn = document.querySelector(`[data-webhook-tab="${tabId}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                activeBtn.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
            }

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }

        function showTransactionDetails(id) {
            fetch(`/admin/stripe-webhooks/${id}/details`)
                .then(response => response.json())
                .then(data => {
                    const detailsHtml = `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type d'événement</label>
                                <code class="block px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-mono">${data.event_type}</code>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ID Événement Stripe</label>
                                <code class="block px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-mono">${data.stripe_event_id}</code>
                            </div>
                            ${data.stripe_customer_id ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ID Client Stripe</label>
                                <code class="block px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-mono">${data.stripe_customer_id}</code>
                            </div>
                            ` : ''}
                            ${data.amount ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant</label>
                                <span class="block px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm text-slate-900 dark:text-white font-semibold">${data.amount} ${data.currency || 'EUR'}</span>
                            </div>
                            ` : ''}
                            ${data.status ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                                <span class="block px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm text-slate-900 dark:text-white">${data.status}</span>
                            </div>
                            ` : ''}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Données brutes (JSON)</label>
                                <pre class="px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs text-slate-900 dark:text-white overflow-x-auto max-h-96 font-mono">${JSON.stringify(data.raw_data, null, 2)}</pre>
                            </div>
                        </div>
                    `;
                    document.getElementById('transaction-details').innerHTML = detailsHtml;
                    document.getElementById('transaction-modal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des détails');
                });
        }

        function closeTransactionModal() {
            document.getElementById('transaction-modal').classList.add('hidden');
        }

        // Handle initial tab from URL
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab');
            if (initialTab && document.getElementById('webhook-tab-' + initialTab)) {
                showWebhookTab(initialTab);
            }
        });

        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTransactionModal();
            }
        });
    </script>
    @endpush
@endsection
