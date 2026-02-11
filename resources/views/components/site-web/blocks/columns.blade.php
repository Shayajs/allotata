@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? '';
    $leftContent = $content['left'] ?? [];
    $rightContent = $content['right'] ?? [];
    
    $layout = $settings['layout'] ?? '50-50'; // 50-50, 60-40, 40-60, 70-30, 30-70
    $gap = $settings['gap'] ?? 'medium'; // small, medium, large
    $separator = $settings['separator'] ?? true;
    $separatorStyle = $settings['separatorStyle'] ?? 'line'; // line, dashed, gradient
    $verticalAlign = $settings['verticalAlign'] ?? 'top'; // top, center, stretch
    $mobileStack = $settings['mobileStack'] ?? true;
    
    // Classes de layout
    $layoutClasses = [
        '50-50' => 'grid-cols-1 md:grid-cols-2',
        '60-40' => 'grid-cols-1 md:grid-cols-[60%_40%]',
        '40-60' => 'grid-cols-1 md:grid-cols-[40%_60%]',
        '70-30' => 'grid-cols-1 md:grid-cols-[70%_30%]',
        '30-70' => 'grid-cols-1 md:grid-cols-[30%_70%]',
    ];
    
    $gapClasses = [
        'small' => 'gap-4 md:gap-6',
        'medium' => 'gap-6 md:gap-10',
        'large' => 'gap-8 md:gap-16',
    ];
    
    $alignClasses = [
        'top' => 'items-start',
        'center' => 'items-center',
        'stretch' => 'items-stretch',
    ];
@endphp

<section class="py-12 md:py-20 px-4" style="background: var(--site-background);">
    <div class="max-w-6xl mx-auto">
        {{-- Titre optionnel --}}
        @if($title)
            <h2 class="text-2xl md:text-3xl font-bold text-center mb-10"
                style="font-family: var(--site-font-heading); color: var(--site-text);"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h2>
        @endif

        {{-- Grille avec colonnes --}}
        <div class="grid {{ $layoutClasses[$layout] ?? $layoutClasses['50-50'] }} {{ $gapClasses[$gap] ?? $gapClasses['medium'] }} {{ $alignClasses[$verticalAlign] ?? '' }} relative">
            
            {{-- Colonne gauche --}}
            <div class="space-y-4">
                @if(!empty($leftContent['title']))
                    <h3 class="text-xl md:text-2xl font-bold"
                        style="font-family: var(--site-font-heading); color: var(--site-text);">
                        {{ $leftContent['title'] }}
                    </h3>
                @endif
                
                @if(!empty($leftContent['text']))
                    <div class="prose prose-lg max-w-none" style="color: var(--site-text);">
                        {!! nl2br(e($leftContent['text'])) !!}
                    </div>
                @endif
                
                @if(!empty($leftContent['image']))
                    <img src="{{ $leftContent['image'] }}" alt="{{ $leftContent['imageAlt'] ?? '' }}" 
                         class="w-full rounded-xl shadow-lg object-cover">
                @endif
                
                @if(!empty($leftContent['buttonText']))
                    <a href="{{ $leftContent['buttonLink'] ?? '#' }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 font-semibold text-white transition hover:opacity-90"
                       style="background: var(--site-primary); border-radius: var(--site-button-radius);">
                        {{ $leftContent['buttonText'] }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                @endif
                
                @if(empty($leftContent['title']) && empty($leftContent['text']) && empty($leftContent['image']))
                    <div class="p-8 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-center">
                        <p class="text-slate-500 dark:text-slate-400">Colonne gauche</p>
                        @if($editMode)
                            <p class="text-xs text-slate-400 mt-1">Modifiez le contenu dans les propriétés</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Séparateur vertical --}}
            @if($separator)
                <div class="hidden md:block absolute left-1/2 top-0 bottom-0 transform -translate-x-1/2">
                    @if($separatorStyle === 'line')
                        <div class="w-px h-full" style="background: linear-gradient(to bottom, transparent, var(--site-primary), transparent);"></div>
                    @elseif($separatorStyle === 'dashed')
                        <div class="w-px h-full border-l-2 border-dashed" style="border-color: var(--site-primary); opacity: 0.5;"></div>
                    @elseif($separatorStyle === 'gradient')
                        <div class="w-1 h-full rounded-full" style="background: linear-gradient(to bottom, var(--site-primary), var(--site-secondary));"></div>
                    @endif
                </div>
            @endif

            {{-- Colonne droite --}}
            <div class="space-y-4">
                @if(!empty($rightContent['title']))
                    <h3 class="text-xl md:text-2xl font-bold"
                        style="font-family: var(--site-font-heading); color: var(--site-text);">
                        {{ $rightContent['title'] }}
                    </h3>
                @endif
                
                @if(!empty($rightContent['text']))
                    <div class="prose prose-lg max-w-none" style="color: var(--site-text);">
                        {!! nl2br(e($rightContent['text'])) !!}
                    </div>
                @endif
                
                @if(!empty($rightContent['image']))
                    <img src="{{ $rightContent['image'] }}" alt="{{ $rightContent['imageAlt'] ?? '' }}" 
                         class="w-full rounded-xl shadow-lg object-cover">
                @endif
                
                @if(!empty($rightContent['buttonText']))
                    <a href="{{ $rightContent['buttonLink'] ?? '#' }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 font-semibold text-white transition hover:opacity-90"
                       style="background: var(--site-secondary); border-radius: var(--site-button-radius);">
                        {{ $rightContent['buttonText'] }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                @endif
                
                @if(empty($rightContent['title']) && empty($rightContent['text']) && empty($rightContent['image']))
                    <div class="p-8 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-center">
                        <p class="text-slate-500 dark:text-slate-400">Colonne droite</p>
                        @if($editMode)
                            <p class="text-xs text-slate-400 mt-1">Modifiez le contenu dans les propriétés</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
