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
                onclick="openProduitModal()"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
            >
                + Ajouter un produit
            </button>
        </div>

        @if($produits && $produits->count() > 0)
            @php
                $produitsCount = $produits->count();
                $showExpandButton = $produitsCount > 10;
                $initialProduits = $produits->take(10);
                $remainingProduits = $produits->skip(10);
            @endphp
            
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" id="produits-list-initial">
                @foreach($initialProduits as $produit)
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
                                            <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
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
                                onclick="editProduitFromButton(this)"
                                data-produit-id="{{ $produit->id }}"
                                data-produit-nom="{{ addslashes($produit->nom) }}"
                                data-produit-description="{{ addslashes($produit->description ?? '') }}"
                                data-produit-prix="{{ $produit->prix }}"
                                data-produit-gestion-stock="{{ $produit->gestion_stock }}"
                                data-produit-quantite-disponible="{{ $produit->stock ? ($produit->stock->quantite_disponible ?? 0) : 0 }}"
                                data-produit-quantite-minimum="{{ $produit->stock ? ($produit->stock->quantite_minimum ?? 0) : 0 }}"
                                data-produit-actif="{{ $produit->est_actif ? 'true' : 'false' }}"
                                data-produit-livraison-disponible="{{ $produit->livraison_disponible !== null ? ($produit->livraison_disponible ? 'true' : 'false') : 'null' }}"
                                data-produit-vente-sur-place-disponible="{{ $produit->vente_sur_place_disponible !== null ? ($produit->vente_sur_place_disponible ? 'true' : 'false') : 'null' }}"
                                data-produit-images="{{ base64_encode(json_encode($produit->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values())) }}"
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
            
            @if($showExpandButton)
                <div id="produits-list-expanded" class="hidden grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-4">
                    @foreach($remainingProduits as $produit)
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
                                                <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
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
                                    onclick="editProduitFromButton(this)"
                                    data-produit-id="{{ $produit->id }}"
                                    data-produit-nom="{{ addslashes($produit->nom) }}"
                                    data-produit-description="{{ addslashes($produit->description ?? '') }}"
                                    data-produit-prix="{{ $produit->prix }}"
                                    data-produit-gestion-stock="{{ $produit->gestion_stock }}"
                                    data-produit-quantite-disponible="{{ $produit->stock ? ($produit->stock->quantite_disponible ?? 0) : 0 }}"
                                    data-produit-quantite-minimum="{{ $produit->stock ? ($produit->stock->quantite_minimum ?? 0) : 0 }}"
                                    data-produit-actif="{{ $produit->est_actif ? 'true' : 'false' }}"
                                    data-produit-livraison-disponible="{{ $produit->livraison_disponible !== null ? ($produit->livraison_disponible ? 'true' : 'false') : 'null' }}"
                                    data-produit-vente-sur-place-disponible="{{ $produit->vente_sur_place_disponible !== null ? ($produit->vente_sur_place_disponible ? 'true' : 'false') : 'null' }}"
                                    data-produit-images="{{ base64_encode(json_encode($produit->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values())) }}"
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
                
                <div class="mt-6 text-center">
                    <button 
                        id="produits-expand-button"
                        onclick="toggleProduitsExpand()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition-all"
                    >
                        <span id="produits-expand-text">Voir plus ({{ $remainingProduits->count() }} autres)</span>
                        <svg id="produits-expand-icon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <script>
                    function toggleProduitsExpand() {
                        const expandedList = document.getElementById('produits-list-expanded');
                        const expandButton = document.getElementById('produits-expand-button');
                        const expandText = document.getElementById('produits-expand-text');
                        const expandIcon = document.getElementById('produits-expand-icon');
                        
                        if (expandedList.classList.contains('hidden')) {
                            expandedList.classList.remove('hidden');
                            expandedList.style.opacity = '0';
                            setTimeout(() => {
                                expandedList.style.transition = 'opacity 0.3s ease-in-out';
                                expandedList.style.opacity = '1';
                            }, 10);
                            expandText.textContent = 'Voir moins';
                            expandIcon.classList.add('rotate-180');
                        } else {
                            expandedList.style.transition = 'opacity 0.3s ease-in-out';
                            expandedList.style.opacity = '0';
                            setTimeout(() => {
                                expandedList.classList.add('hidden');
                            }, 300);
                            expandText.textContent = 'Voir plus ({{ $remainingProduits->count() }} autres)';
                            expandIcon.classList.remove('rotate-180');
                        }
                    }
                </script>
            @endif
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

@include('entreprise.dashboard.tabs.stock-modal-content')
