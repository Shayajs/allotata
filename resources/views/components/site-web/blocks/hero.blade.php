@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $heightClass = match($settings['height'] ?? 'large') {
        'small' => 'min-h-[300px]',
        'medium' => 'min-h-[400px]',
        'large' => 'min-h-[500px]',
        'full' => 'min-h-screen',
        default => 'min-h-[500px]'
    };
    
    $alignClass = match($settings['alignment'] ?? 'center') {
        'left' => 'text-left items-start',
        'center' => 'text-center items-center',
        'right' => 'text-right items-end',
        default => 'text-center items-center'
    };
    
    // Utiliser l'image de fond de l'entreprise par défaut pour que ça reste dynamique
    // Si l'utilisateur veut une image spécifique, on pourrait ajouter une option "Override" plus tard
    $bgImage = $entreprise->image_fond;
    
    $hasOverlay = $content['overlay'] ?? true;
    
    // Utiliser les valeurs de l'entreprise par défaut
    $title = $content['title'] ?? $entreprise->nom;
    $subtitle = $content['subtitle'] ?? $entreprise->phrase_accroche ?? $entreprise->type_activite;
    $buttonText = $content['buttonText'] ?? 'Nous contacter';
    $buttonLink = $content['buttonLink'] ?? '#contact';
@endphp

<section class="{{ $heightClass }} relative flex flex-col justify-center {{ $alignClass }} p-8 md:p-16 overflow-hidden"
    @if($bgImage)
        style="background-image: url('{{ str_starts_with($bgImage, 'http') ? $bgImage : asset('storage/' . $bgImage) }}'); background-size: cover; background-position: center;"
    @else
        style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));"
    @endif
>
    @if($hasOverlay && $bgImage)
        <div class="absolute inset-0 bg-black/{{ $settings['overlayOpacity'] ?? 50 }}"></div>
    @endif
    
    <div class="relative z-10 max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight"
            style="font-family: var(--site-font-heading);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h1>
        
        @if($subtitle)
            <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-2xl {{ $settings['alignment'] === 'center' ? 'mx-auto' : '' }}"
               style="font-family: var(--site-font-body);"
               @if($editMode) data-editable="subtitle" @endif>
                {{ $subtitle }}
            </p>
        @endif
        
        @if($buttonText)
            <a href="{{ $buttonLink }}" 
               class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90 hover:scale-105"
               style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                {{ $buttonText }}
            </a>
        @endif
    </div>
    
    {{-- Scroll indicator --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/60 animate-bounce">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>
