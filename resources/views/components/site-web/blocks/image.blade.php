@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $sizeClass = match($settings['size'] ?? 'medium') {
        'small' => 'max-w-sm',
        'medium' => 'max-w-2xl',
        'large' => 'max-w-4xl',
        'full' => 'max-w-full',
        default => 'max-w-2xl'
    };
    
    $roundedClass = ($settings['rounded'] ?? true) ? 'rounded-xl' : '';
    $shadowClass = ($settings['shadow'] ?? true) ? 'shadow-xl' : '';
    
    $src = $content['src'] ?? null;
    $alt = $content['alt'] ?? 'Image';
    $caption = $content['caption'] ?? null;
@endphp

<section class="py-8 md:py-12 px-4">
    <figure class="{{ $sizeClass }} mx-auto">
        @if($src)
            <div class="overflow-hidden {{ $roundedClass }} {{ $shadowClass }}">
                <img 
                    src="{{ str_starts_with($src, 'http') ? $src : route('storage.serve', ['path' => $src]) }}" 
                    alt="{{ $alt }}"
                    class="w-full h-auto transition-transform duration-500 hover:scale-105"
                    loading="lazy"
                >
            </div>
            
            @if($caption)
                <figcaption class="mt-4 text-center text-sm text-slate-600 dark:text-slate-400 italic"
                            style="font-family: var(--site-font-body);"
                            @if($editMode) data-editable="caption" @endif>
                    {{ $caption }}
                </figcaption>
            @endif
        @else
            <div class="aspect-video bg-slate-200 dark:bg-slate-700 {{ $roundedClass }} flex items-center justify-center">
                <div class="text-center text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p>Aucune image</p>
                </div>
            </div>
        @endif
    </figure>
</section>
