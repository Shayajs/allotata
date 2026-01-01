@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Pourquoi nous choisir ?';
    $items = $content['items'] ?? [];
    
    // Exemples par défaut
    if (empty($items)) {
        $items = [
            ['icon' => 'check', 'title' => 'Qualité garantie', 'description' => 'Nous nous engageons à fournir un service de qualité supérieure.'],
            ['icon' => 'clock', 'title' => 'Ponctualité', 'description' => 'Respect des horaires et des délais convenus.'],
            ['icon' => 'heart', 'title' => 'À votre écoute', 'description' => 'Nous prenons en compte vos besoins et vos préférences.'],
            ['icon' => 'star', 'title' => 'Expertise', 'description' => 'Des années d\'expérience à votre service.'],
            ['icon' => 'shield', 'title' => 'Confiance', 'description' => 'Un service fiable et transparent.'],
            ['icon' => 'refresh', 'title' => 'Flexibilité', 'description' => 'Nous nous adaptons à votre emploi du temps.'],
        ];
    }
    
    $columns = $settings['columns'] ?? 3;
    $colClass = match($columns) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-2 lg:grid-cols-3',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3'
    };
    
    $icons = [
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        'heart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
        'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>',
        'default' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
    ];
@endphp

<section class="py-16 md:py-24 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12"
            style="font-family: var(--site-font-heading); color: var(--site-text);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h2>
        
        <div class="grid grid-cols-1 {{ $colClass }} gap-8">
            @foreach($items as $feature)
                <div class="text-center p-6 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition group">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 group-hover:rotate-3"
                         style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $icons[$feature['icon'] ?? 'default'] ?? $icons['default'] !!}
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-bold mb-2" style="color: var(--site-text); font-family: var(--site-font-heading);">
                        {{ $feature['title'] }}
                    </h3>
                    
                    <p class="text-slate-600 dark:text-slate-400" style="font-family: var(--site-font-body);">
                        {{ $feature['description'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
