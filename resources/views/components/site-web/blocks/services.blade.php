@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Nos Services';
    $subtitle = $content['subtitle'] ?? '';
    $items = $content['items'] ?? [];
    
    // Si pas d'items personnalisés, utiliser les services de l'entreprise
    if (empty($items) && $entreprise->typesServices && $entreprise->typesServices->count() > 0) {
        $items = $entreprise->typesServices->map(fn($s) => [
            'name' => $s->nom,
            'description' => $s->description ?? '',
            'price' => $s->prix ? number_format($s->prix, 2) . ' €' : '',
            'duration' => $s->duree ? $s->duree . ' min' : '',
        ])->toArray();
    }
    
    $columns = $settings['columns'] ?? 3;
    $layout = $settings['layout'] ?? 'grid';
    
    $colClass = match($columns) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-2 lg:grid-cols-3',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3'
    };
@endphp

<section class="py-16 md:py-24 px-4" id="services">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
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
        
        @if(count($items) > 0)
            <div class="grid grid-cols-1 {{ $colClass }} gap-6 md:gap-8">
                @foreach($items as $service)
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow border border-slate-100 dark:border-slate-700">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: var(--site-primary);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        
                        <h3 class="text-xl font-bold mb-2" style="color: var(--site-text); font-family: var(--site-font-heading);">
                            {{ $service['name'] }}
                        </h3>
                        
                        @if(!empty($service['description']))
                            <p class="text-slate-600 dark:text-slate-400 mb-4" style="font-family: var(--site-font-body);">
                                {{ $service['description'] }}
                            </p>
                        @endif
                        
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700">
                            @if(!empty($service['price']))
                                <span class="text-lg font-bold" style="color: var(--site-primary);">
                                    {{ $service['price'] }}
                                </span>
                            @endif
                            @if(!empty($service['duration']))
                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $service['duration'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <p>Aucun service défini</p>
            </div>
        @endif
    </div>
</section>
