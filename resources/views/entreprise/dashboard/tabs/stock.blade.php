<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Stock et Produits</h2>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-red-800 dark:text-red-300 font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            @foreach($errors->all() as $error)
                <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Section Produits -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </span>
                Produits
            </h3>
            <button 
                onclick="document.getElementById('modal-produit').classList.remove('hidden')"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
            >
                + Ajouter un produit
            </button>
        </div>

        @if($produits && $produits->count() > 0)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($produits as $produit)
                    <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow {{ $produit->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $produit->nom }}</h4>
                                @if($produit->images->count() > 0)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">📷 {{ $produit->images->count() }} image(s)</span>
                                @endif
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $produit->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $produit->est_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        
                        @php
                            $imageCouverture = $produit->imageCouverture;
                            $premiereImage = $produit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        @endphp
                        
                        @if($imageAffichee)
                            <div class="mb-3 rounded-lg overflow-hidden">
                                <img src="{{ asset('media/' . $imageAffichee->image_path) }}" alt="{{ $produit->nom }}" class="w-full h-32 object-cover">
                            </div>
                        @endif
                        
                        @if($produit->description)
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ $produit->description }}</p>
                        @endif
                        
                        <div class="flex items-center gap-4 text-sm mb-3">
                            @if($promotion)
                                <div class="flex items-center gap-2">
                                    <span class="line-through text-slate-400 text-xs">{{ number_format($produit->prix, 2, ',', ' ') }} €</span>
                                    <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($prixActuel, 2, ',', ' ') }} €</span>
                                    <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded">PROMO</span>
                                </div>
                            @else
                                <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($prixActuel, 2, ',', ' ') }} €</span>
                            @endif
                        </div>

                        <!-- Info Stock -->
                        @if($produit->gestion_stock === 'disponible_immediatement' && $produit->stock)
                            <div class="mb-3 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">Stock:</span>
                                    <span class="font-bold {{ $produit->stock->quantite_disponible <= $produit->stock->quantite_minimum ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                        {{ $produit->stock->quantite_disponible }}
                                        @if($produit->stock->alerte_stock)
                                            <span class="ml-1">⚠️</span>
                                        @endif
                                    </span>
                                </div>
                                @if($produit->stock->quantite_minimum > 0)
                                    <div class="flex items-center justify-between text-xs mt-1">
                                        <span class="text-slate-500 dark:text-slate-400">Seuil:</span>
                                        <span class="text-slate-600 dark:text-slate-400">{{ $produit->stock->quantite_minimum }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($produit->gestion_stock === 'en_attente_commandes')
                            <div class="mb-3 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <span class="text-xs text-orange-700 dark:text-orange-400">📦 En attente de commandes</span>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button 
                                onclick="editProduit({{ $produit->id }}, '{{ addslashes($produit->nom) }}', '{{ addslashes($produit->description ?? '') }}', {{ $produit->prix }}, '{{ $produit->gestion_stock }}', {{ $produit->stock ? ($produit->stock->quantite_disponible ?? 0) : 0 }}, {{ $produit->stock ? ($produit->stock->quantite_minimum ?? 0) : 0 }}, {{ $produit->est_actif ? 'true' : 'false' }})"
                                class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                            >
                                Modifier
                            </button>
                            <form action="{{ route('stock.produit.delete', [$entreprise->slug, $produit->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce produit ?');" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 text-sm font-medium bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-lg font-medium mb-2">Aucun produit enregistré</p>
                <p class="text-sm">Commencez par ajouter votre premier produit.</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal Ajout/Modification Produit -->
<div id="modal-produit" class="hidden fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
    <div class="modal-content rounded-2xl shadow-2xl p-6 max-w-2xl w-full">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modal-produit-title">Ajouter un produit</h3>
            <button onclick="document.getElementById('modal-produit').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="form-produit" action="{{ route('stock.produit.store', $entreprise->slug) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="produit_id" id="produit_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom *</label>
                    <input 
                        type="text" 
                        name="nom" 
                        id="produit_nom"
                        required
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                    <textarea 
                        name="description" 
                        id="produit_description"
                        rows="3"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                    <input 
                        type="number" 
                        name="prix" 
                        id="produit_prix"
                        step="0.01"
                        min="0"
                        required
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Type de gestion *</label>
                    <select 
                        name="gestion_stock" 
                        id="produit_gestion_stock"
                        onchange="toggleStockFields()"
                        required
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="disponible_immediatement">Disponible immédiatement (gestion stock)</option>
                        <option value="en_attente_commandes">En attente de commandes</option>
                    </select>
                </div>

                <div id="stock-fields" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité disponible</label>
                        <input 
                            type="number" 
                            name="quantite_disponible" 
                            id="produit_quantite_disponible"
                            min="0"
                            value="0"
                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité minimum (alerte)</label>
                        <input 
                            type="number" 
                            name="quantite_minimum" 
                            id="produit_quantite_minimum"
                            min="0"
                            value="0"
                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Images</label>
                    <input 
                        type="file" 
                        name="images[]" 
                        multiple
                        accept="image/*"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <label class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <input 
                        type="checkbox" 
                        name="est_actif" 
                        id="produit_est_actif"
                        value="1"
                        checked
                        class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Produit actif</span>
                </label>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('modal-produit').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editProduit(id, nom, description, prix, gestionStock, quantiteDisponible, quantiteMinimum, estActif) {
        document.getElementById('modal-produit-title').textContent = 'Modifier le produit';
        document.getElementById('produit_id').value = id;
        document.getElementById('produit_nom').value = nom;
        document.getElementById('produit_description').value = description;
        document.getElementById('produit_prix').value = prix;
        document.getElementById('produit_gestion_stock').value = gestionStock;
        document.getElementById('produit_quantite_disponible').value = quantiteDisponible || 0;
        document.getElementById('produit_quantite_minimum').value = quantiteMinimum || 0;
        document.getElementById('produit_est_actif').checked = estActif === 'true';
        toggleStockFields();
        document.getElementById('modal-produit').classList.remove('hidden');
    }

    function toggleStockFields() {
        const gestionStock = document.getElementById('produit_gestion_stock').value;
        const stockFields = document.getElementById('stock-fields');
        if (gestionStock === 'disponible_immediatement') {
            stockFields.style.display = 'grid';
            document.getElementById('produit_quantite_disponible').required = true;
        } else {
            stockFields.style.display = 'none';
            document.getElementById('produit_quantite_disponible').required = false;
        }
    }

    // Initialiser l'affichage des champs stock
    toggleStockFields();

    // Réinitialiser le formulaire à l'ouverture
    document.querySelector('[onclick*="modal-produit"]')?.addEventListener('click', function() {
        document.getElementById('modal-produit-title').textContent = 'Ajouter un produit';
        document.getElementById('form-produit').reset();
        document.getElementById('produit_id').value = '';
        document.getElementById('produit_est_actif').checked = true;
        toggleStockFields();
    });
</script>
