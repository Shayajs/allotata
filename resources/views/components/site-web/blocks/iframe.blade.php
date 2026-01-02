@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $src = $content['src'] ?? '';
    $title = $content['title'] ?? '';
    $description = $content['description'] ?? '';
    $height = $settings['height'] ?? '400';
    $style = $settings['style'] ?? 'card'; // card, full, minimal
    $rounded = $settings['rounded'] ?? true;
    
    // Détecter le type d'intégration pour affichage
    $type = 'custom';
    if (str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be')) {
        $type = 'youtube';
    } elseif (str_contains($src, 'google.com/maps') || str_contains($src, 'maps.google')) {
        $type = 'google-maps';
    } elseif (str_contains($src, 'vimeo.com')) {
        $type = 'vimeo';
    } elseif (str_contains($src, 'spotify.com')) {
        $type = 'spotify';
    } elseif (str_contains($src, 'soundcloud.com')) {
        $type = 'soundcloud';
    } elseif (str_contains($src, 'calendly.com')) {
        $type = 'calendly';
    } elseif (str_contains($src, 'typeform.com')) {
        $type = 'typeform';
    }
    
    $typeLabels = [
        'youtube' => ['icon' => '▶️', 'label' => 'YouTube', 'color' => '#FF0000'],
        'google-maps' => ['icon' => '📍', 'label' => 'Google Maps', 'color' => '#4285F4'],
        'vimeo' => ['icon' => '🎬', 'label' => 'Vimeo', 'color' => '#1AB7EA'],
        'spotify' => ['icon' => '🎵', 'label' => 'Spotify', 'color' => '#1DB954'],
        'soundcloud' => ['icon' => '🎶', 'label' => 'SoundCloud', 'color' => '#FF5500'],
        'calendly' => ['icon' => '📅', 'label' => 'Calendly', 'color' => '#006BFF'],
        'typeform' => ['icon' => '📝', 'label' => 'Typeform', 'color' => '#262627'],
        'custom' => ['icon' => '🔗', 'label' => 'Intégration', 'color' => 'var(--site-primary)'],
    ];
    
    $typeInfo = $typeLabels[$type] ?? $typeLabels['custom'];
@endphp

<section class="py-12 md:py-16 px-4" style="background: var(--site-background);">
    <div class="max-w-5xl mx-auto">
        {{-- Titre et description (optionnels) --}}
        @if($title || $description)
            <div class="text-center mb-8">
                @if($title)
                    <h2 class="text-2xl md:text-3xl font-bold mb-3"
                        style="font-family: var(--site-font-heading); color: var(--site-text);"
                        @if($editMode) data-editable="title" @endif>
                        {{ $title }}
                    </h2>
                @endif
                @if($description)
                    <p class="text-lg max-w-2xl mx-auto"
                       style="color: var(--site-text); opacity: 0.7; font-family: var(--site-font-body);"
                       @if($editMode) data-editable="description" @endif>
                        {{ $description }}
                    </p>
                @endif
            </div>
        @endif

        @if($src)
            {{-- Conteneur de l'iframe --}}
            <div class="{{ $style === 'card' ? 'p-4 md:p-6 bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700' : '' }} {{ $rounded ? 'rounded-2xl' : '' }} overflow-hidden">
                
                {{-- Badge du type d'intégration (style card uniquement) --}}
                @if($style === 'card')
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b border-slate-200 dark:border-slate-700">
                        <span class="text-xl">{{ $typeInfo['icon'] }}</span>
                        <span class="text-sm font-medium" style="color: {{ $typeInfo['color'] }};">
                            {{ $typeInfo['label'] }}
                        </span>
                    </div>
                @endif
                
                {{-- Iframe --}}
                <div class="{{ $rounded && $style !== 'full' ? 'rounded-xl' : '' }} overflow-hidden shadow-lg">
                    <iframe 
                        src="{{ $src }}"
                        class="w-full"
                        style="height: {{ $height }}px; border: none;"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            </div>
        @else
            {{-- Placeholder quand pas d'URL --}}
            <div class="rounded-2xl bg-slate-100 dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center" style="height: {{ $height }}px;">
                <div class="text-center p-8">
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <span class="text-3xl">🎬</span>
                        <span class="text-3xl">📍</span>
                        <span class="text-3xl">🎵</span>
                        <span class="text-3xl">📅</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2" style="color: var(--site-text);">
                        Intégration externe
                    </h3>
                    <p class="text-sm max-w-sm mx-auto" style="color: var(--site-text); opacity: 0.6;">
                        Ajoutez l'URL d'une vidéo YouTube, carte Google Maps, playlist Spotify, calendrier Calendly...
                    </p>
                    @if($editMode)
                        <p class="mt-4 text-xs px-4 py-2 bg-slate-200 dark:bg-slate-700 rounded-lg inline-block" style="color: var(--site-text); opacity: 0.8;">
                            👉 Cliquez sur ce bloc puis renseignez l'URL dans les propriétés
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
