{{-- Onglet système : Services --}}
@php
    $services = $entreprise->typesServices()->where('est_actif', true)->with(['imageCouverture', 'options.choices', 'serviceAvis'])->get();
@endphp

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <header class="mb-8 text-center">
        <h1 class="text-3xl font-bold mb-2" style="font-family: var(--site-font-heading); color: var(--site-text);">
            Nos Services
        </h1>
        <p class="opacity-60 max-w-2xl mx-auto" style="color: var(--site-text);">
            Découvrez l'ensemble de nos prestations
        </p>
    </header>

    @if($services->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div class="rounded-2xl border overflow-hidden shadow-sm hover:shadow-lg transition-shadow"
                     style="background: var(--site-background); border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                    
                    {{-- Image de couverture --}}
                    @if($service->imageCouverture)
                        <div class="aspect-video overflow-hidden">
                            <img src="/media/{{ $service->imageCouverture->chemin }}" alt="{{ $service->nom }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="aspect-video flex items-center justify-center" style="background: color-mix(in srgb, var(--site-primary) 10%, var(--site-background));">
                            <svg class="w-12 h-12 opacity-30" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.193 23.193 0 0112 15c-3.183 0-6.22-.64-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m-3 0h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="p-5">
                        <h3 class="text-lg font-bold mb-1" style="color: var(--site-text);">{{ $service->nom }}</h3>
                        
                        @if($service->description)
                            <p class="text-sm opacity-60 mb-3 line-clamp-2" style="color: var(--site-text);">{{ $service->description }}</p>
                        @endif

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                @if($service->prix)
                                    <span class="text-lg font-bold" style="color: var(--site-primary);">{{ number_format($service->prix, 0, ',', ' ') }}&euro;</span>
                                @endif
                                @if($service->duree_minutes && !$service->estDateButoire())
                                    <span class="text-xs opacity-50 px-2 py-1 rounded-full" style="background: color-mix(in srgb, var(--site-text) 8%, transparent); color: var(--site-text);">
                                        {{ $service->duree_minutes }} min
                                    </span>
                                @endif
                            </div>

                            {{-- Note moyenne --}}
                            @if($service->serviceAvis && $service->serviceAvis->count() > 0)
                                <div class="flex items-center gap-1 text-xs">
                                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="opacity-60">{{ number_format($service->note_moyenne, 1) }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Options --}}
                        @if($service->options && $service->options->count() > 0)
                            <div class="mt-3 pt-3 border-t" style="border-color: color-mix(in srgb, var(--site-text) 8%, transparent);">
                                <p class="text-xs font-medium opacity-50 mb-1">Options disponibles :</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($service->options->take(3) as $option)
                                        <span class="text-xs px-2 py-0.5 rounded-full" style="background: color-mix(in srgb, var(--site-primary) 10%, transparent); color: var(--site-primary);">
                                            {{ $option->nom }}
                                        </span>
                                    @endforeach
                                    @if($service->options->count() > 3)
                                        <span class="text-xs opacity-40">+{{ $service->options->count() - 3 }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Bouton réserver --}}
                        <a href="{{ route('site-web.show', ['slug' => $slug]) }}?tab=reservation&service={{ $service->id }}"
                           class="mt-4 block w-full text-center px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition hover:opacity-90"
                           style="background: var(--site-primary); border-radius: var(--site-button-radius);">
                            Réserver
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 opacity-50">
            <p>Aucun service disponible pour le moment.</p>
        </div>
    @endif
</div>
