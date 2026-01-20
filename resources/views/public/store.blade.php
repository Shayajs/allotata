<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Boutique - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite('resources/js/tracking-visite.js')
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Navigation -->
        <nav class="mb-6 flex items-center justify-between">
            <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="font-medium">Retour à {{ $entreprise->nom }}</span>
            </a>
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
        </nav>

        <!-- En-tête -->
        <header class="mb-8">
            <div class="flex items-center gap-4">
                @if($entreprise->logo)
                    <img src="/media/{{ $entreprise->logo }}" alt="{{ $entreprise->nom }}" class="w-16 h-16 rounded-xl object-cover shadow-md">
                @endif
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">
                        Boutique
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->nom }} • Découvrez nos produits</p>
                </div>
            </div>
        </header>

        <!-- Section Produits -->
        @if($produits && $produits->count() > 0)
            <section class="mt-8">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($produits as $produit)
                        @php
                            $imageCouverture = $produit->imageCouverture;
                            $premiereImage = $produit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        @endphp
                        
                        <div 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all cursor-pointer hover:border-green-300 dark:hover:border-green-700 group"
                            onclick="openProduitModal({{ $loop->index }})"
                            data-produit-id="{{ $produit->id }}"
                            data-produit-nom="{{ $produit->nom }}"
                            data-tracking-produit="true"
                        >
                            @if($imageAffichee)
                                <div class="relative h-48 w-full overflow-hidden">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $produit->nom }}"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                    @if($promotion)
                                        <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                            PROMO
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative h-48 w-full bg-gradient-to-br from-green-100 to-orange-100 dark:from-green-900/20 dark:to-orange-900/20 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    @if($promotion)
                                        <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                            PROMO
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="p-4 sm:p-6">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 truncate group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                    {{ $produit->nom }}
                                </h3>
                                
                                @if($produit->description)
                                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 line-clamp-2">
                                        {{ $produit->description }}
                                    </p>
                                @endif
                                
                                <div class="flex flex-col gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            @if($promotion)
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg line-through text-slate-400">{{ number_format($produit->prix, 2, ',', ' ') }} €</span>
                                                    <span class="text-2xl font-bold text-red-600 dark:text-red-400">
                                                        {{ number_format($prixActuel, 2, ',', ' ') }} €
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                                                    {{ number_format($prixActuel, 2, ',', ' ') }} €
                                                </span>
                                            @endif
                                            @if($produit->gestion_stock === 'en_attente_commandes')
                                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">📦 En attente de commandes</span>
                                            @elseif($produit->stock)
                                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">En stock: {{ $produit->stock->quantite_disponible }}</span>
                                            @endif
                                        </div>
                                        <div class="text-green-600 dark:text-green-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    @auth
                                        <a href="{{ route('messagerie.commander-produit', ['slug' => $entreprise->slug, 'produitId' => $produit->id]) }}" onclick="event.stopPropagation();" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                            </svg>
                                            Commander
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}?redirect={{ urlencode(route('public.store', $entreprise->slug)) }}" onclick="event.stopPropagation();" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm">
                                            Se connecter pour commander
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-lg font-medium text-slate-600 dark:text-slate-400 mb-2">Aucun produit disponible pour le moment</p>
                <p class="text-sm text-slate-500 dark:text-slate-500">Revenez plus tard pour découvrir nos produits.</p>
            </div>
        @endif
    </div>

    <!-- Modal détaillé pour un produit (layout deux colonnes comme les services) -->
    <div id="produit-detail-modal" class="hidden fixed inset-0 bg-black/80 z-50 overflow-y-auto" onclick="closeProduitModal(event)">
        <div class="min-h-screen py-4 sm:py-8 px-2 sm:px-4 flex items-start justify-center">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-6xl my-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="flex flex-col lg:flex-row h-[calc(100vh-4rem)] max-h-[800px]">
                    <!-- Colonne gauche : Images (50%) -->
                    <div class="lg:w-1/2 flex flex-col bg-slate-100 dark:bg-slate-900">
                        <button onclick="closeProduitModal()" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        
                        <!-- Galerie d'images principale -->
                        <div id="produit-detail-gallery" class="relative flex-1 bg-slate-200 dark:bg-slate-700">
                            <!-- Image principale -->
                            <img id="produit-detail-image" src="" alt="" class="w-full h-full object-cover">
                            
                            <!-- Placeholder quand pas d'image -->
                            <div id="produit-detail-no-image" class="hidden absolute inset-0 bg-gradient-to-br from-green-100 to-orange-100 dark:from-green-900/20 dark:to-orange-900/20 flex items-center justify-center">
                                <svg class="w-16 h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            
                            <!-- Navigation galerie -->
                            <button onclick="prevProduitDetailImage(event)" class="absolute left-2 top-1/2 -translate-y-1/2 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition hidden" id="produit-detail-prev">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button onclick="nextProduitDetailImage(event)" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition hidden" id="produit-detail-next">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            
                            <!-- Indicateur de position -->
                            <div id="produit-detail-indicator" class="absolute bottom-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-black/60 text-white text-xs sm:text-sm rounded-full hidden"></div>
                        </div>
                        
                        <!-- Miniatures -->
                        <div id="produit-detail-thumbnails" class="flex gap-1.5 sm:gap-2 p-2 sm:p-3 bg-slate-100 dark:bg-slate-900 overflow-x-auto border-t border-slate-200 dark:border-slate-700 hidden"></div>
                    </div>
                    
                    <!-- Colonne droite : Contenu (50%) -->
                    <div class="lg:w-1/2 flex flex-col overflow-y-auto">
                        <div class="p-4 sm:p-6">
                            <h3 id="produit-detail-nom" class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-2"></h3>
                            
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <div class="flex items-center gap-1.5">
                                    <span id="produit-detail-prix" class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400"></span>
                                    <span id="produit-detail-prix-original" class="hidden text-xl line-through text-slate-400"></span>
                                </div>
                                <span class="text-slate-500 dark:text-slate-400 text-sm">•</span>
                                <span id="produit-detail-stock" class="text-slate-600 dark:text-slate-400 text-sm"></span>
                            </div>
                            
                            <div id="produit-detail-description" class="text-slate-600 dark:text-slate-400 text-sm sm:text-base mb-6 whitespace-pre-line"></div>
                            
                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                @auth
                                    <a href="#" id="produit-commander-link-modal" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                        Commander
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                        Connectez-vous pour commander
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentProduitIndex = 0;
        let currentProduitDetailImageIndex = 0;
        const produitsData = [
            @foreach($produits as $produit)
            @php
                $promotion = $produit->promotionActive()->first();
                $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
            @endphp
            {
                id: {{ $produit->id }},
                nom: "{{ addslashes($produit->nom) }}",
                description: "{{ addslashes($produit->description ?? '') }}",
                prix: "{{ number_format($produit->prix, 2, ',', ' ') }}",
                prixActuel: "{{ number_format($prixActuel, 2, ',', ' ') }}",
                aPromotion: {{ $promotion ? 'true' : 'false' }},
                stock: "{{ $produit->gestion_stock === 'en_attente_commandes' ? 'En attente de commandes' : ($produit->stock ? 'En stock: ' . $produit->stock->quantite_disponible : 'Disponible') }}",
                images: [
                    @foreach($produit->images as $image)
                    "{{ asset('media/' . $image->image_path) }}",
                    @endforeach
                ],
            },
            @endforeach
        ];

        function openProduitModal(produitIndex) {
            currentProduitIndex = produitIndex;
            currentProduitDetailImageIndex = 0;
            updateProduitModal();
            updateProduitDetailGallery();
            document.getElementById('produit-detail-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeProduitModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('produit-detail-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function prevProduitDetailImage(event) {
            event.stopPropagation();
            const produit = produitsData[currentProduitIndex];
            if (produit.images.length > 1) {
                currentProduitDetailImageIndex = (currentProduitDetailImageIndex - 1 + produit.images.length) % produit.images.length;
                updateProduitDetailGallery();
            }
        }

        function nextProduitDetailImage(event) {
            event.stopPropagation();
            const produit = produitsData[currentProduitIndex];
            if (produit.images.length > 1) {
                currentProduitDetailImageIndex = (currentProduitDetailImageIndex + 1) % produit.images.length;
                updateProduitDetailGallery();
            }
        }

        function selectProduitDetailImage(index) {
            currentProduitDetailImageIndex = index;
            updateProduitDetailGallery();
        }

        function updateProduitDetailGallery() {
            const produit = produitsData[currentProduitIndex];
            const imageEl = document.getElementById('produit-detail-image');
            const noImageEl = document.getElementById('produit-detail-no-image');
            const prevBtn = document.getElementById('produit-detail-prev');
            const nextBtn = document.getElementById('produit-detail-next');
            const indicator = document.getElementById('produit-detail-indicator');
            const thumbnails = document.getElementById('produit-detail-thumbnails');
            
            if (produit.images && produit.images.length > 0) {
                imageEl.src = produit.images[currentProduitDetailImageIndex];
                imageEl.classList.remove('hidden');
                noImageEl.classList.add('hidden');
                
                // Afficher/masquer les boutons de navigation
                if (produit.images.length > 1) {
                    prevBtn.classList.remove('hidden');
                    nextBtn.classList.remove('hidden');
                    indicator.classList.remove('hidden');
                    indicator.textContent = `${currentProduitDetailImageIndex + 1} / ${produit.images.length}`;
                    
                    // Afficher les miniatures
                    thumbnails.classList.remove('hidden');
                    thumbnails.innerHTML = produit.images.map((img, idx) => `
                        <div class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden cursor-pointer border-2 transition-all ${
                            idx === currentProduitDetailImageIndex 
                                ? 'border-green-500 dark:border-green-400 shadow-md' 
                                : 'border-transparent hover:border-slate-300 dark:hover:border-slate-600'
                        }" onclick="selectProduitDetailImage(${idx})">
                            <img src="${img}" alt="Miniature ${idx + 1}" class="w-full h-full object-cover">
                        </div>
                    `).join('');
                } else {
                    prevBtn.classList.add('hidden');
                    nextBtn.classList.add('hidden');
                    indicator.classList.add('hidden');
                    thumbnails.classList.add('hidden');
                }
            } else {
                imageEl.classList.add('hidden');
                noImageEl.classList.remove('hidden');
                prevBtn.classList.add('hidden');
                nextBtn.classList.add('hidden');
                indicator.classList.add('hidden');
                thumbnails.classList.add('hidden');
            }
        }

        function updateProduitModal() {
            const produit = produitsData[currentProduitIndex];
            
            document.getElementById('produit-detail-nom').textContent = produit.nom;
            document.getElementById('produit-detail-description').textContent = produit.description || 'Aucune description disponible.';
            document.getElementById('produit-detail-stock').textContent = produit.stock;
            
            // Mettre à jour le lien Commander dans la modale
            const commanderLinkModal = document.getElementById('produit-commander-link-modal');
            if (commanderLinkModal) {
                const baseUrl = "{{ route('messagerie.commander-produit', [$entreprise->slug, 'produitId' => 0]) }}";
                commanderLinkModal.href = baseUrl.replace('/0', '/' + produit.id);
            }
            
            if (produit.aPromotion) {
                document.getElementById('produit-detail-prix').textContent = produit.prixActuel + ' €';
                document.getElementById('produit-detail-prix-original').textContent = produit.prix + ' €';
                document.getElementById('produit-detail-prix-original').classList.remove('hidden');
                document.getElementById('produit-detail-prix').classList.remove('text-green-600', 'dark:text-green-400');
                document.getElementById('produit-detail-prix').classList.add('text-red-600', 'dark:text-red-400');
            } else {
                document.getElementById('produit-detail-prix').textContent = produit.prixActuel + ' €';
                document.getElementById('produit-detail-prix-original').classList.add('hidden');
                document.getElementById('produit-detail-prix').classList.remove('text-red-600', 'dark:text-red-400');
                document.getElementById('produit-detail-prix').classList.add('text-green-600', 'dark:text-green-400');
            }
        }

        // Navigation au clavier
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('produit-detail-modal');
            if (!modal.classList.contains('hidden')) {
                if (e.key === 'Escape') {
                    closeProduitModal();
                } else if (e.key === 'ArrowLeft') {
                    prevProduitDetailImage({ stopPropagation: () => {} });
                } else if (e.key === 'ArrowRight') {
                    nextProduitDetailImage({ stopPropagation: () => {} });
                }
            }
        });
    </script>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
