@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $src = $content['src'] ?? '';
    $height = $content['height'] ?? 400;
    $fullWidth = $settings['fullWidth'] ?? true;
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="{{ $fullWidth ? 'max-w-6xl' : 'max-w-4xl' }} mx-auto">
        @if($src)
            <div class="rounded-xl overflow-hidden shadow-lg bg-white dark:bg-slate-800">
                <iframe 
                    src="{{ $src }}"
                    class="w-full"
                    style="height: {{ $height }}px;"
                    frameborder="0"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        @else
            <div class="rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center" style="height: {{ $height }}px;">
                <div class="text-center text-slate-500 dark:text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    <p>Ajoutez une URL dans les propriétés</p>
                </div>
            </div>
        @endif
    </div>
</section>
