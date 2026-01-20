@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $src = $content['src'] ?? '';
    $height = $settings['height'] ?? '400';
    $rounded = $settings['rounded'] ?? true;
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="max-w-5xl mx-auto">
        @if($src)
            <div class="{{ $rounded ? 'rounded-xl' : '' }} overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700">
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
        @else
            <div class="rounded-xl bg-slate-100 dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center" style="height: {{ $height }}px;">
                <div class="text-center p-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400">Ajoutez une URL d'iframe</p>
                </div>
            </div>
        @endif
    </div>
</section>
