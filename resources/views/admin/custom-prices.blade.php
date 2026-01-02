@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">💎 Prix personnalisés</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Créez des prix personnalisés pour des utilisateurs ou entreprises spécifiques. Ces prix remplaceront les prix par défaut lors du checkout.
        </p>
    </div>

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

    <!-- Liste des prix personnalisés -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Prix personnalisés existants</h2>
        
        @if($customPrices->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cible</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prix Stripe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($customPrices as $customPrice)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($customPrice->user)
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">👤 {{ $customPrice->user->name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $customPrice->user->email }}</div>
                                    @elseif($customPrice->entreprise)
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">🏢 {{ $customPrice->entreprise->nom }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $customPrice->entreprise->email }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                        @if($customPrice->subscription_type === 'default')
                                            Abonnement utilisateur
                                        @elseif($customPrice->subscription_type === 'site_web')
                                            Site Web Vitrine
                                        @else
                                            Multi-Personnes
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ number_format($customPrice->amount, 2, ',', ' ') }} {{ strtoupper($customPrice->currency) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs text-slate-600 dark:text-slate-400">{{ $customPrice->stripe_price_id }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($customPrice->isValid())
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">Actif</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">Inactif</span>
                                    @endif
                                    @if($customPrice->expires_at)
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Expire: {{ $customPrice->expires_at->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="{{ route('admin.custom-prices.toggle', $customPrice) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                            {{ $customPrice->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.custom-prices.delete', $customPrice) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce prix personnalisé ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $customPrices->links() }}
            </div>
        @else
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 text-center">
                <p class="text-slate-600 dark:text-slate-400">Aucun prix personnalisé créé pour le moment.</p>
            </div>
        @endif
    </div>

    <!-- Formulaire de création -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Créer un prix personnalisé</h2>
        
        <form action="{{ route('admin.custom-prices.create') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Type de cible *
                    </label>
                    <select name="target_type" id="target_type" required onchange="toggleTargetFields()" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Sélectionner...</option>
                        <option value="user">Utilisateur</option>
                        <option value="entreprise">Entreprise</option>
                    </select>
                </div>

                <div id="user_field" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Utilisateur *
                    </label>
                    <select name="user_id" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Sélectionner un utilisateur...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div id="entreprise_field" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Entreprise *
                    </label>
                    <select name="entreprise_id" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Sélectionner une entreprise...</option>
                        @foreach($entreprises as $entreprise)
                            <option value="{{ $entreprise->id }}">{{ $entreprise->nom }} ({{ $entreprise->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Type d'abonnement *
                    </label>
                    <select name="subscription_type" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Sélectionner...</option>
                        <option value="default">Abonnement utilisateur</option>
                        <option value="site_web">Site Web Vitrine</option>
                        <option value="multi_personnes">Gestion Multi-Personnes</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Montant (€) *
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        step="0.01" 
                        min="0.01"
                        required
                        placeholder="10.00"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Devise *
                    </label>
                    <select name="currency" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="eur" selected>EUR (€)</option>
                        <option value="usd">USD ($)</option>
                        <option value="gbp">GBP (£)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Période de facturation *
                    </label>
                    <select name="interval" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="month" selected>Mensuel</option>
                        <option value="year">Annuel</option>
                        <option value="week">Hebdomadaire</option>
                        <option value="day">Quotidien</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nom du produit *
                    </label>
                    <input 
                        type="text" 
                        name="product_name" 
                        required
                        placeholder="Ex: Abonnement Premium Personnalisé"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Description du produit
                    </label>
                    <textarea 
                        name="product_description" 
                        rows="2"
                        placeholder="Description du produit..."
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notes (interne)
                    </label>
                    <textarea 
                        name="notes" 
                        rows="2"
                        placeholder="Raison du prix personnalisé (ex: Ristourne 50%, prix négocié...)"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Date d'expiration (optionnel)
                    </label>
                    <input 
                        type="date" 
                        name="expires_at" 
                        min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Si défini, le prix personnalisé expirera à cette date et le prix par défaut sera utilisé.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
                >
                    Créer le prix personnalisé
                </button>
                <a 
                    href="{{ route('admin.index') }}" 
                    class="px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition-colors"
                >
                    Annuler
                </a>
            </div>
        </form>
    </div>

    <!-- Instructions -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-400 mb-2">ℹ️ Comment ça fonctionne</h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <li>• Les prix personnalisés remplacent automatiquement les prix par défaut lors du checkout</li>
            <li>• Un prix personnalisé est créé directement sur Stripe avec le montant spécifié</li>
            <li>• Les webhooks et la gestion d'abonnement fonctionnent exactement de la même manière</li>
            <li>• L'utilisateur/entreprise verra le prix personnalisé lors du paiement</li>
            <li>• Vous pouvez définir une date d'expiration pour que le prix revienne au prix par défaut</li>
            <li>• Les prix personnalisés peuvent être activés/désactivés à tout moment</li>
        </ul>
    </div>
</div>

<script>
    function toggleTargetFields() {
        const targetType = document.getElementById('target_type').value;
        const userField = document.getElementById('user_field');
        const entrepriseField = document.getElementById('entreprise_field');
        
        if (targetType === 'user') {
            userField.classList.remove('hidden');
            entrepriseField.classList.add('hidden');
            userField.querySelector('select').required = true;
            entrepriseField.querySelector('select').required = false;
        } else if (targetType === 'entreprise') {
            userField.classList.add('hidden');
            entrepriseField.classList.remove('hidden');
            userField.querySelector('select').required = false;
            entrepriseField.querySelector('select').required = true;
        } else {
            userField.classList.add('hidden');
            entrepriseField.classList.add('hidden');
            userField.querySelector('select').required = false;
            entrepriseField.querySelector('select').required = false;
        }
    }
</script>
@endsection
