{{-- Navigation : Onglets horizontaux (style Material) --}}
@props(['pages', 'currentPage', 'entreprise', 'slug'])

@if($pages->count() > 1)
<div class="border-b" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex gap-0 overflow-x-auto scrollbar-hide -mb-px" aria-label="Onglets">
            @foreach($pages as $page)
                @php
                    $isActive = $currentPage && $currentPage->id === $page->id;
                    $url = route('site-web.show', ['slug' => $slug]) . '?tab=' . $page->slug;
                    $icon = $page->effective_icon;
                @endphp
                <a href="{{ $url }}"
                   class="relative flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2 {{ $isActive ? '' : 'border-transparent hover:border-current opacity-60 hover:opacity-80' }}"
                   style="{{ $isActive ? 'color: var(--site-primary); border-color: var(--site-primary);' : 'color: var(--site-text);' }}">
                    @include('components.site-web.navigation.icon', ['icon' => $icon, 'class' => 'w-4 h-4'])
                    <span>{{ $page->nom }}</span>
                </a>
            @endforeach
        </nav>
    </div>
</div>
@endif
