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

        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <p class="text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-300">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

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
                    @php
                        $produitsPrincipaux = $produits->take(9);
                        $produitsRestants = $produits->skip(9);
                    @endphp
                    @foreach($produitsPrincipaux as $produit)
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
                    
                    @if($produitsRestants->count() > 0)
                        <!-- Menu déroulant pour les produits restants -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <button 
                                onclick="toggleProduitsRestants()"
                                class="w-full px-4 py-3 flex items-center justify-between text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                            >
                                <span class="font-semibold">Voir {{ $produitsRestants->count() }} autre(s) produit(s)</span>
                                <svg id="produits-restants-arrow" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="produits-restants-list" class="hidden space-y-4 p-4 border-t border-slate-200 dark:border-slate-700">
                                @foreach($produitsRestants as $produit)
                                    @php
                                        $imageCouverture = $produit->imageCouverture;
                                        $premiereImage = $produit->images->first();
                                        $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                        $promotion = $produit->promotionActive()->first();
                                        $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                                    @endphp
                                    <div 
                                        class="bg-slate-50 dark:bg-slate-700/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden cursor-pointer hover:shadow-lg transition-all"
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
                        </div>
                    @endif
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
                        @php
                            $produitsPrincipauxDesktop = $produits->take(9);
                            $produitsRestantsDesktop = $produits->skip(9);
                        @endphp
                        @foreach($produitsPrincipauxDesktop as $produit)
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
                        
                        @if($produitsRestantsDesktop->count() > 0)
                            <!-- Menu déroulant pour les produits restants (Desktop) -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 overflow-hidden">
                                <button 
                                    onclick="toggleProduitsRestantsDesktop()"
                                    class="w-full px-4 py-3 flex items-center justify-between text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="font-semibold text-sm">Voir {{ $produitsRestantsDesktop->count() }} autre(s)</span>
                                    <svg id="produits-restants-desktop-arrow" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="produits-restants-desktop-list" class="hidden space-y-4 p-4 border-t border-slate-200 dark:border-slate-700">
                                    @foreach($produitsRestantsDesktop as $produit)
                                        @php
                                            $imageCouverture = $produit->imageCouverture;
                                            $premiereImage = $produit->images->first();
                                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                            $promotion = $produit->promotionActive()->first();
                                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                                        @endphp
                                        <div 
                                            class="produit-card bg-slate-50 dark:bg-slate-700/50 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 cursor-pointer hover:border-green-500 dark:hover:border-green-600 hover:shadow-lg transition-all"
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
                        @endif
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
                            <!-- Carousel d'images -->
                            @if($firstProduit->images->count() > 0)
                                <div class="relative mb-6 group" id="produit-image-carousel-container">
                                    <div class="relative h-96 w-full rounded-2xl overflow-hidden shadow-xl bg-slate-200 dark:bg-slate-700">
                                        <!-- Image principale -->
                                        <div class="relative w-full h-full" id="produit-carousel-wrapper">
                                            @foreach($firstProduit->images as $index => $image)
                                                <img 
                                                    src="{{ asset('media/' . $image->image_path) }}" 
                                                    alt="{{ $firstProduit->nom }}"
                                                    class="produit-carousel-image w-full h-full object-cover transition-opacity duration-500 {{ $index === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0' }}"
                                                    data-index="{{ $index }}"
                                                    onclick="openLightboxProduit({{ $index }})"
                                                    style="cursor: {{ $firstProduit->images->count() > 1 ? 'zoom-in' : 'default' }}"
                                                >
                                            @endforeach
                                        </div>

                                        <!-- Badge nombre d'images (si plusieurs) -->
                                        @if($firstProduit->images->count() > 1)
                                            <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-sm font-semibold flex items-center gap-2 z-10">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span id="produit-image-counter">1 / {{ $firstProduit->images->count() }}</span>
                                            </div>
                                        @endif

                                        <!-- Badge promotion -->
                                        @if($promotion)
                                            <div class="absolute top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-full text-sm font-semibold z-10 shadow-lg">
                                                PROMOTION
                                            </div>
                                        @endif

                                        <!-- Boutons navigation (si plusieurs images) -->
                                        @if($firstProduit->images->count() > 1)
                                            <button 
                                                onclick="previousImageProduit()" 
                                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all opacity-0 group-hover:opacity-100 z-10 shadow-lg"
                                                aria-label="Image précédente"
                                            >
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                </svg>
                                            </button>
                                            <button 
                                                onclick="nextImageProduit()" 
                                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all opacity-0 group-hover:opacity-100 z-10 shadow-lg"
                                                aria-label="Image suivante"
                                            >
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>

                                            <!-- Indicateurs de position (points) -->
                                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                                @foreach($firstProduit->images as $index => $image)
                                                    <button 
                                                        onclick="goToImageProduit({{ $index }})"
                                                        class="produit-carousel-dot w-2 h-2 rounded-full transition-all {{ $index === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75' }}"
                                                        aria-label="Image {{ $index + 1 }}"
                                                    ></button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Miniatures (si plusieurs images) -->
                                    @if($firstProduit->images->count() > 1)
                                        <div class="mt-4 flex gap-2 overflow-x-auto pb-2" id="produit-thumbnails" style="scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.3) transparent;">
                                            @foreach($firstProduit->images as $index => $image)
                                                <button 
                                                    onclick="goToImageProduit({{ $index }})"
                                                    class="produit-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all {{ $index === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400' }}"
                                                    data-index="{{ $index }}"
                                                >
                                                    <img 
                                                        src="{{ asset('media/' . $image->image_path) }}" 
                                                        alt="{{ $firstProduit->nom }}"
                                                        class="w-full h-full object-cover"
                                                    >
                                                </button>
                                            @endforeach
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
                            @php
                                $avisTries = $firstProduit->avis_tries;
                                $userAvis = Auth::check() ? \App\Models\ProduitAvis::where('user_id', Auth::id())->where('produit_id', $firstProduit->id)->first() : null;
                                $userReservationsPayees = Auth::check() ? \App\Models\Reservation::where('user_id', Auth::id())
                                    ->where('entreprise_id', $entreprise->id)
                                    ->where('est_paye', true)
                                    ->get() : collect();
                            @endphp
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                                    Avis et notes
                                    <span class="text-lg font-normal text-slate-600 dark:text-slate-400">
                                        ({{ $firstProduit->nombre_avis }} avis, note moyenne: {{ $firstProduit->note_moyenne }}/5)
                                    </span>
                                </h2>

                                <!-- Formulaire pour laisser un avis -->
                                @auth
                                    @if(!$userAvis)
                                        <div class="mb-6 bg-slate-50 dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Laisser un avis</h3>
                                            <form action="{{ route('public.produit.avis.store', ['slug' => $entreprise->slug, 'produitId' => $firstProduit->id]) }}" method="POST">
                                                @csrf
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Note *</label>
                                                    <div class="flex gap-2" id="note-stars-produit">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <button type="button" onclick="setNoteProduit({{ $i }})" class="star-btn-produit text-3xl text-slate-300 dark:text-slate-600 hover:text-yellow-400 transition-colors" data-note="{{ $i }}">☆</button>
                                                        @endfor
                                                    </div>
                                                    <input type="hidden" name="note" id="note-input-produit" required>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Commentaire</label>
                                                    <textarea name="commentaire" rows="3" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="Partagez votre expérience..."></textarea>
                                                </div>
                                                @if($userReservationsPayees->count() > 0)
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Lier à une réservation payée (optionnel)</label>
                                                        <select name="reservation_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                                            <option value="">Aucune réservation</option>
                                                            @foreach($userReservationsPayees as $reservation)
                                                                <option value="{{ $reservation->id }}">Réservation du {{ $reservation->date_reservation->format('d/m/Y') }} - {{ number_format($reservation->prix, 2, ',', ' ') }} €</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endif
                                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                                    Publier mon avis
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @else
                                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-blue-800 dark:text-blue-300">
                                            <a href="{{ route('login') }}" class="font-semibold underline">Connectez-vous</a> pour laisser un avis
                                        </p>
                                    </div>
                                @endauth

                                @if($avisTries->count() > 0)
                                    <div class="space-y-4" id="produit-avis-list">
                                        @foreach($avisTries as $avis)
                                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 {{ $avis->aPaiementConfirme() ? 'ring-2 ring-green-500 dark:ring-green-600' : '' }}">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                                            <span class="text-slate-600 dark:text-slate-400 font-semibold">
                                                                {{ substr($avis->user->name ?? 'A', 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <p class="font-semibold text-slate-900 dark:text-white">{{ $avis->user->name ?? 'Utilisateur' }}</p>
                                                                @if($avis->aPaiementConfirme())
                                                                    <span class="px-2 py-0.5 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                                                                        ✓ Paiement confirmé
                                                                    </span>
                                                                @endif
                                                            </div>
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
                                @else
                                    <p class="text-slate-500 dark:text-slate-400">Aucun avis pour le moment.</p>
                                @endif
                            </div>

                            <!-- Formulaire de commande -->
                            <div class="mt-8">
                                @auth
                                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Commander ce produit</h3>
                                        <form action="{{ route('public.commande-produit.store', $entreprise->slug) }}" method="POST" id="commande-produit-form">
                                            @csrf
                                            <input type="hidden" name="produit_id" value="{{ $firstProduit->id }}">
                                            
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité *</label>
                                                    <input 
                                                        type="number" 
                                                        name="quantite" 
                                                        id="commande_quantite"
                                                        min="1"
                                                        value="1"
                                                        required
                                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                        onchange="updatePrixTotal()"
                                                    >
                                                </div>

                                                @php
                                                    $livraisonDispo = $firstProduit->livraisonDisponible();
                                                    $ventePlaceDispo = $firstProduit->venteSurPlaceDisponible();
                                                    $promotion = $firstProduit->promotionActive()->first();
                                                    $prixUnitaire = $promotion ? $promotion->prix_promotion : $firstProduit->prix;
                                                @endphp

                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Mode de réception *</label>
                                                    <div class="space-y-2">
                                                        @if($livraisonDispo)
                                                            <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                                                <input type="radio" name="mode_livraison" value="livraison" class="w-5 h-5 text-green-600" onchange="toggleLivraisonFields()">
                                                                <div class="flex-1">
                                                                    <span class="font-medium text-slate-900 dark:text-white">Livraison</span>
                                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Livraison à votre adresse</p>
                                                                </div>
                                                            </label>
                                                        @endif
                                                        @if($ventePlaceDispo)
                                                            <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                                                <input type="radio" name="mode_livraison" value="vente_sur_place" class="w-5 h-5 text-green-600" onchange="toggleLivraisonFields()">
                                                                <div class="flex-1">
                                                                    <span class="font-medium text-slate-900 dark:text-white">Vente sur place</span>
                                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Retrait sur place</p>
                                                                </div>
                                                            </label>
                                                        @endif
                                                        <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                                            <input type="radio" name="mode_livraison" value="a_discuter" checked class="w-5 h-5 text-green-600" onchange="toggleLivraisonFields()">
                                                            <div class="flex-1">
                                                                <span class="font-medium text-slate-900 dark:text-white">À discuter</span>
                                                                <p class="text-xs text-slate-500 dark:text-slate-400">Nous discuterons ensemble</p>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div id="livraison-fields" class="hidden space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Adresse de livraison *</label>
                                                        <input 
                                                            type="text" 
                                                            name="adresse_livraison" 
                                                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            placeholder="Numéro et nom de rue"
                                                        >
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Code postal *</label>
                                                            <input 
                                                                type="text" 
                                                                name="code_postal_livraison" 
                                                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Ville *</label>
                                                            <input 
                                                                type="text" 
                                                                name="ville_livraison" 
                                                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date de livraison souhaitée (optionnel)</label>
                                                        <input 
                                                            type="date" 
                                                            name="date_livraison_souhaitee" 
                                                            min="{{ date('Y-m-d') }}"
                                                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                        >
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                                                    <textarea 
                                                        name="notes" 
                                                        rows="3"
                                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                        placeholder="Informations supplémentaires..."
                                                    ></textarea>
                                                </div>

                                                <div class="bg-white dark:bg-slate-700 rounded-lg p-4 border border-slate-200 dark:border-slate-600">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-slate-600 dark:text-slate-400">Prix unitaire :</span>
                                                        <span class="font-semibold text-slate-900 dark:text-white">
                                                            @if($promotion)
                                                                <span class="line-through text-slate-400 text-sm mr-2">{{ number_format($firstProduit->prix, 2, ',', ' ') }} €</span>
                                                                <span class="text-red-600 dark:text-red-400">{{ number_format($prixUnitaire, 2, ',', ' ') }} €</span>
                                                            @else
                                                                {{ number_format($prixUnitaire, 2, ',', ' ') }} €
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-lg font-bold text-slate-900 dark:text-white">Total :</span>
                                                        <span id="prix-total" class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($prixUnitaire, 2, ',', ' ') }} €</span>
                                                    </div>
                                                </div>

                                                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                                    Passer la commande
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @else
                                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-blue-800 dark:text-blue-300 mb-4">
                                            <a href="{{ route('login') }}" class="font-semibold underline">Connectez-vous</a> pour commander ce produit
                                        </p>
                                        <a 
                                            href="{{ route('messagerie.commander-produit', ['slug' => $entreprise->slug, 'produitId' => $firstProduit->id]) }}"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                        >
                                            Ou discuter avec l'entreprise
                                        </a>
                                    </div>
                                @endauth
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
                'livraison_disponible' => $produit->livraisonDisponible(),
                'vente_sur_place_disponible' => $produit->venteSurPlaceDisponible(),
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
                mobileProduitImagesData = produit.images || [];
                currentMobileProduitImageIndex = 0;
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

            // Mettre à jour le carousel
            updateProduitCarousel(produit);

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

            // Mettre à jour le carousel d'images
            updateProduitCarousel(produit);

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

            // Mettre à jour le formulaire de commande
            updateCommandeForm(produit);
        }

        function updateCommandeForm(produit) {
            const formContainer = document.getElementById('commande-form-container');
            if (!formContainer) return;

            const livraisonDispo = produit.livraison_disponible !== undefined ? produit.livraison_disponible : true;
            const ventePlaceDispo = produit.vente_sur_place_disponible !== undefined ? produit.vente_sur_place_disponible : true;

            formContainer.innerHTML = `
                @auth
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Commander ce produit</h3>
                        <form action="{{ route('public.commande-produit.store', $entreprise->slug) }}" method="POST" id="commande-produit-form-dynamic">
                            @csrf
                            <input type="hidden" name="produit_id" value="${produit.id}">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité *</label>
                                    <input 
                                        type="number" 
                                        name="quantite" 
                                        id="commande_quantite_dynamic"
                                        min="1"
                                        value="1"
                                        required
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        onchange="updatePrixTotalDynamic()"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Mode de réception *</label>
                                    <div class="space-y-2">
                                        ${livraisonDispo ? `
                                            <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                                <input type="radio" name="mode_livraison" value="livraison" class="w-5 h-5 text-green-600" onchange="toggleLivraisonFieldsDynamic()">
                                                <div class="flex-1">
                                                    <span class="font-medium text-slate-900 dark:text-white">Livraison</span>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Livraison à votre adresse</p>
                                                </div>
                                            </label>
                                        ` : ''}
                                        ${ventePlaceDispo ? `
                                            <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                                <input type="radio" name="mode_livraison" value="vente_sur_place" class="w-5 h-5 text-green-600" onchange="toggleLivraisonFieldsDynamic()">
                                                <div class="flex-1">
                                                    <span class="font-medium text-slate-900 dark:text-white">Vente sur place</span>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Retrait sur place</p>
                                                </div>
                                            </label>
                                        ` : ''}
                                        <label class="flex items-center gap-3 p-3 border-2 border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition-colors">
                                            <input type="radio" name="mode_livraison" value="a_discuter" checked class="w-5 h-5 text-green-600" onchange="toggleLivraisonFieldsDynamic()">
                                            <div class="flex-1">
                                                <span class="font-medium text-slate-900 dark:text-white">À discuter</span>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">Nous discuterons ensemble</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="livraison-fields-dynamic" class="hidden space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Adresse de livraison *</label>
                                        <input 
                                            type="text" 
                                            name="adresse_livraison" 
                                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            placeholder="Numéro et nom de rue"
                                        >
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Code postal *</label>
                                            <input 
                                                type="text" 
                                                name="code_postal_livraison" 
                                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Ville *</label>
                                            <input 
                                                type="text" 
                                                name="ville_livraison" 
                                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date de livraison souhaitée (optionnel)</label>
                                        <input 
                                            type="date" 
                                            name="date_livraison_souhaitee" 
                                            min="${new Date().toISOString().split('T')[0]}"
                                            class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                                    <textarea 
                                        name="notes" 
                                        rows="3"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        placeholder="Informations supplémentaires..."
                                    ></textarea>
                                </div>

                                <div class="bg-white dark:bg-slate-700 rounded-lg p-4 border border-slate-200 dark:border-slate-600">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-slate-600 dark:text-slate-400">Prix unitaire :</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">
                                            ${produit.a_promotion ? `
                                                <span class="line-through text-slate-400 text-sm mr-2">${numberFormat(produit.prix, 2, ',', ' ')} €</span>
                                                <span class="text-red-600 dark:text-red-400" id="prix-unitaire-display-dynamic">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                                            ` : `
                                                <span id="prix-unitaire-display-dynamic">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                                            `}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-bold text-slate-900 dark:text-white">Total :</span>
                                        <span id="prix-total-dynamic" class="text-xl font-bold text-green-600 dark:text-green-400">${numberFormat(produit.prix_actuel, 2, ',', ' ')} €</span>
                                    </div>
                                </div>

                                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                    Passer la commande
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-300 mb-4">
                            <a href="{{ route('login') }}" class="font-semibold underline">Connectez-vous</a> pour commander ce produit
                        </p>
                        <a 
                            href="${commanderProduitBaseUrl.replace('/0', '/' + produit.id)}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                        >
                            Ou discuter avec l'entreprise
                        </a>
                    </div>
                @endauth
            `;

            // Réinitialiser les fonctions
            toggleLivraisonFieldsDynamic();
        }

        function toggleLivraisonFieldsDynamic() {
            const modeLivraison = document.querySelector('#commande-form-container input[name="mode_livraison"]:checked')?.value;
            const livraisonFields = document.getElementById('livraison-fields-dynamic');
            
            if (livraisonFields) {
                if (modeLivraison === 'livraison') {
                    livraisonFields.classList.remove('hidden');
                    livraisonFields.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                        input.required = true;
                    });
                } else {
                    livraisonFields.classList.add('hidden');
                    livraisonFields.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                        input.required = false;
                        input.value = '';
                    });
                }
            }
        }

        function updatePrixTotalDynamic() {
            const quantite = parseInt(document.getElementById('commande_quantite_dynamic')?.value || 1);
            const prixUnitaireText = document.getElementById('prix-unitaire-display-dynamic')?.textContent || '0 €';
            const prixUnitaire = parseFloat(prixUnitaireText.replace(' €', '').replace(',', '.')) || 0;
            const total = quantite * prixUnitaire;
            const prixTotalEl = document.getElementById('prix-total-dynamic');
            if (prixTotalEl) {
                prixTotalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
            }
        }

        function updateMobileProduitDetails(produit) {
            const detailsDiv = document.getElementById('mobile-produit-details');
            const hasMultipleImages = produit.images && produit.images.length > 1;
            
            detailsDiv.innerHTML = `
                ${produit.images && produit.images.length > 0 ? `
                    <div class="relative mb-6 group" id="mobile-produit-image-carousel-container">
                        <div class="relative h-80 w-full rounded-2xl overflow-hidden shadow-xl bg-slate-200 dark:bg-slate-700">
                            <div class="relative w-full h-full" id="mobile-produit-carousel-wrapper">
                                ${produit.images.map((img, idx) => `
                                    <img 
                                        src="${img}" 
                                        alt="${produit.nom}"
                                        class="mobile-produit-carousel-image w-full h-full object-cover transition-opacity duration-500 ${idx === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0'}"
                                        data-index="${idx}"
                                        onclick="openLightboxProduit(${idx})"
                                        style="cursor: ${hasMultipleImages ? 'zoom-in' : 'default'}"
                                    >
                                `).join('')}
                            </div>
                            ${hasMultipleImages ? `
                                <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-sm font-semibold flex items-center gap-2 z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span id="mobile-produit-image-counter">1 / ${produit.images.length}</span>
                                </div>
                                <button onclick="previousMobileImageProduit()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all z-10 shadow-lg" aria-label="Image précédente">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button onclick="nextMobileImageProduit()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all z-10 shadow-lg" aria-label="Image suivante">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                    ${produit.images.map((img, idx) => `
                                        <button onclick="goToMobileImageProduit(${idx})" class="mobile-produit-carousel-dot w-2 h-2 rounded-full transition-all ${idx === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'}" aria-label="Image ${idx + 1}"></button>
                                    `).join('')}
                                </div>
                            ` : ''}
                            ${produit.a_promotion ? '<div class="absolute top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-full text-sm font-semibold z-10 shadow-lg">PROMOTION</div>' : ''}
                        </div>
                        ${hasMultipleImages ? `
                            <div class="mt-4 flex gap-2 overflow-x-auto pb-2" id="mobile-produit-thumbnails" style="scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.3) transparent;">
                                ${produit.images.map((img, idx) => `
                                    <button onclick="goToMobileImageProduit(${idx})" class="mobile-produit-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all ${idx === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400'}" data-index="${idx}">
                                        <img src="${img}" alt="${produit.nom}" class="w-full h-full object-cover">
                                    </button>
                                `).join('')}
                            </div>
                        ` : ''}
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
                <!-- Formulaire de commande (sera mis à jour dynamiquement) -->
                <div id="commande-form-container">
                    <!-- Le formulaire sera injecté ici via updateCommandeForm() -->
                </div>
            `;
            
            // Initialiser les gestes tactiles pour le carrousel mobile
            if (hasMultipleImages) {
                initMobileProduitSwipe();
            }
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

        // Variables pour le carousel produit
        let currentProduitImageIndex = 0;
        let produitImagesData = [];

        function updateProduitCarousel(produit) {
            produitImagesData = produit.images || [];
            currentProduitImageIndex = 0;
            
            const container = document.getElementById('produit-image-carousel-container');
            if (!container) {
                console.warn('Conteneur du carousel non trouvé');
                return;
            }

            // Si pas d'images, cacher le conteneur
            if (produitImagesData.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';

            // Mettre à jour le wrapper d'images
            const wrapper = document.getElementById('produit-carousel-wrapper');
            if (wrapper) {
                wrapper.innerHTML = produitImagesData.map((img, idx) => `
                    <img 
                        src="${img}" 
                        alt="${produit.nom}"
                        class="produit-carousel-image w-full h-full object-cover transition-opacity duration-500 ${idx === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0'}"
                        data-index="${idx}"
                        onclick="openLightboxProduit(${idx})"
                        style="cursor: ${produitImagesData.length > 1 ? 'zoom-in' : 'default'}"
                    >
                `).join('');
            }

            // Mettre à jour le compteur
            const counter = document.getElementById('produit-image-counter');
            if (counter) {
                if (produitImagesData.length > 1) {
                    counter.textContent = `1 / ${produitImagesData.length}`;
                    counter.style.display = 'block';
                } else {
                    counter.style.display = 'none';
                }
            }

            // Mettre à jour les indicateurs (dots) - les recréer complètement
            // Chercher le conteneur des dots - il est dans un div avec les classes absolute bottom-4
            let dotsContainer = container.querySelector('div.absolute.bottom-4');
            if (!dotsContainer) {
                // Chercher par sélecteur plus large
                dotsContainer = container.querySelector('div[class*="bottom-4"]');
            }
            if (!dotsContainer) {
                // Chercher tous les divs et trouver celui qui contient les dots
                const allDivs = container.querySelectorAll('div');
                for (let div of allDivs) {
                    if (div.classList.contains('absolute') && (div.classList.contains('bottom-4') || div.textContent.includes('Image'))) {
                        dotsContainer = div;
                        break;
                    }
                }
            }
            if (!dotsContainer && produitImagesData.length > 1) {
                // Créer le conteneur des dots s'il n'existe pas
                const groupDiv = container.querySelector('.group');
                if (groupDiv) {
                    dotsContainer = document.createElement('div');
                    dotsContainer.className = 'absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10';
                    groupDiv.appendChild(dotsContainer);
                }
            }
            if (dotsContainer) {
                if (produitImagesData.length > 1) {
                    dotsContainer.innerHTML = produitImagesData.map((img, idx) => `
                        <button 
                            onclick="goToImageProduit(${idx})"
                            class="produit-carousel-dot w-2 h-2 rounded-full transition-all ${idx === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'}"
                            aria-label="Image ${idx + 1}"
                        ></button>
                    `).join('');
                    dotsContainer.style.display = 'flex';
                } else {
                    dotsContainer.style.display = 'none';
                }
            }

            // Mettre à jour les miniatures
            const thumbnails = document.getElementById('produit-thumbnails');
            if (thumbnails) {
                if (produitImagesData.length > 1) {
                    thumbnails.innerHTML = produitImagesData.map((img, idx) => `
                        <button 
                            onclick="goToImageProduit(${idx})"
                            class="produit-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all ${idx === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400'}"
                            data-index="${idx}"
                        >
                            <img src="${img}" alt="${produit.nom}" class="w-full h-full object-cover">
                        </button>
                    `).join('');
                    thumbnails.style.display = 'flex';
                } else {
                    thumbnails.style.display = 'none';
                }
            }

            // Mettre à jour les boutons de navigation
            const prevBtn = container.querySelector('button[onclick="previousImageProduit()"]');
            const nextBtn = container.querySelector('button[onclick="nextImageProduit()"]');
            if (prevBtn && nextBtn) {
                if (produitImagesData.length > 1) {
                    prevBtn.style.display = 'block';
                    nextBtn.style.display = 'block';
                } else {
                    prevBtn.style.display = 'none';
                    nextBtn.style.display = 'none';
                }
            }
        }

        function goToImageProduit(index) {
            if (produitImagesData.length === 0 || index < 0 || index >= produitImagesData.length) return;
            
            currentProduitImageIndex = index;
            showImageProduit(index);
        }

        function previousImageProduit() {
            if (produitImagesData.length === 0) return;
            currentProduitImageIndex = (currentProduitImageIndex - 1 + produitImagesData.length) % produitImagesData.length;
            showImageProduit(currentProduitImageIndex);
        }

        function nextImageProduit() {
            if (produitImagesData.length === 0) return;
            currentProduitImageIndex = (currentProduitImageIndex + 1) % produitImagesData.length;
            showImageProduit(currentProduitImageIndex);
        }

        function showImageProduit(index) {
            const images = document.querySelectorAll('.produit-carousel-image');
            const dots = document.querySelectorAll('.produit-carousel-dot');
            const thumbnails = document.querySelectorAll('.produit-thumbnail');
            const counter = document.getElementById('produit-image-counter');

            // Mettre à jour les images
            images.forEach((img, idx) => {
                if (idx === index) {
                    img.classList.remove('opacity-0', 'absolute');
                    img.classList.add('opacity-100');
                } else {
                    img.classList.remove('opacity-100');
                    img.classList.add('opacity-0', 'absolute');
                }
            });

            // Mettre à jour les indicateurs
            dots.forEach((dot, idx) => {
                if (idx === index) {
                    dot.classList.remove('bg-white/50', 'w-2');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });

            // Mettre à jour les miniatures
            thumbnails.forEach((thumb, idx) => {
                if (idx === index) {
                    thumb.classList.remove('border-slate-200', 'dark:border-slate-600');
                    thumb.classList.add('border-green-500', 'ring-2', 'ring-green-500');
                } else {
                    thumb.classList.remove('border-green-500', 'ring-2', 'ring-green-500');
                    thumb.classList.add('border-slate-200', 'dark:border-slate-600');
                }
            });

            // Mettre à jour le compteur
            if (counter) {
                counter.textContent = `${index + 1} / ${produitImagesData.length}`;
            }
        }

        function updateProduitCarouselIndicators() {
            const dots = document.querySelectorAll('.produit-carousel-dot');
            dots.forEach((dot, idx) => {
                if (idx === currentProduitImageIndex) {
                    dot.classList.remove('bg-white/50', 'w-2');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });
        }

        // Lightbox pour voir les images en grand
        function openLightboxProduit(index) {
            if (produitImagesData.length === 0) return;
            currentProduitImageIndex = index;
            const lightbox = document.getElementById('produit-lightbox');
            if (lightbox) {
                lightbox.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                showLightboxImageProduit(index);
            }
        }

        function closeLightboxProduit() {
            const lightbox = document.getElementById('produit-lightbox');
            if (lightbox) {
                lightbox.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function showLightboxImageProduit(index) {
            const lightboxImage = document.getElementById('produit-lightbox-image');
            const lightboxCounter = document.getElementById('produit-lightbox-counter');
            
            if (lightboxImage && produitImagesData[index]) {
                lightboxImage.src = produitImagesData[index];
            }
            
            if (lightboxCounter) {
                lightboxCounter.textContent = `${index + 1} / ${produitImagesData.length}`;
            }
        }

        // Navigation au clavier
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('produit-lightbox');
            if (lightbox && !lightbox.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    previousLightboxImageProduit();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextLightboxImageProduit();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    closeLightboxProduit();
                }
            }
        });

        function previousLightboxImageProduit() {
            currentProduitImageIndex = (currentProduitImageIndex - 1 + produitImagesData.length) % produitImagesData.length;
            showLightboxImageProduit(currentProduitImageIndex);
        }

        function nextLightboxImageProduit() {
            currentProduitImageIndex = (currentProduitImageIndex + 1) % produitImagesData.length;
            showLightboxImageProduit(currentProduitImageIndex);
        }

        // Fonctions pour le carousel mobile produit
        let currentMobileProduitImageIndex = 0;
        let mobileProduitImagesData = [];

        function goToMobileImageProduit(index) {
            if (mobileProduitImagesData.length === 0 || index < 0 || index >= mobileProduitImagesData.length) return;
            currentMobileProduitImageIndex = index;
            showMobileImageProduit(index);
        }

        function previousMobileImageProduit() {
            if (mobileProduitImagesData.length === 0) return;
            currentMobileProduitImageIndex = (currentMobileProduitImageIndex - 1 + mobileProduitImagesData.length) % mobileProduitImagesData.length;
            showMobileImageProduit(currentMobileProduitImageIndex);
        }

        function nextMobileImageProduit() {
            if (mobileProduitImagesData.length === 0) return;
            currentMobileProduitImageIndex = (currentMobileProduitImageIndex + 1) % mobileProduitImagesData.length;
            showMobileImageProduit(currentMobileProduitImageIndex);
        }

        function showMobileImageProduit(index) {
            const images = document.querySelectorAll('.mobile-produit-carousel-image');
            const dots = document.querySelectorAll('.mobile-produit-carousel-dot');
            const thumbnails = document.querySelectorAll('.mobile-produit-thumbnail');
            const counter = document.getElementById('mobile-produit-image-counter');

            images.forEach((img, idx) => {
                if (idx === index) {
                    img.classList.remove('opacity-0', 'absolute');
                    img.classList.add('opacity-100');
                } else {
                    img.classList.remove('opacity-100');
                    img.classList.add('opacity-0', 'absolute');
                }
            });

            dots.forEach((dot, idx) => {
                if (idx === index) {
                    dot.classList.remove('bg-white/50', 'w-1.5');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });

            thumbnails.forEach((thumb, idx) => {
                if (idx === index) {
                    thumb.classList.remove('border-slate-200', 'dark:border-slate-600');
                    thumb.classList.add('border-green-500', 'ring-2', 'ring-green-500');
                } else {
                    thumb.classList.remove('border-green-500', 'ring-2', 'ring-green-500');
                    thumb.classList.add('border-slate-200', 'dark:border-slate-600');
                }
            });

            if (counter) {
                counter.textContent = `${index + 1} / ${mobileProduitImagesData.length}`;
            }
        }

        // Gestion des gestes tactiles (swipe) pour le carrousel mobile produit
        let touchStartXProduit = 0;
        let touchEndXProduit = 0;

        function initMobileProduitSwipe() {
            const carouselWrapper = document.getElementById('mobile-produit-carousel-wrapper');
            if (!carouselWrapper) return;

            carouselWrapper.addEventListener('touchstart', function(e) {
                touchStartXProduit = e.changedTouches[0].screenX;
            }, { passive: true });

            carouselWrapper.addEventListener('touchend', function(e) {
                touchEndXProduit = e.changedTouches[0].screenX;
                handleMobileProduitSwipe();
            }, { passive: true });
        }

        function handleMobileProduitSwipe() {
            const swipeThreshold = 50; // Minimum distance for a swipe
            const diff = touchStartXProduit - touchEndXProduit;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    nextMobileImageProduit();
                } else {
                    // Swipe right - previous image
                    previousMobileImageProduit();
                }
            }
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

        // Fonctions pour gérer les menus déroulants
        function toggleProduitsRestants() {
            const list = document.getElementById('produits-restants-list');
            const arrow = document.getElementById('produits-restants-arrow');
            if (list && arrow) {
                list.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        function toggleProduitsRestantsDesktop() {
            const list = document.getElementById('produits-restants-desktop-list');
            const arrow = document.getElementById('produits-restants-desktop-arrow');
            if (list && arrow) {
                list.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        window.addEventListener('hashchange', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#produit-')) {
                const produitId = parseInt(hash.replace('#produit-', ''));
                selectProduit(produitId, window.innerWidth < 1024);
            }
        });
        // Gestion des étoiles pour les avis produits
        function setNoteProduit(note) {
            document.getElementById('note-input-produit').value = note;
            const stars = document.querySelectorAll('.star-btn-produit');
            stars.forEach((star, index) => {
                if (index < note) {
                    star.textContent = '★';
                    star.classList.add('text-yellow-400');
                    star.classList.remove('text-slate-300', 'dark:text-slate-600');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-slate-300', 'dark:text-slate-600');
                }
            });
        }

        // Gestion du formulaire de commande
        function toggleLivraisonFields() {
            const modeLivraison = document.querySelector('input[name="mode_livraison"]:checked')?.value;
            const livraisonFields = document.getElementById('livraison-fields');
            
            if (modeLivraison === 'livraison') {
                livraisonFields.classList.remove('hidden');
                // Rendre les champs requis
                livraisonFields.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                    input.required = true;
                });
            } else {
                livraisonFields.classList.add('hidden');
                // Retirer le required
                livraisonFields.querySelectorAll('input[type="text"], input[type="date"]').forEach(input => {
                    input.required = false;
                    input.value = '';
                });
            }
        }

        function updatePrixTotal() {
            const quantite = parseInt(document.getElementById('commande_quantite')?.value || 1);
            const prixUnitaireText = document.getElementById('prix-unitaire-display')?.textContent || '0 €';
            const prixUnitaire = parseFloat(prixUnitaireText.replace(' €', '').replace(',', '.')) || {{ $prixUnitaire ?? $firstProduit->prix ?? 0 }};
            const total = quantite * prixUnitaire;
            const prixTotalEl = document.getElementById('prix-total');
            if (prixTotalEl) {
                prixTotalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
            }
        }

        // Initialiser
        document.addEventListener('DOMContentLoaded', function() {
            toggleLivraisonFields();
            // Mettre à jour le formulaire de commande pour le premier produit
            if (produitsData.length > 0) {
                updateCommandeForm(produitsData[0]);
            }

            // Initialiser le carousel avec les données du premier produit
            if (produitsData.length > 0) {
                updateProduitCarousel(produitsData[0]);
            }

            // Navigation au clavier pour le carousel (hors lightbox)
            document.addEventListener('keydown', function(e) {
                const lightbox = document.getElementById('produit-lightbox');
                if (lightbox && !lightbox.classList.contains('hidden')) return; // Ne pas interférer avec la lightbox
                
                if (produitImagesData.length > 1) {
                    if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        previousImageProduit();
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        nextImageProduit();
                    }
                }
            });
        });
    </script>

    <!-- Lightbox pour les images produits -->
    <div id="produit-lightbox" class="fixed inset-0 z-[200] hidden bg-black/95 backdrop-blur-sm" onclick="closeLightboxProduit()">
        <div class="relative w-full h-full flex items-center justify-center p-4" onclick="event.stopPropagation()">
            <button 
                onclick="closeLightboxProduit()" 
                class="absolute top-4 right-4 text-white hover:text-slate-300 transition-colors z-10"
                aria-label="Fermer"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            @if($firstProduit->images->count() > 1)
                <button 
                    onclick="event.stopPropagation(); previousLightboxImageProduit()" 
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-4 rounded-full transition-all z-10"
                    aria-label="Image précédente"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button 
                    onclick="event.stopPropagation(); nextLightboxImageProduit()" 
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-4 rounded-full transition-all z-10"
                    aria-label="Image suivante"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold z-10">
                    <span id="produit-lightbox-counter">1 / {{ $firstProduit->images->count() }}</span>
                </div>
            @endif

            <img 
                id="produit-lightbox-image"
                src="" 
                alt="{{ $firstProduit->nom }}"
                class="max-w-full max-h-[90vh] object-contain rounded-lg"
                onclick="event.stopPropagation()"
            >
        </div>
    </div>
</body>
</html>
