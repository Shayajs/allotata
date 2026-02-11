{{-- Navigation : Navbar horizontale classique --}}
@props(['pages', 'currentPage', 'entreprise', 'slug'])

@if($pages->count() > 1)
<nav class="sticky top-0 z-40 border-b backdrop-blur-md"
     style="background: color-mix(in srgb, var(--site-background) 85%, transparent); border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            {{-- Logo / Nom --}}
            <a href="{{ route('site-web.show', ['slug' => $slug]) }}"
               class="flex items-center gap-2 text-sm font-semibold truncate"
               style="color: var(--site-text);">
                @if(!empty($entreprise->logo))
                    <img src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" alt="" class="w-7 h-7 rounded-lg object-cover">
                @endif
                <span class="hidden sm:inline">{{ $entreprise->nom }}</span>
            </a>

            {{-- Liens --}}
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide">
                @foreach($pages as $page)
                    @php
                        $isActive = $currentPage && $currentPage->id === $page->id;
                        $url = route('site-web.show', ['slug' => $slug]) . '?tab=' . $page->slug;
                    @endphp
                    <a href="{{ $url }}"
                       class="px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ $isActive ? 'font-bold' : 'opacity-70 hover:opacity-100' }}"
                       style="{{ $isActive ? 'background: var(--site-primary); color: white;' : 'color: var(--site-text);' }}">
                        {{ $page->nom }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>
@endif
