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
                    @foreach($typesServices as $service)
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
                        @foreach($typesServices as $service)
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
                            <!-- Image principale -->
                            @if($imageAffichee)
                                <div class="relative h-96 w-full rounded-2xl overflow-hidden mb-6 shadow-xl">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $firstService->nom }}"
                                        class="w-full h-full object-cover"
                                        id="service-main-image"
                                    >
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

                            <!-- Galerie d'images -->
                            @if($firstService->images->count() > 1)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Galerie</h2>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="service-gallery">
                                        @foreach($firstService->images as $image)
                                            <div class="relative h-32 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity" onclick="changeMainImage('{{ asset('media/' . $image->image_path) }}')">
                                                <img 
                                                    src="{{ asset('media/' . $image->image_path) }}" 
                                                    alt="{{ $firstService->nom }}"
                                                    class="w-full h-full object-cover"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

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
                            @if($firstService->serviceAvis->count() > 0)
                                <div class="mb-8">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                                        Avis et notes
                                        <span class="text-lg font-normal text-slate-600 dark:text-slate-400">
                                            ({{ $firstService->nombre_avis }} avis, note moyenne: {{ $firstService->note_moyenne }}/5)
                                        </span>
                                    </h2>
                                    <div class="space-y-4" id="service-avis-list">
                                        @foreach($firstService->serviceAvis->take(5) as $avis)
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

            // Mettre à jour l'URL avec le hash
            window.location.hash = `service-${serviceId}`;
        }

        function updateDesktopServiceDetails(service) {
            document.getElementById('service-title').textContent = service.nom;
            document.getElementById('service-duree').textContent = service.duree_minutes + ' minutes';
            document.getElementById('service-prix').textContent = numberFormat(service.prix, 0, ',', ' ') + ' €';
            document.getElementById('service-description').textContent = service.description || 'Aucune description disponible.';

            // Image principale
            if (service.image_principale) {
                document.getElementById('service-main-image').src = service.image_principale;
            }

            // Galerie
            const gallery = document.getElementById('service-gallery');
            if (gallery && service.images.length > 1) {
                gallery.innerHTML = service.images.map((img, idx) => `
                    <div class="relative h-32 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity" onclick="changeMainImage('${img}')">
                        <img src="${img}" alt="${service.nom}" class="w-full h-full object-cover">
                    </div>
                `).join('');
            }

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
            detailsDiv.innerHTML = `
                ${service.image_principale ? `
                    <div class="relative h-64 w-full rounded-xl overflow-hidden mb-4">
                        <img src="${service.image_principale}" alt="${service.nom}" class="w-full h-full object-cover">
                    </div>
                ` : ''}
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">${service.nom}</h2>
                <div class="flex items-center gap-4 mb-4">
                    <span class="text-slate-600 dark:text-slate-400">${service.duree_minutes} min</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400">${numberFormat(service.prix, 0, ',', ' ')} €</span>
                </div>
                <p class="text-slate-700 dark:text-slate-300 mb-6">${service.description || 'Aucune description disponible.'}</p>
                ${service.images.length > 1 ? `
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Galerie</h3>
                        <div class="grid grid-cols-3 gap-2">
                            ${service.images.map(img => `
                                <div class="relative h-24 rounded-lg overflow-hidden">
                                    <img src="${img}" alt="${service.nom}" class="w-full h-full object-cover">
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                <a href="${agendaBaseUrl}?service=${service.id}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all">
                    Réserver ce service
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
            document.getElementById('service-main-image').src = imageSrc;
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
    </script>
</body>
</html>
