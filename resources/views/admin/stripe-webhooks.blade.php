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

    <!-- Configuration du webhook -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Configuration du webhook</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                <div class="flex items-center gap-2">
                    @if($webhookConfigured)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            ✓ Configuré
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            ✗ Non configuré
                        </span>
                    @endif
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL du webhook</label>
                <div class="flex items-center gap-2">
                    <code class="px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white flex-1">
                        {{ $webhookUrl }}
                    </code>
                    <button 
                        onclick="navigator.clipboard.writeText('{{ $webhookUrl }}')"
                        class="px-3 py-2 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-300 rounded text-sm transition"
                        title="Copier l'URL"
                    >
                        📋
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secret du webhook</label>
                <div class="flex items-center gap-2">
                    @if($webhookSecret)
                        <code class="px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white flex-1 font-mono">
                            {{ substr($webhookSecret, 0, 20) }}...
                        </code>
                    @else
                        <span class="text-sm text-red-600 dark:text-red-400">Non configuré</span>
                    @endif
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tolérance (secondes)</label>
                <span class="text-sm text-slate-600 dark:text-slate-400">{{ $webhookTolerance }}</span>
            </div>
        </div>
        
        @if(!$webhookConfigured)
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-400">
                    ⚠️ Le secret du webhook n'est pas configuré. Les webhooks Stripe ne pourront pas être vérifiés.
                    Ajoutez <code class="bg-yellow-100 dark:bg-yellow-900/40 px-1 rounded">STRIPE_WEBHOOK_SECRET</code> dans votre fichier .env
                </p>
            </div>
        @endif
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">📊</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">✓</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['processed'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Traités</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">⏳</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['pending'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">En attente</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">📅</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['today'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Aujourd'hui</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-xl">🕐</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['last_24h'], 0, ',', ' ') }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Dernières 24h</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Types d'événements -->
    @if($eventTypes->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Types d'événements reçus</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($eventTypes as $eventType)
                    <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $eventType->event_type }}</span>
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded text-xs font-semibold">
                            {{ $eventType->count }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Événements en attente récents -->
    @if($recentPending->count() > 0)
        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-orange-900 dark:text-orange-400 mb-4">⚠️ Événements non traités récents</h2>
            <div class="space-y-2">
                @foreach($recentPending as $transaction)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg">
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $transaction->event_type }}</span>
                            <span class="text-sm text-slate-600 dark:text-slate-400 ml-2">
                                {{ $transaction->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <code class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                            {{ $transaction->stripe_event_id }}
                        </code>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="{{ route('admin.stripe-webhooks.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Recherche</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="ID événement, customer, subscription..."
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                >
            </div>
            
            <div class="min-w-[150px]">
                <label for="event_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type d'événement</label>
                <select 
                    id="event_type" 
                    name="event_type" 
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                >
                    <option value="">Tous</option>
                    @foreach($eventTypes as $eventType)
                        <option value="{{ $eventType->event_type }}" {{ request('event_type') == $eventType->event_type ? 'selected' : '' }}>
                            {{ $eventType->event_type }} ({{ $eventType->count }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="min-w-[150px]">
                <label for="processed" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                <select 
                    id="processed" 
                    name="processed" 
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                >
                    <option value="">Tous</option>
                    <option value="1" {{ request('processed') == '1' ? 'selected' : '' }}>Traités</option>
                    <option value="0" {{ request('processed') == '0' ? 'selected' : '' }}>Non traités</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition text-sm"
                >
                    🔍 Filtrer
                </button>
                <a 
                    href="{{ route('admin.stripe-webhooks.index') }}" 
                    class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm"
                >
                    ✕ Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des webhooks -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Type d'événement</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">ID Événement</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white">
                                        {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $transaction->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $transaction->event_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs text-slate-600 dark:text-slate-400 font-mono">
                                        {{ Str::limit($transaction->stripe_event_id, 20) }}
                                    </code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->user)
                                        <div class="text-sm text-slate-900 dark:text-white">
                                            {{ $transaction->user->name }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $transaction->user->email }}
                                        </div>
                                    @elseif($transaction->stripe_customer_id)
                                        <code class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                            {{ Str::limit($transaction->stripe_customer_id, 15) }}
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
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ✓ Traité
                                        </span>
                                        @if($transaction->processed_at)
                                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                {{ $transaction->processed_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                            ⏳ En attente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button 
                                        onclick="showTransactionDetails({{ $transaction->id }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
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
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">Aucun webhook reçu pour le moment.</p>
            </div>
        @endif
    </div>

    <!-- Modal pour les détails -->
    <div id="transaction-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Détails du webhook</h3>
                <button onclick="closeTransactionModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
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
        function showTransactionDetails(id) {
            fetch(`/admin/stripe-webhooks/${id}/details`)
                .then(response => response.json())
                .then(data => {
                    const detailsHtml = `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type d'événement</label>
                                <code class="block px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white font-mono">${data.event_type}</code>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ID Événement Stripe</label>
                                <code class="block px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white font-mono">${data.stripe_event_id}</code>
                            </div>
                            ${data.stripe_customer_id ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ID Client Stripe</label>
                                <code class="block px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white font-mono">${data.stripe_customer_id}</code>
                            </div>
                            ` : ''}
                            ${data.amount ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant</label>
                                <span class="block px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white">${data.amount} ${data.currency || 'EUR'}</span>
                            </div>
                            ` : ''}
                            ${data.status ? `
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                                <span class="block px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-900 dark:text-white">${data.status}</span>
                            </div>
                            ` : ''}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Données brutes (JSON)</label>
                                <pre class="px-4 py-3 bg-slate-100 dark:bg-slate-700 rounded text-xs text-slate-900 dark:text-white overflow-x-auto max-h-96">${JSON.stringify(data.raw_data, null, 2)}</pre>
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

        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTransactionModal();
            }
        });
    </script>
    @endpush
@endsection
