<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Produits - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <div class="min-h-screen">
        <!-- Navigation mobile/desktop -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="font-medium">Retour</span>
                    </a>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Produits</h1>
                    <button 
                        id="theme-toggle"
                        class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                        aria-label="Basculer le thème"
                    >
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>

        @if($produits && $produits->count() > 0)
            <!-- Mobile: Navigation par onglets -->
            <div class="lg:hidden">
                <div class="flex border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 sticky top-[73px] z-40">
                    <button 
                        id="mobile-tab-liste"
                        onclick="switchMobileTab('liste')"
                        class="flex-1 px-4 py-3 text-sm font-semibold text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        Liste
                    </button>
                    <button 
                        id="mobile-tab-details"
                        onclick="switchMobileTab('details')"
                        class="flex-1 px-4 py-3 text-sm font-semibold text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        Détails
                    </button>
                </div>

                <!-- Onglet Liste (Mobile) -->
                <div id="mobile-content-liste" class="p-4 space-y-4">
                    @foreach($produits as $produit)
                        @php
                            $imageCouverture = $produit->imageCouverture;
                            $premiereImage = $produit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        @endphp
                        <div 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden cursor-pointer hover:shadow-lg transition-all"
                            onclick="selectProduit({{ $produit->id }}, true)"
                            data-produit-id="{{ $produit->id }}"
                        >
                            @if($imageAffichee)
                                <div class="relative h-48 w-full overflow-hidden">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $produit->nom }}"
                                        class="w-full h-full object-cover"
                                    >
                                    @if($promotion)
                                        <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                            PROMO
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $produit->nom }}</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-sm">
                                        @if($promotion)
                                            <div class="flex items-center gap-2">
                                                <span class="line-through text-slate-400 text-xs">{{ number_format($produit->prix, 2, ',', ' ') }} €</span>
                                                <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($prixActuel, 2, ',', ' ') }} €</span>
                                            </div>
                                        @else
                                            <span class="font-bold text-green-600 dark:text-green-400">
                                                {{ number_format($prixActuel, 2, ',', ' ') }} €
                                            </span>
                                        @endif
                                    </div>
                                    @if($produit->gestion_stock === 'disponible_immediatement' && $produit->stock)
                                        <span class="text-xs text-slate-600 dark:text-slate-400">
                                            Stock: {{ $produit->stock->quantite_disponible }}
                                        </span>
                                    @elseif($produit->gestion_stock === 'en_attente_commandes')
                                        <span class="text-xs text-orange-600 dark:text-orange-400">En attente</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Onglet Détails (Mobile) -->
                <div id="mobile-content-details" class="hidden p-4">
                    <div id="mobile-produit-details" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <p class="text-center text-slate-500 dark:text-slate-400">Sélectionnez un produit dans la liste</p>
                    </div>
                </div>
            </div>

            <!-- Desktop: Layout 20/80 -->
            <div class="hidden lg:flex max-w-[1920px] mx-auto h-[calc(100vh-73px)]">
                <!-- Sidebar (20%) -->
                <div class="w-1/5 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-y-auto">
                    <div class="p-4 space-y-4">
                        @foreach($produits as $produit)
                            @php
                                $imageCouverture = $produit->imageCouverture;
                                $premiereImage = $produit->images->first();
                                $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                $promotion = $produit->promotionActive()->first();
                                $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                            @endphp
                            <div 
                                class="produit-card bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 cursor-pointer hover:border-green-500 dark:hover:border-green-600 hover:shadow-lg transition-all {{ $loop->first ? 'border-green-500 dark:border-green-600 shadow-md' : '' }}"
                                onclick="selectProduit({{ $produit->id }}, false)"
                                data-produit-id="{{ $produit->id }}"
                            >
                                @if($imageAffichee)
                                    <div class="relative h-32 w-full rounded-lg overflow-hidden mb-3">
                                        <img 
                                            src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                            alt="{{ $produit->nom }}"
                                            class="w-full h-full object-cover"
                                        >
                                        @if($promotion)
                                            <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                                PROMO
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 line-clamp-2">{{ $produit->nom }}</h3>
                                <div class="flex items-center justify-between text-sm mb-2">
                                    @if($promotion)
                                        <div class="flex items-center gap-2">
                                            <span class="line-through text-slate-400 text-xs">{{ number_format($produit->prix, 2, ',', ' ') }} €</span>
                                            <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($prixActuel, 2, ',', ' ') }} €</span>
                                        </div>
                                    @else
                                        <span class="font-bold text-green-600 dark:text-green-400">
                                            {{ number_format($prixActuel, 2, ',', ' ') }} €
                                        </span>
                                    @endif
                                    @if($produit->gestion_stock === 'disponible_immediatement' && $produit->stock)
                                        <span class="text-xs text-slate-600 dark:text-slate-400">
                                            Stock: {{ $produit->stock->quantite_disponible }}
                                        </span>
                                    @elseif($produit->gestion_stock === 'en_attente_commandes')
                                        <span class="text-xs text-orange-600 dark:text-orange-400">En attente</span>
                                    @endif
                                </div>
                                @if(!$loop->last)
                                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Contenu principal (80%) -->
                <div class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-900">
                    <div id="produit-details" class="p-8">
                        @php
                            $firstProduit = $produits->first();
                            $imageCouverture = $firstProduit->imageCouverture;
                            $premiereImage = $firstProduit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $firstProduit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $firstProduit->prix;
                        @endphp
                        <div class="max-w-4xl mx-auto">
                            <!-- Image principale -->
                            @if($imageAffichee)
                                <div class="relative h-96 w-full rounded-2xl overflow-hidden mb-6 shadow-xl">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $firstProduit->nom }}"
                                        class="w-full h-full object-cover"
                                        id="produit-main-image"
                                    >
                                    @if($promotion)
                                        <div class="absolute top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-full text-sm font-semibold">
                                            PROMOTION
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Titre et prix -->
                            <div class="mb-6">
                                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4" id="produit-title">{{ $firstProduit->nom }}</h1>
                                <div class="flex items-center gap-6 text-lg">
                                    @if($promotion)
                                        <div class="flex items-center gap-3">
                                            <span class="line-through text-slate-400 text-xl">{{ number_format($firstProduit->prix, 2, ',', ' ') }} €</span>
                                            <span class="text-3xl font-bold text-red-600 dark:text-red-400" id="produit-prix">
                                                {{ number_format($prixActuel, 2, ',', ' ') }} €
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-3xl font-bold text-green-600 dark:text-green-400" id="produit-prix">
                                            {{ number_format($prixActuel, 2, ',', ' ') }} €
                                        </span>
                                    @endif
                                    @if($firstProduit->gestion_stock === 'disponible_immediatement' && $firstProduit->stock)
                                        <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            <span id="produit-stock">Stock: {{ $firstProduit->stock->quantite_disponible }}</span>
                                        </span>
                                    @elseif($firstProduit->gestion_stock === 'en_attente_commandes')
                                        <span class="text-orange-600 dark:text-orange-400 flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>En attente de commandes</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Description</h2>
                                <p class="text-slate-700 dark:text-slate-300 text-lg leading-relaxed" id="produit-description">
                                    {{ $firstProduit->description ?: 'Aucune description disponible.' }}
                                </p>
                            </div>

                            <!-- Galerie d'images -->
                            @if($firstProduit->images->count() > 1)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Galerie</h2>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="produit-gallery">
                                        @foreach($firstProduit->images as $image)
                                            <div class="relative h-32 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity" onclick="changeMainImage('{{ asset('media/' . $image->image_path) }}')">
                                                <img 
                                                    src="{{ asset('media/' . $image->image_path) }}" 
                                                    alt="{{ $firstProduit->nom }}"
                                                    class="w-full h-full object-cover"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Photos des clients (avis) -->
                            @php
                                $produitAvis = $firstProduit->produitAvis;
                                $photosAvis = collect();
                                foreach($produitAvis as $avis) {
                                    foreach($avis->photos as $photo) {
                                        $photosAvis->push($photo);
                                    }
                                }
                            @endphp
                            @if($photosAvis->count() > 0)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Photos des clients</h2>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="produit-avis-photos">
                                        @foreach($photosAvis->take(8) as $photo)
                                            <div class="relative h-32 rounded-lg overflow-hidden">
                                                <img 
                                                    src="{{ asset('storage/' . $photo->photo_path) }}" 
                                                    alt="Photo client"
                                                    class="w-full h-full object-cover"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Photos de réalisations -->
                            @if($entreprise->realisationPhotos->count() > 0)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Photos de réalisations</h2>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        @foreach($entreprise->realisationPhotos->take(8) as $photo)
                                            <div class="relative h-32 rounded-lg overflow-hidden">
                                                <img 
                                                    src="{{ asset('storage/' . $photo->photo_path) }}" 
                                                    alt="{{ $photo->titre ?: 'Réalisation' }}"
                                                    class="w-full h-full object-cover"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Avis et notes -->
                            @if($firstProduit->produitAvis->count() > 0)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                                        Avis et notes
                                        <span class="text-lg font-normal text-slate-600 dark:text-slate-400">
                                            ({{ $firstProduit->nombre_avis }} avis, note moyenne: {{ $firstProduit->note_moyenne }}/5)
                                        </span>
                                    </h2>
                                    <div class="space-y-4" id="produit-avis-list">
                                        @foreach($firstProduit->produitAvis->take(5) as $avis)
                                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                                            <span class="text-slate-600 dark:text-slate-400 font-semibold">
                                                                {{ substr($avis->user->name ?? 'A', 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $avis->user->name ?? 'Utilisateur' }}</p>
                                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $avis->created_at->format('d/m/Y') }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <span class="text-yellow-400 {{ $i <= $avis->note ? '' : 'opacity-30' }}">★</span>
                                                        @endfor
                                                    </div>
                                                </div>
                                                @if($avis->commentaire)
                                                    <p class="text-slate-700 dark:text-slate-300 mt-2">{{ $avis->commentaire }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Bouton d'action -->
                            <div class="mt-8">
                                <a 
                                    href="{{ route('messagerie.commander-produit', ['slug' => $entreprise->slug, 'produitId' => $firstProduit->id]) }}"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Commander ce produit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">Aucun produit disponible pour le moment.</p>
            </div>
        @endif
    </div>

    @php
        $produitsDataArray = $produits->map(function($produit) {
            $imageCouverture = $produit->imageCouverture;
            $premiereImage = $produit->images->first();
            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
            $promotion = $produit->promotionActive()->first();
            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
            
            $produitAvis = $produit->produitAvis;
            $photosAvis = collect();
            foreach($produitAvis as $avis) {
                foreach($avis->photos as $photo) {
                    $photosAvis->push($photo);
                }
            }
            
            return [
                'id' => $produit->id,
                'nom' => $produit->nom,
                'description' => $produit->description ?? '',
                'prix' => $produit->prix,
                'prix_actuel' => $prixActuel,
                'a_promotion' => $promotion ? true : false,
                'gestion_stock' => $produit->gestion_stock,
                'stock_quantite' => $produit->stock ? $produit->stock->quantite_disponible : null,
                'images' => $produit->images->map(fn($img) => asset('media/' . $img->image_path))->toArray(),
                'image_principale' => $imageAffichee ? asset('media/' . $imageAffichee->image_path) : null,
                'avis' => $produitAvis->map(function($avis) {
                    return [
                        'user_name' => $avis->user->name ?? 'Utilisateur',
                        'note' => $avis->note,
                        'commentaire' => $avis->commentaire ?? '',
                        'date' => $avis->created_at->format('d/m/Y'),
                    ];
                })->toArray(),
                'nombre_avis' => $produit->nombre_avis,
                'note_moyenne' => $produit->note_moyenne,
                'photos_avis' => $photosAvis->map(fn($p) => asset('storage/' . $p->photo_path))->take(8)->toArray(),
            ];
        })->toArray();
        
        $realisationPhotosArray = $entreprise->realisationPhotos->map(fn($p) => asset('storage/' . $p->photo_path))->take(8)->toArray();
        $commanderProduitBaseUrl = route('messagerie.commander-produit', ['slug' => $entreprise->slug, 'produitId' => 0]);
    @endphp

    <script>
        // Données des produits
        const produitsData = @json($produitsDataArray);

        const realisationPhotos = @json($realisationPhotosArray);
        const commanderProduitBaseUrl = @json($commanderProduitBaseUrl);

        function selectProduit(produitId, isMobile) {
            const produit = produitsData.find(p => p.id === produitId);
            if (!produit) return;

            if (isMobile) {
                // Mobile: Afficher dans l'onglet détails
                switchMobileTab('details');
                updateMobileProduitDetails(produit);
            } else {
                // Desktop: Mettre à jour le contenu principal
                updateDesktopProduitDetails(produit);
                
                // Mettre à jour la sélection dans la sidebar
                document.querySelectorAll('.produit-card').forEach(card => {
                    card.classList.remove('border-green-500', 'dark:border-green-600', 'shadow-md');
                    card.classList.add('border-slate-200', 'dark:border-slate-700');
                });
                const selectedCard = document.querySelector(`[data-produit-id="${produitId}"]`);
                if (selectedCard) {
                    selectedCard.classList.remove('border-slate-200', 'dark:border-slate-700');
                    selectedCard.classList.add('border-green-500', 'dark:border-green-600', 'shadow-md');
                }
            }

            // Mettre à jour l'URL avec le hash
            window.location.hash = `produit-${produitId}`;
        }

        function updateDesktopProduitDetails(produit) {
            document.getElementById('produit-title').textContent = produit.nom;
            
            // Prix
            const prixDiv = document.getElementById('produit-prix').parentElement;
            if (produit.a_promotion) {
                prixDiv.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="line-through text-slate-400 text-xl">${numberFormat(produit.prix, 2, ',', ' ')} €</span>
                        <span class="text-3xl font-bold text-red-600 dark:text-red-400" id="produit-prix">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                    </div>
                `;
            } else {
                prixDiv.innerHTML = `<span class="text-3xl font-bold text-green-600 dark:text-green-400" id="produit-prix">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>`;
            }
            
            // Stock
            const stockSpan = document.getElementById('produit-stock');
            if (stockSpan) {
                if (produit.gestion_stock === 'disponible_immediatement' && produit.stock_quantite !== null) {
                    stockSpan.textContent = `Stock: ${produit.stock_quantite}`;
                } else {
                    stockSpan.textContent = 'En attente de commandes';
                }
            }
            
            document.getElementById('produit-description').textContent = produit.description || 'Aucune description disponible.';

            // Image principale
            if (produit.image_principale) {
                document.getElementById('produit-main-image').src = produit.image_principale;
            }

            // Galerie
            const gallery = document.getElementById('produit-gallery');
            if (gallery && produit.images.length > 1) {
                gallery.innerHTML = produit.images.map((img, idx) => `
                    <div class="relative h-32 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity" onclick="changeMainImage('${img}')">
                        <img src="${img}" alt="${produit.nom}" class="w-full h-full object-cover">
                    </div>
                `).join('');
            }

            // Photos avis
            const avisPhotos = document.getElementById('produit-avis-photos');
            if (avisPhotos && produit.photos_avis.length > 0) {
                avisPhotos.innerHTML = produit.photos_avis.map(photo => `
                    <div class="relative h-32 rounded-lg overflow-hidden">
                        <img src="${photo}" alt="Photo client" class="w-full h-full object-cover">
                    </div>
                `).join('');
            }

            // Avis
            const avisList = document.getElementById('produit-avis-list');
            if (avisList) {
                if (produit.avis.length > 0) {
                    avisList.innerHTML = produit.avis.map(avis => `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                        <span class="text-slate-600 dark:text-slate-400 font-semibold">${avis.user_name.charAt(0)}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">${avis.user_name}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">${avis.date}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    ${Array.from({length: 5}, (_, i) => 
                                        `<span class="text-yellow-400 ${i < avis.note ? '' : 'opacity-30'}">★</span>`
                                    ).join('')}
                                </div>
                            </div>
                            ${avis.commentaire ? `<p class="text-slate-700 dark:text-slate-300 mt-2">${avis.commentaire}</p>` : ''}
                        </div>
                    `).join('');
                } else {
                    avisList.innerHTML = '<p class="text-slate-500 dark:text-slate-400">Aucun avis pour le moment.</p>';
                }
            }
        }

        function updateMobileProduitDetails(produit) {
            const detailsDiv = document.getElementById('mobile-produit-details');
            detailsDiv.innerHTML = `
                ${produit.image_principale ? `
                    <div class="relative h-64 w-full rounded-xl overflow-hidden mb-4">
                        <img src="${produit.image_principale}" alt="${produit.nom}" class="w-full h-full object-cover">
                        ${produit.a_promotion ? '<div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">PROMO</div>' : ''}
                    </div>
                ` : ''}
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">${produit.nom}</h2>
                <div class="flex items-center gap-4 mb-4">
                    ${produit.a_promotion ? `
                        <div class="flex items-center gap-2">
                            <span class="line-through text-slate-400">${numberFormat(produit.prix, 2, ',', ' ')} €</span>
                            <span class="text-xl font-bold text-red-600 dark:text-red-400">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                        </div>
                    ` : `
                        <span class="text-xl font-bold text-green-600 dark:text-green-400">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                    `}
                    ${produit.gestion_stock === 'disponible_immediatement' && produit.stock_quantite !== null ? `
                        <span class="text-sm text-slate-600 dark:text-slate-400">Stock: ${produit.stock_quantite}</span>
                    ` : produit.gestion_stock === 'en_attente_commandes' ? `
                        <span class="text-sm text-orange-600 dark:text-orange-400">En attente</span>
                    ` : ''}
                </div>
                <p class="text-slate-700 dark:text-slate-300 mb-6">${produit.description || 'Aucune description disponible.'}</p>
                ${produit.images.length > 1 ? `
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Galerie</h3>
                        <div class="grid grid-cols-3 gap-2">
                            ${produit.images.map(img => `
                                <div class="relative h-24 rounded-lg overflow-hidden">
                                    <img src="${img}" alt="${produit.nom}" class="w-full h-full object-cover">
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                <a href="${commanderProduitBaseUrl.replace('/0', '/' + produit.id)}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all">
                    Commander ce produit
                </a>
            `;
        }

        function switchMobileTab(tab) {
            const listeTab = document.getElementById('mobile-tab-liste');
            const detailsTab = document.getElementById('mobile-tab-details');
            const listeContent = document.getElementById('mobile-content-liste');
            const detailsContent = document.getElementById('mobile-content-details');

            if (tab === 'liste') {
                listeTab.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
                listeTab.classList.remove('border-transparent', 'text-slate-600', 'dark:text-slate-400');
                detailsTab.classList.remove('border-green-500', 'text-green-600', 'dark:text-green-400');
                detailsTab.classList.add('border-transparent', 'text-slate-600', 'dark:text-slate-400');
                listeContent.classList.remove('hidden');
                detailsContent.classList.add('hidden');
            } else {
                detailsTab.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
                detailsTab.classList.remove('border-transparent', 'text-slate-600', 'dark:text-slate-400');
                listeTab.classList.remove('border-green-500', 'text-green-600', 'dark:text-green-400');
                listeTab.classList.add('border-transparent', 'text-slate-600', 'dark:text-slate-400');
                detailsContent.classList.remove('hidden');
                listeContent.classList.add('hidden');
            }
        }

        function changeMainImage(imageSrc) {
            document.getElementById('produit-main-image').src = imageSrc;
        }

        function numberFormat(number, decimals, decPoint, thousandsSep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            const n = !isFinite(+number) ? 0 : +number;
            const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
            const sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep;
            const dec = (typeof decPoint === 'undefined') ? '.' : decPoint;
            let s = '';
            const toFixedFix = function(n, prec) {
                const k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }

        // Gérer le hash dans l'URL au chargement
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#produit-')) {
                const produitId = parseInt(hash.replace('#produit-', ''));
                selectProduit(produitId, window.innerWidth < 1024);
            }
        });

        // Gérer le hash lors du changement
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#produit-')) {
                const produitId = parseInt(hash.replace('#produit-', ''));
                selectProduit(produitId, window.innerWidth < 1024);
            }
        });
    </script>
</body>
</html>
