{{-- Navigation : Sidebar latérale --}}
@props(['pages', 'currentPage', 'entreprise', 'slug'])

@if($pages->count() > 1)
<aside class="w-full lg:w-64 lg:min-h-screen lg:border-r flex-shrink-0"
       style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background: color-mix(in srgb, var(--site-background) 95%, var(--site-text) 5%);">
    
    {{-- Entête sidebar --}}
    <div class="p-4 lg:p-6 border-b lg:border-b" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
        <a href="{{ route('site-web.show', ['slug' => $slug]) }}" class="flex items-center gap-3">
            @if(!empty($entreprise->logo))
                <img src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" alt="" class="w-10 h-10 rounded-xl object-cover">
            @endif
            <div class="min-w-0">
                <div class="font-semibold text-sm truncate" style="color: var(--site-text);">{{ $entreprise->nom }}</div>
                @if($entreprise->phrase_accroche)
                    <div class="text-xs truncate opacity-60" style="color: var(--site-text);">{{ $entreprise->phrase_accroche }}</div>
                @endif
            </div>
        </a>
    </div>

    {{-- Liens de navigation --}}
    <nav class="p-2 lg:p-3 flex lg:flex-col gap-1 overflow-x-auto lg:overflow-x-visible">
        @foreach($pages as $page)
            @php
                $isActive = $currentPage && $currentPage->id === $page->id;
                $url = route('site-web.show', ['slug' => $slug]) . '?tab=' . $page->slug;
                $icon = $page->effective_icon;
            @endphp
            <a href="{{ $url }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl whitespace-nowrap transition-all {{ $isActive ? 'shadow-sm' : 'opacity-60 hover:opacity-100' }}"
               style="{{ $isActive ? 'background: var(--site-primary); color: white;' : 'color: var(--site-text);' }}">
                @include('components.site-web.navigation.icon', ['icon' => $icon, 'class' => 'w-4 h-4 flex-shrink-0'])
                <span>{{ $page->nom }}</span>
            </a>
        @endforeach
    </nav>
</aside>
@endif
