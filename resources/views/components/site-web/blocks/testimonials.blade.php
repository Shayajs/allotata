@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Ce que disent nos clients';
    $items = $content['items'] ?? [];
    
    // Si pas d'items personnalisés, utiliser les avis de l'entreprise
    if (empty($items) && $entreprise->avis && $entreprise->avis->count() > 0) {
        $items = $entreprise->avis->take(6)->map(fn($a) => [
            'text' => $a->commentaire,
            'author' => $a->user->name ?? 'Client',
            'rating' => $a->note,
            'date' => $a->created_at->format('F Y'),
        ])->toArray();
    }
    
    $layout = $settings['layout'] ?? 'grid';
@endphp

<section class="py-16 md:py-24 px-4 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12"
            style="font-family: var(--site-font-heading); color: var(--site-text);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h2>
        
        @if(count($items) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($items as $testimonial)
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg relative">
                        {{-- Quote icon --}}
                        <div class="absolute top-4 right-4 opacity-10">
                            <svg class="w-12 h-12" style="color: var(--site-primary);" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
                            </svg>
                        </div>
                        
                        {{-- Rating --}}
                        @if(!empty($testimonial['rating']))
                            <div class="flex gap-1 mb-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-slate-300 dark:text-slate-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        @endif
                        
                        {{-- Text --}}
                        <p class="text-slate-700 dark:text-slate-300 mb-6 italic" style="font-family: var(--site-font-body);">
                            "{{ $testimonial['text'] }}"
                        </p>
                        
                        {{-- Author --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background: var(--site-primary);">
                                {{ strtoupper(substr($testimonial['author'], 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold" style="color: var(--site-text);">{{ $testimonial['author'] }}</p>
                                @if(!empty($testimonial['date']))
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $testimonial['date'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <p>Aucun témoignage disponible</p>
            </div>
        @endif
    </div>
</section>
