@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gestion des prix Stripe</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Créez et gérez les prix Stripe pour les abonnements. Les prix créés seront automatiquement configurés dans le fichier .env.
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

    <!-- Prix existants -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Prix configurés</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($prices as $key => $price)
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ $price['label'] }}
                        </h3>
                        @if($price['id'] && isset($price['stripe_data']))
                            <button 
                                onclick="openEditModal('{{ $key }}', {{ json_encode($price['stripe_data']) }})"
                                class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                            >
                                ✏️ Modifier
                            </button>
                        @elseif(!$price['id'])
                            <button 
                                onclick="openCreateMissingModal('{{ $key }}', '{{ $price['label'] }}')"
                                class="px-3 py-1.5 text-xs bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                            >
                                ➕ Créer
                            </button>
                        @endif
                    </div>
                    
                    @if($price['id'])
                        @if(isset($price['stripe_data']))
                            <div class="space-y-2 text-sm mb-4">
                                <div class="flex justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">ID Stripe:</span>
                                    <span class="font-mono text-xs text-slate-900 dark:text-white">{{ $price['stripe_data']['id'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">Montant:</span>
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ number_format($price['stripe_data']['amount'], 2, ',', ' ') }} {{ strtoupper($price['stripe_data']['currency']) }}
                                    </span>
                                </div>
                                @if($price['stripe_data']['recurring'])
                                    <div class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-400">Période:</span>
                                        <span class="text-slate-900 dark:text-white">
                                            /{{ $price['stripe_data']['recurring']['interval'] }}
                                        </span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">Statut:</span>
                                    <span class="px-2 py-1 rounded text-xs {{ $price['stripe_data']['active'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                        {{ $price['stripe_data']['active'] ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>
                        @elseif(isset($price['error']))
                            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded mb-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-400">{{ $price['error'] }}</p>
                            </div>
                        @else
                            <div class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                <p class="font-mono text-xs">{{ $price['id'] }}</p>
                                <p class="mt-2 text-xs text-slate-500">Vérification en cours...</p>
                            </div>
                        @endif
                    @else
                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded mb-4">
                            <p class="text-sm text-orange-800 dark:text-orange-400 mb-2">⚠️ Prix non configuré sur Stripe</p>
                            <p class="text-xs text-orange-700 dark:text-orange-300">Les utilisateurs ne pourront pas s'abonner tant que le prix n'est pas créé.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Formulaire de création -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Créer un nouveau prix Stripe</h2>
        
        <form action="{{ route('admin.stripe-prices.create') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Type d'abonnement *
                    </label>
                    <select name="type" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Sélectionner un type</option>
                        <option value="default">Abonnement utilisateur (15€/mois)</option>
                        <option value="site_web">Site Web Vitrine (5€/mois)</option>
                        <option value="multi_personnes">Gestion Multi-Personnes (20€/mois)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Le prix sera automatiquement associé à ce type d'abonnement.
                    </p>
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
                        placeholder="2.00"
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
                        placeholder="Ex: Abonnement Site Web Vitrine"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Si un produit avec ce nom existe déjà, il sera réutilisé.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Description du produit (optionnel)
                    </label>
                    <textarea 
                        name="product_description" 
                        rows="3"
                        placeholder="Description du produit..."
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
                >
                    Créer le prix Stripe
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
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-400 mb-2">ℹ️ Instructions</h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <li>• Les prix créés seront automatiquement ajoutés au fichier <code class="bg-blue-100 dark:bg-blue-900/50 px-2 py-1 rounded">.env</code></li>
            <li>• Si un produit avec le même nom existe déjà sur Stripe, il sera réutilisé</li>
            <li>• Les prix sont créés directement sur Stripe via l'API</li>
            <li>• Après création, vous pouvez utiliser ces prix pour les abonnements</li>
            <li>• Les prix existants sont affichés ci-dessus avec leurs détails</li>
            <li>• <strong>Note :</strong> Modifier un prix créera un nouveau prix et désactivera l'ancien (Stripe ne permet pas de modifier un prix existant)</li>
        </ul>
    </div>

    <!-- Modale de modification -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Modifier le prix</h3>
                    <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        ✕
                    </button>
                </div>
                <form id="editForm" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" id="editType" name="type">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant (€) *</label>
                            <input type="number" id="editAmount" name="amount" step="0.01" min="0.01" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Devise *</label>
                            <select id="editCurrency" name="currency" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="eur">EUR (€)</option>
                                <option value="usd">USD ($)</option>
                                <option value="gbp">GBP (£)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période *</label>
                            <select id="editInterval" name="interval" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="month">Mensuel</option>
                                <option value="year">Annuel</option>
                                <option value="week">Hebdomadaire</option>
                                <option value="day">Quotidien</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nom du produit *</label>
                            <input type="text" id="editProductName" name="product_name" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description du produit</label>
                            <textarea id="editProductDescription" name="product_description" rows="3" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                            Modifier le prix
                        </button>
                        <button type="button" onclick="closeEditModal()" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modale de création de prix manquant -->
    <div id="createMissingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Créer le prix manquant</h3>
                    <button onclick="closeCreateMissingModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        ✕
                    </button>
                </div>
                <form id="createMissingForm" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" id="createMissingType" name="type">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                        <p class="text-sm text-blue-800 dark:text-blue-400">
                            <strong id="createMissingLabel"></strong> n'est pas encore configuré sur Stripe. Remplissez le formulaire ci-dessous pour créer le prix avec les valeurs par défaut (vous pouvez les modifier).
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant (€)</label>
                            <input type="number" id="createMissingAmount" name="amount" step="0.01" min="0.01" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Laissez vide pour utiliser la valeur par défaut</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Devise</label>
                            <select id="createMissingCurrency" name="currency" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="eur" selected>EUR (€)</option>
                                <option value="usd">USD ($)</option>
                                <option value="gbp">GBP (£)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période</label>
                            <select id="createMissingInterval" name="interval" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="month" selected>Mensuel</option>
                                <option value="year">Annuel</option>
                                <option value="week">Hebdomadaire</option>
                                <option value="day">Quotidien</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nom du produit</label>
                            <input type="text" id="createMissingProductName" name="product_name" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Laissez vide pour utiliser le nom par défaut</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description du produit</label>
                            <textarea id="createMissingProductDescription" name="product_description" rows="3" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                            Créer le prix
                        </button>
                        <button type="button" onclick="closeCreateMissingModal()" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const defaultAmounts = {
        'default': 15.00,
        'site_web': 5.00,
        'multi_personnes': 20.00
    };

    const defaultLabels = {
        'default': 'Abonnement utilisateur',
        'site_web': 'Site Web Vitrine',
        'multi_personnes': 'Gestion Multi-Personnes'
    };

    // URLs de base pour éviter les problèmes de génération de routes
    const baseUrl = '{{ url("/admin/stripe-prices") }}';

    function openEditModal(type, stripeData) {
        document.getElementById('editType').value = type;
        document.getElementById('editAmount').value = stripeData.amount;
        document.getElementById('editCurrency').value = stripeData.currency;
        document.getElementById('editInterval').value = stripeData.recurring?.interval || 'month';
        // Construire l'URL correctement
        document.getElementById('editForm').action = baseUrl + '/' + type + '/update';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function openCreateMissingModal(type, label) {
        document.getElementById('createMissingType').value = type;
        document.getElementById('createMissingLabel').textContent = label;
        document.getElementById('createMissingAmount').value = defaultAmounts[type] || '';
        document.getElementById('createMissingProductName').value = defaultLabels[type] || '';
        // Construire l'URL correctement
        document.getElementById('createMissingForm').action = baseUrl + '/' + type + '/create-missing';
        document.getElementById('createMissingModal').classList.remove('hidden');
    }

    function closeCreateMissingModal() {
        document.getElementById('createMissingModal').classList.add('hidden');
    }

    // Fermer les modales en cliquant en dehors
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    document.getElementById('createMissingModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeCreateMissingModal();
    });
</script>
@endsection
