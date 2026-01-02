@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Contactez-nous';
    $showEmail = $content['showEmail'] ?? true;
    $showPhone = $content['showPhone'] ?? true;
    $showAddress = $content['showAddress'] ?? true;
    $showMap = $content['showMap'] ?? true; // Activé par défaut maintenant
    
    $layout = $settings['layout'] ?? 'centered';
    
    // Afficher la carte uniquement si on a des coordonnées
    $hasCoordinates = $entreprise->latitude && $entreprise->longitude;
@endphp

<section class="py-16 md:py-24 px-4" style="background: var(--site-background);" id="contact">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-4"
            style="font-family: var(--site-font-heading); color: var(--site-text);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h2>
        
        <p class="text-center text-lg text-slate-600 dark:text-slate-400 mb-12 max-w-2xl mx-auto"
           style="font-family: var(--site-font-body);">
            N'hésitez pas à nous contacter pour toute question ou demande de rendez-vous.
        </p>
        
        <div class="grid grid-cols-1 {{ ($showMap && $hasCoordinates) ? 'md:grid-cols-2 gap-8' : '' }}">
            {{-- Informations de contact --}}
            <div class="space-y-6">
                @if($showEmail && $entreprise->email)
                    <a href="mailto:{{ $entreprise->email }}" 
                       class="flex items-center gap-4 p-4 rounded-xl transition hover:scale-105"
                       style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary)); box-shadow: var(--site-button-shadow);">
                        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-white/80 font-medium">Email</p>
                            <p class="text-white font-semibold">{{ $entreprise->email }}</p>
                        </div>
                    </a>
                @endif
                
                @if($showPhone && $entreprise->telephone)
                    <a href="tel:{{ $entreprise->telephone }}" 
                       class="flex items-center gap-4 p-4 rounded-xl border-2 transition hover:scale-105"
                       style="border-color: var(--site-primary);">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                             style="background: var(--site-primary);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium" style="color: var(--site-primary);">Téléphone</p>
                            <p class="font-semibold" style="color: var(--site-text);">{{ $entreprise->telephone }}</p>
                        </div>
                    </a>
                @endif
                
                @if($showAddress && ($entreprise->ville || $entreprise->formatted_address))
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-800">
                        <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Localisation</p>
                            <p class="font-semibold" style="color: var(--site-text);">{{ $entreprise->formatted_address }}</p>
                            @if($entreprise->estMobile())
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Se déplace dans un rayon de {{ $entreprise->rayon_deplacement }} km
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
                
                {{-- Bouton de réservation --}}
                <div class="pt-4">
                    <a href="{{ route('public.entreprise', ['slug' => $entreprise->slug]) }}" 
                       class="inline-flex items-center justify-center gap-2 w-full px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90"
                       style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Prendre rendez-vous
                    </a>
                </div>
            </div>
            
            {{-- Carte interactive --}}
            @if($showMap && $hasCoordinates)
                <div class="rounded-xl overflow-hidden shadow-lg">
                    @include('components.map-standalone', [
                        'entreprises' => collect([$entreprise]),
                        'center' => ['lat' => (float) $entreprise->latitude, 'lng' => (float) $entreprise->longitude],
                        'zoom' => 14,
                        'height' => '400px',
                        'single' => true,
                        'enableClustering' => false,
                    ])
                </div>
            @endif
        </div>
    </div>
</section>

