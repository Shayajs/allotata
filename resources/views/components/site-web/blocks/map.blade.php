@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Nous trouver';
    $subtitle = $content['subtitle'] ?? '';
    $showAddress = $content['showAddress'] ?? true;
    $height = $settings['height'] ?? '400px';
    
    $hasCoordinates = $entreprise->hasCoordinates();
    $estVirtuelle = $entreprise->estVirtuelle();
@endphp

<section class="py-16 md:py-24 px-4" style="background: var(--site-background);" id="map">
    <div class="max-w-6xl mx-auto">
        {{-- Titre --}}
        <div class="text-center mb-8 md:mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4"
                style="font-family: var(--site-font-heading); color: var(--site-text);"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h2>
            @if($subtitle)
                <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto"
                   style="font-family: var(--site-font-body);"
                   @if($editMode) data-editable="subtitle" @endif>
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if($estVirtuelle)
            <div class="text-center py-12 px-6 rounded-2xl border-2 border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20">
                <svg class="w-16 h-16 mx-auto mb-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-xl font-semibold mb-2" style="color: var(--site-text);">Prestations en ligne</h3>
                <p class="text-slate-600 dark:text-slate-400 max-w-lg mx-auto">
                    Pas de lieu d'accueil physique — contactez-nous pour organiser votre prestation à distance.
                </p>
            </div>
        @elseif($hasCoordinates)
            {{-- Carte interactive --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border border-slate-200 dark:border-slate-700">
                @include('components.map-standalone', [
                    'entreprises' => collect([$entreprise]),
                    'center' => ['lat' => (float) $entreprise->latitude, 'lng' => (float) $entreprise->longitude],
                    'zoom' => 14,
                    'height' => $height,
                    'single' => true,
                    'enableClustering' => false,
                ])
            </div>

            {{-- Informations de localisation --}}
            @if($showAddress)
                <div class="mt-8 flex flex-wrap items-center justify-center gap-6">
                    {{-- Adresse --}}
                    <div class="flex items-center gap-3 px-6 py-3 rounded-xl"
                         style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-white/80">Adresse</p>
                            <p class="font-semibold text-white">{{ $entreprise->formatted_address }}</p>
                        </div>
                    </div>

                    {{-- Déplacement si mobile --}}
                    @if($entreprise->estMobile() && $entreprise->rayon_deplacement)
                        <div class="flex items-center gap-3 px-6 py-3 rounded-xl border-2"
                             style="border-color: var(--site-primary);">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background: var(--site-primary);">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm" style="color: var(--site-primary);">Zone de déplacement</p>
                                <p class="font-semibold" style="color: var(--site-text);">{{ $entreprise->rayon_deplacement }} km autour</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @else
            {{-- Message si pas de coordonnées --}}
            <div class="text-center py-12 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                @if($entreprise->ville)
                    <h3 class="text-xl font-semibold mb-2" style="color: var(--site-text);">
                        📍 {{ $entreprise->ville }}
                    </h3>
                    @if($entreprise->estMobile())
                        <p class="text-slate-600 dark:text-slate-400">
                            Nous nous déplaçons dans un rayon de {{ $entreprise->rayon_deplacement }} km
                        </p>
                    @endif
                @else
                    <p class="text-slate-600 dark:text-slate-400">
                        Localisation non disponible
                    </p>
                @endif
            </div>
        @endif
    </div>
</section>
