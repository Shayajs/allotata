{{-- Barre d'onglets mobile (scrollable horizontalement) – cachée en PWA et sur md+ --}}
@props(['items', 'activeTab' => 'accueil'])

@php
    use App\Services\NavigationService;
    $filteredItems = NavigationService::filterItems($items);
@endphp

<nav class="nav-mobile-tabs md:hidden mb-4 -mx-4 px-4 overflow-x-auto scrollbar-hide" aria-label="Onglets navigation">
    <div class="flex gap-2 pb-2 min-w-0">
        @foreach($filteredItems as $item)
            @php
                $isActive = $activeTab === ($item['tab'] ?? $item['key']);
                $iconPath = NavigationService::getIconPath($item['icon'] ?? '');
                $isLink = $item['is_link'] ?? false;
                $isLocked = $item['locked'] ?? false;
            @endphp

            @if($isLink && $isLocked)
                @continue
            @endif

            @if($isLink && !$isLocked && ($item['url'] ?? null))
                <a 
                    href="{{ $item['url'] }}"
                    class="flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path></svg>
                    {{ $item['label'] }}
                </a>
            @else
                <button 
                    type="button" 
                    onclick="showTab('{{ $item['tab'] ?? $item['key'] }}')"
                    class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border {{ $isActive ? 'border-green-300 dark:border-green-700 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                    data-tab="{{ $item['tab'] ?? $item['key'] }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                        @if(isset($item['icon_extra']))
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ NavigationService::getIconPath($item['icon_extra']) }}"></path>
                        @endif
                    </svg>
                    {{ $item['label'] }}
                    @if(($item['badge'] ?? null) && $item['badge'] !== 'dot-red')
                        <span class="px-1.5 py-0.5 text-xs bg-{{ $item['badge_color'] ?? 'green' }}-500 text-white rounded-full">{{ $item['badge'] }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</nav>
