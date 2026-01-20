@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $images = $content['images'] ?? [];
    $columns = $content['columns'] ?? 3;
    $title = $content['title'] ?? '';
    
    $colClass = match($columns) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        default => 'grid-cols-3'
    };
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-6xl mx-auto">
        @if($title)
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 text-center"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h3>
        @endif
        
        @if(count($images) > 0)
            <div class="grid {{ $colClass }} gap-4">
                @foreach($images as $image)
                    <div class="aspect-square overflow-hidden rounded-xl shadow-lg">
                        <img 
                            src="{{ str_starts_with($image['src'] ?? '', 'http') ? ($image['src'] ?? '') : asset('storage/' . ($image['src'] ?? '')) }}" 
                            alt="{{ $image['alt'] ?? 'Image' }}"
                            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300 cursor-pointer"
                            loading="lazy"
                        >
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid {{ $colClass }} gap-4">
                @for($i = 0; $i < $columns * 2; $i++)
                    <div class="aspect-square bg-slate-200 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                        <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endfor
            </div>
        @endif
    </div>
</section>
