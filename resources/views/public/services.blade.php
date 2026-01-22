<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Services - {{ $entreprise->nom }}</title>
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
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Services</h1>
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

        @if($typesServices && $typesServices->count() > 0)
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
                        $servicesPrincipaux = $typesServices->take(9);
                        $servicesRestants = $typesServices->skip(9);
                    @endphp
                    @foreach($servicesPrincipaux as $service)
                        @php
                            $imageCouverture = $service->imageCouverture;
                            $premiereImage = $service->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                        @endphp
                        <div 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden cursor-pointer hover:shadow-lg transition-all"
                            onclick="selectService({{ $service->id }}, true)"
                            data-service-id="{{ $service->id }}"
                        >
                            @if($imageAffichee)
                                <div class="relative h-48 w-full overflow-hidden">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $service->nom }}"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $service->nom }}</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-slate-600 dark:text-slate-400">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $service->duree_minutes }} min
                                        </span>
                                        <span class="font-bold text-green-600 dark:text-green-400">
                                            {{ number_format($service->prix, 0, ',', ' ') }} €
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    @if($servicesRestants->count() > 0)
                        <!-- Menu déroulant pour les services restants -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <button 
                                onclick="toggleServicesRestants()"
                                class="w-full px-4 py-3 flex items-center justify-between text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                            >
                                <span class="font-semibold">Voir {{ $servicesRestants->count() }} autre(s) service(s)</span>
                                <svg id="services-restants-arrow" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="services-restants-list" class="hidden space-y-4 p-4 border-t border-slate-200 dark:border-slate-700">
                                @foreach($servicesRestants as $service)
                                    @php
                                        $imageCouverture = $service->imageCouverture;
                                        $premiereImage = $service->images->first();
                                        $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                    @endphp
                                    <div 
                                        class="bg-slate-50 dark:bg-slate-700/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden cursor-pointer hover:shadow-lg transition-all"
                                        onclick="selectService({{ $service->id }}, true)"
                                        data-service-id="{{ $service->id }}"
                                    >
                                        @if($imageAffichee)
                                            <div class="relative h-48 w-full overflow-hidden">
                                                <img 
                                                    src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                                    alt="{{ $service->nom }}"
                                                    class="w-full h-full object-cover"
                                                >
                                            </div>
                                        @endif
                                        <div class="p-4">
                                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $service->nom }}</h3>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4 text-sm">
                                                    <span class="text-slate-600 dark:text-slate-400">
                                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ $service->duree_minutes }} min
                                                    </span>
                                                    <span class="font-bold text-green-600 dark:text-green-400">
                                                        {{ number_format($service->prix, 0, ',', ' ') }} €
                                                    </span>
                                                </div>
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
                    <div id="mobile-service-details" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <p class="text-center text-slate-500 dark:text-slate-400">Sélectionnez un service dans la liste</p>
                    </div>
                </div>
            </div>

            <!-- Desktop: Layout 20/80 -->
            <div class="hidden lg:flex max-w-[1920px] mx-auto h-[calc(100vh-73px)]">
                <!-- Sidebar (20%) -->
                <div class="w-1/5 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-y-auto">
                    <div class="p-4 space-y-4">
                        @php
                            $servicesPrincipauxDesktop = $typesServices->take(9);
                            $servicesRestantsDesktop = $typesServices->skip(9);
                        @endphp
                        @foreach($servicesPrincipauxDesktop as $service)
                            @php
                                $imageCouverture = $service->imageCouverture;
                                $premiereImage = $service->images->first();
                                $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            @endphp
                            <div 
                                class="service-card bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 cursor-pointer hover:border-green-500 dark:hover:border-green-600 hover:shadow-lg transition-all {{ $loop->first ? 'border-green-500 dark:border-green-600 shadow-md' : '' }}"
                                onclick="selectService({{ $service->id }}, false)"
                                data-service-id="{{ $service->id }}"
                            >
                                @if($imageAffichee)
                                    <div class="relative h-32 w-full rounded-lg overflow-hidden mb-3">
                                        <img 
                                            src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                            alt="{{ $service->nom }}"
                                            class="w-full h-full object-cover"
                                        >
                                    </div>
                                @endif
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 line-clamp-2">{{ $service->nom }}</h3>
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $service->duree_minutes }} min
                                    </span>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($service->prix, 0, ',', ' ') }} €
                                    </span>
                                </div>
                                @if(!$loop->last)
                                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700"></div>
                                @endif
                            </div>
                        @endforeach
                        
                        @if($servicesRestantsDesktop->count() > 0)
                            <!-- Menu déroulant pour les services restants (Desktop) -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 overflow-hidden">
                                <button 
                                    onclick="toggleServicesRestantsDesktop()"
                                    class="w-full px-4 py-3 flex items-center justify-between text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                >
                                    <span class="font-semibold text-sm">Voir {{ $servicesRestantsDesktop->count() }} autre(s)</span>
                                    <svg id="services-restants-desktop-arrow" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="services-restants-desktop-list" class="hidden space-y-4 p-4 border-t border-slate-200 dark:border-slate-700">
                                    @foreach($servicesRestantsDesktop as $service)
                                        @php
                                            $imageCouverture = $service->imageCouverture;
                                            $premiereImage = $service->images->first();
                                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                        @endphp
                                        <div 
                                            class="service-card bg-slate-50 dark:bg-slate-700/50 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 cursor-pointer hover:border-green-500 dark:hover:border-green-600 hover:shadow-lg transition-all"
                                            onclick="selectService({{ $service->id }}, false)"
                                            data-service-id="{{ $service->id }}"
                                        >
                                            @if($imageAffichee)
                                                <div class="relative h-32 w-full rounded-lg overflow-hidden mb-3">
                                                    <img 
                                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                                        alt="{{ $service->nom }}"
                                                        class="w-full h-full object-cover"
                                                    >
                                                </div>
                                            @endif
                                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 line-clamp-2">{{ $service->nom }}</h3>
                                            <div class="flex items-center justify-between text-sm mb-2">
                                                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $service->duree_minutes }} min
                                                </span>
                                                <span class="font-bold text-green-600 dark:text-green-400">
                                                    {{ number_format($service->prix, 0, ',', ' ') }} €
                                                </span>
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
                    <div id="service-details" class="p-8">
                        @php
                            $firstService = $typesServices->first();
                            $imageCouverture = $firstService->imageCouverture;
                            $premiereImage = $firstService->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                        @endphp
                        <div class="max-w-4xl mx-auto">
                            <!-- Carousel d'images -->
                            @if($firstService->images->count() > 0)
                                <div class="relative mb-6 group" id="service-image-carousel-container">
                                    <div class="relative h-96 w-full rounded-2xl overflow-hidden shadow-xl bg-slate-200 dark:bg-slate-700">
                                        <!-- Image principale -->
                                        <div class="relative w-full h-full" id="service-carousel-wrapper">
                                            @foreach($firstService->images as $index => $image)
                                                <img 
                                                    src="{{ asset('media/' . $image->image_path) }}" 
                                                    alt="{{ $firstService->nom }}"
                                                    class="service-carousel-image w-full h-full object-cover transition-opacity duration-500 {{ $index === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0' }}"
                                                    data-index="{{ $index }}"
                                                    onclick="openLightboxService({{ $index }})"
                                                    style="cursor: {{ $firstService->images->count() > 1 ? 'zoom-in' : 'default' }}"
                                                >
                                            @endforeach
                                        </div>

                                        <!-- Badge nombre d'images (si plusieurs) -->
                                        @if($firstService->images->count() > 1)
                                            <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-sm font-semibold flex items-center gap-2 z-10">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span id="service-image-counter">1 / {{ $firstService->images->count() }}</span>
                                            </div>
                                        @endif

                                        <!-- Boutons navigation (si plusieurs images) -->
                                        @if($firstService->images->count() > 1)
                                            <button 
                                                onclick="previousImageService()" 
                                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all opacity-0 group-hover:opacity-100 z-10 shadow-lg"
                                                aria-label="Image précédente"
                                            >
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                </svg>
                                            </button>
                                            <button 
                                                onclick="nextImageService()" 
                                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all opacity-0 group-hover:opacity-100 z-10 shadow-lg"
                                                aria-label="Image suivante"
                                            >
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>

                                            <!-- Indicateurs de position (points) -->
                                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                                @foreach($firstService->images as $index => $image)
                                                    <button 
                                                        onclick="goToImageService({{ $index }})"
                                                        class="service-carousel-dot w-2 h-2 rounded-full transition-all {{ $index === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75' }}"
                                                        aria-label="Image {{ $index + 1 }}"
                                                    ></button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Miniatures (si plusieurs images) -->
                                    @if($firstService->images->count() > 1)
                                        <div class="mt-4 flex gap-2 overflow-x-auto pb-2" id="service-thumbnails" style="scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.3) transparent;">
                                            @foreach($firstService->images as $index => $image)
                                                <button 
                                                    onclick="goToImageService({{ $index }})"
                                                    class="service-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all {{ $index === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400' }}"
                                                    data-index="{{ $index }}"
                                                >
                                                    <img 
                                                        src="{{ asset('media/' . $image->image_path) }}" 
                                                        alt="{{ $firstService->nom }}"
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
                                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4" id="service-title">{{ $firstService->nom }}</h1>
                                <div class="flex items-center gap-6 text-lg">
                                    <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span id="service-duree">{{ $firstService->duree_minutes }} minutes</span>
                                    </span>
                                    <span class="text-2xl font-bold text-green-600 dark:text-green-400" id="service-prix">
                                        {{ number_format($firstService->prix, 0, ',', ' ') }} €
                                    </span>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Description</h2>
                                <p class="text-slate-700 dark:text-slate-300 text-lg leading-relaxed" id="service-description">
                                    {{ $firstService->description ?: 'Aucune description disponible.' }}
                                </p>
                            </div>


                            <!-- Photos des clients (avis) -->
                            @php
                                $serviceAvis = $firstService->serviceAvis;
                                $photosAvis = collect();
                                foreach($serviceAvis as $avis) {
                                    foreach($avis->photos as $photo) {
                                        $photosAvis->push($photo);
                                    }
                                }
                            @endphp
                            @if($photosAvis->count() > 0)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Photos des clients</h2>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="service-avis-photos">
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
                                $avisTries = $firstService->avis_tries;
                                $userAvis = Auth::check() ? \App\Models\ServiceAvis::where('user_id', Auth::id())->where('type_service_id', $firstService->id)->first() : null;
                                $userReservationsPayees = Auth::check() ? \App\Models\Reservation::where('user_id', Auth::id())
                                    ->where('entreprise_id', $entreprise->id)
                                    ->where('est_paye', true)
                                    ->get() : collect();
                            @endphp
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                                    Avis et notes
                                    <span class="text-lg font-normal text-slate-600 dark:text-slate-400">
                                        ({{ $firstService->nombre_avis }} avis, note moyenne: {{ $firstService->note_moyenne }}/5)
                                    </span>
                                </h2>

                                <!-- Formulaire pour laisser un avis -->
                                @auth
                                    @if(!$userAvis)
                                        <div class="mb-6 bg-slate-50 dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Laisser un avis</h3>
                                            <form action="{{ route('public.service.avis.store', ['slug' => $entreprise->slug, 'serviceId' => $firstService->id]) }}" method="POST">
                                                @csrf
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Note *</label>
                                                    <div class="flex gap-2" id="note-stars-service">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <button type="button" onclick="setNoteService({{ $i }})" class="star-btn-service text-3xl text-slate-300 dark:text-slate-600 hover:text-yellow-400 transition-colors" data-note="{{ $i }}">☆</button>
                                                        @endfor
                                                    </div>
                                                    <input type="hidden" name="note" id="note-input-service" required>
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
                                    <div class="space-y-4" id="service-avis-list">
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

                            <!-- Bouton d'action -->
                            <div class="mt-8">
                                <a 
                                    id="desktop-reservation-link"
                                    href="{{ route('public.agenda', ['slug' => $entreprise->slug, 'service' => $firstService->id]) }}"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Réserver ce service
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">Aucun service disponible pour le moment.</p>
            </div>
        @endif
    </div>

    @php
        $servicesDataArray = $typesServices->map(function($service) {
            $imageCouverture = $service->imageCouverture;
            $premiereImage = $service->images->first();
            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
            
            $serviceAvis = $service->serviceAvis;
            $photosAvis = collect();
            foreach($serviceAvis as $avis) {
                foreach($avis->photos as $photo) {
                    $photosAvis->push($photo);
                }
            }
            
            return [
                'id' => $service->id,
                'nom' => $service->nom,
                'description' => $service->description ?? '',
                'duree_minutes' => $service->duree_minutes,
                'prix' => $service->prix,
                'images' => $service->images->map(fn($img) => asset('media/' . $img->image_path))->toArray(),
                'image_principale' => $imageAffichee ? asset('media/' . $imageAffichee->image_path) : null,
                'avis' => $serviceAvis->map(function($avis) {
                    return [
                        'user_name' => $avis->user->name ?? 'Utilisateur',
                        'note' => $avis->note,
                        'commentaire' => $avis->commentaire ?? '',
                        'date' => $avis->created_at->format('d/m/Y'),
                    ];
                })->toArray(),
                'nombre_avis' => $service->nombre_avis,
                'note_moyenne' => $service->note_moyenne,
                'photos_avis' => $photosAvis->map(fn($p) => asset('storage/' . $p->photo_path))->take(8)->toArray(),
            ];
        })->toArray();
        
        $realisationPhotosArray = $entreprise->realisationPhotos->map(fn($p) => asset('storage/' . $p->photo_path))->take(8)->toArray();
        $agendaBaseUrl = route('public.agenda', $entreprise->slug);
    @endphp

    <script>
        // Données des services
        const servicesData = @json($servicesDataArray);

        const realisationPhotos = @json($realisationPhotosArray);
        const agendaBaseUrl = @json($agendaBaseUrl);

        function selectService(serviceId, isMobile) {
            const service = servicesData.find(s => s.id === serviceId);
            if (!service) return;

            if (isMobile) {
                // Mobile: Afficher dans l'onglet détails
                switchMobileTab('details');
                mobileServiceImagesData = service.images || [];
                currentMobileServiceImageIndex = 0;
                updateMobileServiceDetails(service);
            } else {
                // Desktop: Mettre à jour le contenu principal
                updateDesktopServiceDetails(service);
                
                // Mettre à jour la sélection dans la sidebar
                document.querySelectorAll('.service-card').forEach(card => {
                    card.classList.remove('border-green-500', 'dark:border-green-600', 'shadow-md');
                    card.classList.add('border-slate-200', 'dark:border-slate-700');
                });
                const selectedCard = document.querySelector(`[data-service-id="${serviceId}"]`);
                if (selectedCard) {
                    selectedCard.classList.remove('border-slate-200', 'dark:border-slate-700');
                    selectedCard.classList.add('border-green-500', 'dark:border-green-600', 'shadow-md');
                }
            }

            // Mettre à jour le carousel
            updateServiceCarousel(service);

            // Mettre à jour l'URL avec le hash
            window.location.hash = `service-${serviceId}`;
        }

        function updateDesktopServiceDetails(service) {
            document.getElementById('service-title').textContent = service.nom;
            document.getElementById('service-duree').textContent = service.duree_minutes + ' minutes';
            document.getElementById('service-prix').textContent = numberFormat(service.prix, 0, ',', ' ') + ' €';
            document.getElementById('service-description').textContent = service.description || 'Aucune description disponible.';

            // Mettre à jour le carousel d'images
            updateServiceCarousel(service);

            // Photos avis
            const avisPhotos = document.getElementById('service-avis-photos');
            if (avisPhotos && service.photos_avis.length > 0) {
                avisPhotos.innerHTML = service.photos_avis.map(photo => `
                    <div class="relative h-32 rounded-lg overflow-hidden">
                        <img src="${photo}" alt="Photo client" class="w-full h-full object-cover">
                    </div>
                `).join('');
            }

            // Avis
            const avisList = document.getElementById('service-avis-list');
            if (avisList) {
                if (service.avis.length > 0) {
                    avisList.innerHTML = service.avis.map(avis => `
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

            // Mettre à jour le lien de réservation
            const reservationLink = document.getElementById('desktop-reservation-link');
            if (reservationLink) {
                reservationLink.href = agendaBaseUrl + '?service=' + service.id;
            }
        }

        function updateMobileServiceDetails(service) {
            const detailsDiv = document.getElementById('mobile-service-details');
            const hasMultipleImages = service.images && service.images.length > 1;
            
            detailsDiv.innerHTML = `
                ${service.images && service.images.length > 0 ? `
                    <div class="relative mb-6 group" id="mobile-service-image-carousel-container">
                        <div class="relative h-80 w-full rounded-2xl overflow-hidden shadow-xl bg-slate-200 dark:bg-slate-700">
                            <div class="relative w-full h-full" id="mobile-service-carousel-wrapper">
                                ${service.images.map((img, idx) => `
                                    <img 
                                        src="${img}" 
                                        alt="${service.nom}"
                                        class="mobile-service-carousel-image w-full h-full object-cover transition-opacity duration-500 ${idx === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0'}"
                                        data-index="${idx}"
                                        onclick="openLightboxService(${idx})"
                                        style="cursor: ${hasMultipleImages ? 'zoom-in' : 'default'}"
                                    >
                                `).join('')}
                            </div>
                            ${hasMultipleImages ? `
                                <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-sm font-semibold flex items-center gap-2 z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span id="mobile-service-image-counter">1 / ${service.images.length}</span>
                                </div>
                                <button onclick="previousMobileImageService()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all z-10 shadow-lg" aria-label="Image précédente">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button onclick="nextMobileImageService()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 backdrop-blur-sm text-white p-3 rounded-full transition-all z-10 shadow-lg" aria-label="Image suivante">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                    ${service.images.map((img, idx) => `
                                        <button onclick="goToMobileImageService(${idx})" class="mobile-service-carousel-dot w-2 h-2 rounded-full transition-all ${idx === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'}" aria-label="Image ${idx + 1}"></button>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                        ${hasMultipleImages ? `
                            <div class="mt-4 flex gap-2 overflow-x-auto pb-2" id="mobile-service-thumbnails" style="scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.3) transparent;">
                                ${service.images.map((img, idx) => `
                                    <button onclick="goToMobileImageService(${idx})" class="mobile-service-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all ${idx === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400'}" data-index="${idx}">
                                        <img src="${img}" alt="${service.nom}" class="w-full h-full object-cover">
                                    </button>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">${service.nom}</h2>
                <div class="flex items-center gap-4 mb-4">
                    <span class="text-slate-600 dark:text-slate-400">${service.duree_minutes} min</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400">${numberFormat(service.prix, 0, ',', ' ')} €</span>
                </div>
                <p class="text-slate-700 dark:text-slate-300 mb-6">${service.description || 'Aucune description disponible.'}</p>
                <a href="${agendaBaseUrl}?service=${service.id}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all">
                    Réserver ce service
                </a>
            `;
            
            // Initialiser les gestes tactiles pour le carrousel mobile
            if (hasMultipleImages) {
                initMobileServiceSwipe();
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

        // Variables pour le carousel service
        let currentServiceImageIndex = 0;
        let serviceImagesData = [];

        function updateServiceCarousel(service) {
            serviceImagesData = service.images || [];
            currentServiceImageIndex = 0;
            
            const container = document.getElementById('service-image-carousel-container');
            if (!container || serviceImagesData.length === 0) return;

            // Mettre à jour le wrapper d'images
            const wrapper = document.getElementById('service-carousel-wrapper');
            if (wrapper) {
                wrapper.innerHTML = serviceImagesData.map((img, idx) => `
                    <img 
                        src="${img}" 
                        alt="${service.nom}"
                        class="service-carousel-image w-full h-full object-cover transition-opacity duration-500 ${idx === 0 ? 'opacity-100' : 'opacity-0 absolute inset-0'}"
                        data-index="${idx}"
                        onclick="openLightboxService(${idx})"
                        style="cursor: ${serviceImagesData.length > 1 ? 'zoom-in' : 'default'}"
                    >
                `).join('');
            }

            // Mettre à jour le compteur
            const counter = document.getElementById('service-image-counter');
            if (counter && serviceImagesData.length > 1) {
                counter.textContent = `1 / ${serviceImagesData.length}`;
            }

            // Mettre à jour les indicateurs
            updateServiceCarouselIndicators();

            // Mettre à jour les miniatures
            const thumbnails = document.getElementById('service-thumbnails');
            if (thumbnails && serviceImagesData.length > 1) {
                thumbnails.innerHTML = serviceImagesData.map((img, idx) => `
                    <button 
                        onclick="goToImageService(${idx})"
                        class="service-thumbnail flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all ${idx === 0 ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600 hover:border-green-400'}"
                        data-index="${idx}"
                    >
                        <img src="${img}" alt="${service.nom}" class="w-full h-full object-cover">
                    </button>
                `).join('');
            }
        }

        function goToImageService(index) {
            if (serviceImagesData.length === 0 || index < 0 || index >= serviceImagesData.length) return;
            
            currentServiceImageIndex = index;
            showImageService(index);
        }

        function previousImageService() {
            if (serviceImagesData.length === 0) return;
            currentServiceImageIndex = (currentServiceImageIndex - 1 + serviceImagesData.length) % serviceImagesData.length;
            showImageService(currentServiceImageIndex);
        }

        function nextImageService() {
            if (serviceImagesData.length === 0) return;
            currentServiceImageIndex = (currentServiceImageIndex + 1) % serviceImagesData.length;
            showImageService(currentServiceImageIndex);
        }

        function showImageService(index) {
            const images = document.querySelectorAll('.service-carousel-image');
            const dots = document.querySelectorAll('.service-carousel-dot');
            const thumbnails = document.querySelectorAll('.service-thumbnail');
            const counter = document.getElementById('service-image-counter');

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
                counter.textContent = `${index + 1} / ${serviceImagesData.length}`;
            }
        }

        function updateServiceCarouselIndicators() {
            const dots = document.querySelectorAll('.service-carousel-dot');
            dots.forEach((dot, idx) => {
                if (idx === currentServiceImageIndex) {
                    dot.classList.remove('bg-white/50', 'w-2');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });
        }

        // Lightbox pour voir les images en grand
        function openLightboxService(index) {
            if (serviceImagesData.length === 0) return;
            currentServiceImageIndex = index;
            const lightbox = document.getElementById('service-lightbox');
            if (lightbox) {
                lightbox.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                showLightboxImageService(index);
            }
        }

        function closeLightboxService() {
            const lightbox = document.getElementById('service-lightbox');
            if (lightbox) {
                lightbox.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function showLightboxImageService(index) {
            const lightboxImage = document.getElementById('service-lightbox-image');
            const lightboxCounter = document.getElementById('service-lightbox-counter');
            
            if (lightboxImage && serviceImagesData[index]) {
                lightboxImage.src = serviceImagesData[index];
            }
            
            if (lightboxCounter) {
                lightboxCounter.textContent = `${index + 1} / ${serviceImagesData.length}`;
            }
        }

        // Navigation au clavier
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('service-lightbox');
            if (lightbox && !lightbox.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    previousLightboxImageService();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextLightboxImageService();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    closeLightboxService();
                }
            }
        });

        function previousLightboxImageService() {
            currentServiceImageIndex = (currentServiceImageIndex - 1 + serviceImagesData.length) % serviceImagesData.length;
            showLightboxImageService(currentServiceImageIndex);
        }

        function nextLightboxImageService() {
            currentServiceImageIndex = (currentServiceImageIndex + 1) % serviceImagesData.length;
            showLightboxImageService(currentServiceImageIndex);
        }

        // Fonctions pour le carousel mobile
        let currentMobileServiceImageIndex = 0;
        let mobileServiceImagesData = [];

        function goToMobileImageService(index) {
            if (mobileServiceImagesData.length === 0 || index < 0 || index >= mobileServiceImagesData.length) return;
            currentMobileServiceImageIndex = index;
            showMobileImageService(index);
        }

        function previousMobileImageService() {
            if (mobileServiceImagesData.length === 0) return;
            currentMobileServiceImageIndex = (currentMobileServiceImageIndex - 1 + mobileServiceImagesData.length) % mobileServiceImagesData.length;
            showMobileImageService(currentMobileServiceImageIndex);
        }

        function nextMobileImageService() {
            if (mobileServiceImagesData.length === 0) return;
            currentMobileServiceImageIndex = (currentMobileServiceImageIndex + 1) % mobileServiceImagesData.length;
            showMobileImageService(currentMobileServiceImageIndex);
        }

        function showMobileImageService(index) {
            const images = document.querySelectorAll('.mobile-service-carousel-image');
            const dots = document.querySelectorAll('.mobile-service-carousel-dot');
            const thumbnails = document.querySelectorAll('.mobile-service-thumbnail');
            const counter = document.getElementById('mobile-service-image-counter');

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
                counter.textContent = `${index + 1} / ${mobileServiceImagesData.length}`;
            }
        }

        // Gestion des gestes tactiles (swipe) pour le carrousel mobile service
        let touchStartX = 0;
        let touchEndX = 0;

        function initMobileServiceSwipe() {
            const carouselWrapper = document.getElementById('mobile-service-carousel-wrapper');
            if (!carouselWrapper) return;

            carouselWrapper.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            carouselWrapper.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleMobileServiceSwipe();
            }, { passive: true });
        }

        function handleMobileServiceSwipe() {
            const swipeThreshold = 50; // Minimum distance for a swipe
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    nextMobileImageService();
                } else {
                    // Swipe right - previous image
                    previousMobileImageService();
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
            if (hash && hash.startsWith('#service-')) {
                const serviceId = parseInt(hash.replace('#service-', ''));
                selectService(serviceId, window.innerWidth < 1024);
            }
        });

        // Gérer le hash lors du changement
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#service-')) {
                const serviceId = parseInt(hash.replace('#service-', ''));
                selectService(serviceId, window.innerWidth < 1024);
            }
        });

        // Fonctions pour gérer les menus déroulants
        function toggleServicesRestants() {
            const list = document.getElementById('services-restants-list');
            const arrow = document.getElementById('services-restants-arrow');
            if (list && arrow) {
                list.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        function toggleServicesRestantsDesktop() {
            const list = document.getElementById('services-restants-desktop-list');
            const arrow = document.getElementById('services-restants-desktop-arrow');
            if (list && arrow) {
                list.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }
        // Gestion des étoiles pour les avis services
        function setNoteService(note) {
            document.getElementById('note-input-service').value = note;
            const stars = document.querySelectorAll('.star-btn-service');
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
    </script>

    <!-- Lightbox pour les images services -->
    <div id="service-lightbox" class="fixed inset-0 z-[200] hidden bg-black/95 backdrop-blur-sm" onclick="closeLightboxService()">
        <div class="relative w-full h-full flex items-center justify-center p-4" onclick="event.stopPropagation()">
            <button 
                onclick="closeLightboxService()" 
                class="absolute top-4 right-4 text-white hover:text-slate-300 transition-colors z-10"
                aria-label="Fermer"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            @if($firstService->images->count() > 1)
                <button 
                    onclick="event.stopPropagation(); previousLightboxImageService()" 
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-4 rounded-full transition-all z-10"
                    aria-label="Image précédente"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button 
                    onclick="event.stopPropagation(); nextLightboxImageService()" 
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-4 rounded-full transition-all z-10"
                    aria-label="Image suivante"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold z-10">
                    <span id="service-lightbox-counter">1 / {{ $firstService->images->count() }}</span>
                </div>
            @endif

            <img 
                id="service-lightbox-image"
                src="" 
                alt="{{ $firstService->nom }}"
                class="max-w-full max-h-[90vh] object-contain rounded-lg"
                onclick="event.stopPropagation()"
            >
        </div>
    </div>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
