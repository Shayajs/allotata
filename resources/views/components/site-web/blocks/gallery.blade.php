@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $columns = $content['columns'] ?? 3;
    $gapClass = match($settings['gap'] ?? 'medium') {
        'small' => 'gap-2',
        'medium' => 'gap-4',
        'large' => 'gap-6',
        default => 'gap-4'
    };
    
    $roundedClass = ($settings['rounded'] ?? true) ? 'rounded-lg' : '';
    
    // Utiliser les images du bloc ou les photos de réalisations de l'entreprise
    $images = $content['images'] ?? [];
    $useEntreprisePhotos = empty($images) && $entreprise->realisationPhotos && $entreprise->realisationPhotos->count() > 0;
    
    if ($useEntreprisePhotos) {
        $images = $entreprise->realisationPhotos->map(function($photo) {
            return [
                'src' => $photo->photo_path,
                'alt' => $photo->titre ?? 'Photo de réalisation',
                'title' => $photo->titre
            ];
        })->toArray();
    }
    
    $colClass = match($columns) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3'
    };
@endphp

<section class="py-12 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        @if(!empty($content['title']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-10"
                style="font-family: var(--site-font-heading); color: var(--site-text);"
                @if($editMode) data-editable="title" @endif>
                {{ $content['title'] }}
            </h2>
        @endif
        
        @if(count($images) > 0)
            <div class="grid grid-cols-1 {{ $colClass }} {{ $gapClass }}">
                @foreach($images as $index => $image)
                    <div class="group relative aspect-square overflow-hidden {{ $roundedClass }} bg-slate-200 dark:bg-slate-700 cursor-pointer"
                         onclick="openLightbox({{ $index }})">
                        <img 
                            src="{{ str_starts_with($image['src'], 'http') ? $image['src'] : asset('storage/' . $image['src']) }}" 
                            alt="{{ $image['alt'] ?? 'Image' }}"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                            loading="lazy"
                        >
                        
                        {{-- Overlay au survol --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="absolute bottom-0 left-0 right-0 p-4">
                                @if(!empty($image['title']))
                                    <p class="text-white font-medium">{{ $image['title'] }}</p>
                                @endif
                            </div>
                            
                            {{-- Icône zoom --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="w-12 h-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Lightbox --}}
            <div id="gallery-lightbox" class="fixed inset-0 z-50 hidden bg-black/95 flex items-center justify-center">
                <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white/80 hover:text-white transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
                <button onclick="prevImage()" class="absolute left-4 text-white/80 hover:text-white transition">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <img id="lightbox-image" src="" alt="" class="max-h-[90vh] max-w-[90vw] object-contain">
                
                <button onclick="nextImage()" class="absolute right-4 text-white/80 hover:text-white transition">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
            
            <script>
                const galleryImages = @json(collect($images)->map(fn($img) => str_starts_with($img['src'], 'http') ? $img['src'] : asset('storage/' . $img['src'])));
                let currentImageIndex = 0;
                
                function openLightbox(index) {
                    currentImageIndex = index;
                    document.getElementById('lightbox-image').src = galleryImages[index];
                    document.getElementById('gallery-lightbox').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
                
                function closeLightbox() {
                    document.getElementById('gallery-lightbox').classList.add('hidden');
                    document.body.style.overflow = '';
                }
                
                function nextImage() {
                    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                    document.getElementById('lightbox-image').src = galleryImages[currentImageIndex];
                }
                
                function prevImage() {
                    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                    document.getElementById('lightbox-image').src = galleryImages[currentImageIndex];
                }
                
                document.getElementById('gallery-lightbox')?.addEventListener('click', function(e) {
                    if (e.target === this) closeLightbox();
                });
                
                document.addEventListener('keydown', function(e) {
                    if (document.getElementById('gallery-lightbox').classList.contains('hidden')) return;
                    if (e.key === 'Escape') closeLightbox();
                    if (e.key === 'ArrowRight') nextImage();
                    if (e.key === 'ArrowLeft') prevImage();
                });
            </script>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p>Aucune image dans la galerie</p>
            </div>
        @endif
    </div>
</section>
