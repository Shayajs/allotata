@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $url = $content['url'] ?? '';
    $aspectRatio = $settings['aspectRatio'] ?? '16:9';
    
    // Convertir l'URL en embed
    $embedUrl = '';
    if ($url) {
        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?\/]+)/', $url, $matches)) {
            $embedUrl = "https://www.youtube.com/embed/{$matches[1]}";
        }
        // Vimeo
        elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $embedUrl = "https://player.vimeo.com/video/{$matches[1]}";
        }
        else {
            $embedUrl = $url;
        }
    }
    
    $aspectClass = match($aspectRatio) {
        '16:9' => 'aspect-video',
        '4:3' => 'aspect-[4/3]',
        '1:1' => 'aspect-square',
        default => 'aspect-video'
    };
@endphp

<section class="py-12 md:py-16 px-4">
    <div class="max-w-4xl mx-auto">
        @if($embedUrl)
            <div class="{{ $aspectClass }} rounded-xl overflow-hidden shadow-xl bg-black">
                <iframe 
                    src="{{ $embedUrl }}"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        @else
            <div class="{{ $aspectClass }} rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                <div class="text-center text-slate-500 dark:text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Ajoutez une URL de vidéo</p>
                </div>
            </div>
        @endif
    </div>
</section>
