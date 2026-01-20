@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $videoType = $content['type'] ?? 'external'; // external ou upload
    $url = $content['url'] ?? '';
    $file = $content['file'] ?? '';
    $aspectRatio = $settings['aspectRatio'] ?? '16:9';
    $isPinned = $settings['pinned'] ?? false;
    
    // Convertir l'URL en embed pour services externes
    $embedUrl = '';
    $videoUrl = '';
    
    if ($videoType === 'upload' && $file) {
        // Les fichiers de la médiathèque sont accessibles via /media/{path}
        // Si le chemin commence par "media/", utiliser directement
        // Sinon, utiliser asset('storage/...') pour compatibilité
        if (strpos($file, 'media/') === 0) {
            $videoUrl = url('/media/' . $file);
        } else {
            $videoUrl = asset('storage/' . $file);
        }
    } elseif ($videoType === 'external' && $url) {
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
    
    $pinClass = $isPinned ? 'video-pinned' : '';
    $pinContainerClass = $isPinned ? 'video-pinned-container' : '';
@endphp

<section class="py-8 md:py-12 px-4 {{ $pinContainerClass }}" data-video-block-id="{{ $block['id'] ?? '' }}">
    <div class="{{ $isPinned ? 'video-sticky-wrapper' : 'max-w-4xl mx-auto' }} {{ $pinClass }}">
        @if($embedUrl)
            {{-- Service externe (iframe) --}}
            <div class="{{ $aspectClass }} rounded-xl overflow-hidden shadow-xl bg-black video-player">
                <iframe 
                    src="{{ $embedUrl }}"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                    data-video-id="{{ $block['id'] ?? '' }}"
                ></iframe>
            </div>
        @elseif($videoUrl)
            {{-- Vidéo uploadée (video tag) --}}
            <div class="{{ $aspectClass }} rounded-xl overflow-hidden shadow-xl bg-black video-player relative">
                <video 
                    class="w-full h-full"
                    controls
                    preload="metadata"
                    data-video-id="{{ $block['id'] ?? '' }}"
                    data-video-pinned="{{ $isPinned ? 'true' : 'false' }}"
                >
                    <source src="{{ $videoUrl }}" type="video/mp4">
                    <source src="{{ $videoUrl }}" type="video/webm">
                    <source src="{{ $videoUrl }}" type="video/ogg">
                    Votre navigateur ne supporte pas la balise vidéo.
                </video>
                {{-- Contrôle audio pour mobile quand vidéo hors écran --}}
                <div class="video-audio-control hidden absolute bottom-4 right-4 bg-black/80 backdrop-blur-sm rounded-lg p-2 z-10">
                    <button type="button" class="video-play-pause text-white p-2 hover:bg-white/20 rounded transition">
                        <svg class="w-6 h-6 play-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <svg class="w-6 h-6 pause-icon hidden" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <div class="{{ $aspectClass }} rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                <div class="text-center text-slate-500 dark:text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Ajoutez une vidéo</p>
                </div>
            </div>
        @endif
    </div>
</section>
