{{-- Sidebar de navigation – consomme NavigationService --}}
@props(['items', 'activeTab' => 'accueil', 'context' => 'dashboard'])

@php
    use App\Services\NavigationService;

    $isPro = $context === 'entreprise';
    $navSurface = $isPro
        ? 'bg-slate-100/90 dark:bg-slate-800 border-orange-300/80 dark:border-orange-700/40'
        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700';
    $navTitle = match ($context) {
        'dashboard' => 'Tableau de Bord',
        'entreprise' => 'Mon entreprise',
        default => null,
    };
@endphp

<aside class="nav-sidebar hidden md:flex flex-col w-16 xl:w-64 flex-shrink-0 sticky top-20 self-start h-[calc(100vh-6rem)] overflow-y-auto">
    <nav class="{{ $navSurface }} rounded-xl shadow-sm border p-2 xl:p-3 space-y-1">
        @if($navTitle)
            <div class="hidden xl:block px-3 py-2 mb-1">
                <p class="text-xs font-semibold uppercase tracking-wider {{ $isPro ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $navTitle }}</p>
            </div>
        @endif
        @foreach($items as $item)
            @if(isset($item['separator']))
                <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>
                @continue
            @endif

            @if(!($item['visible'] ?? true))
                @continue
            @endif

            @php
                $isActive = $activeTab === ($item['tab'] ?? $item['key']);
                $iconPath = NavigationService::getIconPath($item['icon'] ?? '');
                $iconExtraPath = isset($item['icon_extra']) ? NavigationService::getIconPath($item['icon_extra']) : null;
                $badgeValue = $item['badge'] ?? null;
                $badgeColor = $item['badge_color'] ?? 'green';
                $labelClass = $item['label_class'] ?? '';
                $isLink = $item['is_link'] ?? false;
                $isLocked = $item['locked'] ?? false;

                $baseClasses = 'sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative';
                $activeClasses = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
                $inactiveClasses = 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white';
            @endphp

            {{-- Lien externe (Site Web actif) --}}
            @if($isLink && !$isLocked && ($item['url'] ?? null))
                <a 
                    href="{{ $item['url'] }}"
                    class="w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 text-blue-700 dark:text-blue-400 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 border border-blue-200/50 dark:border-blue-800/50"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path></svg>
                    <span class="hidden xl:inline font-semibold">{{ $item['label'] }}</span>
                    <svg class="hidden xl:inline w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">{{ $item['label'] }}</span>
                </a>
            @elseif($isLink && $isLocked)
                {{-- Lien verrouillé (Site Web non actif) --}}
                <button 
                    onclick="document.getElementById('site-web-upsell-overlay')?.classList.remove('hidden')"
                    class="w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-600 dark:hover:text-slate-300"
                >
                    <div class="relative flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path></svg>
                        <svg class="w-3 h-3 absolute -bottom-0.5 -right-0.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    </div>
                    <span class="hidden xl:inline">{{ $item['label'] }}</span>
                    <svg class="hidden xl:inline w-3.5 h-3.5 ml-auto text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">{{ $item['label'] }} (verrouillé)</span>
                </button>
            @else
                {{-- Bouton standard (onglet) --}}
                <button 
                    onclick="showTab('{{ $item['tab'] ?? $item['key'] }}')"
                    class="{{ $baseClasses }} {{ $isActive ? $activeClasses : $inactiveClasses }}"
                    data-tab="{{ $item['tab'] ?? $item['key'] }}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                        @if($iconExtraPath)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconExtraPath }}"></path>
                        @endif
                    </svg>
                    <span class="hidden xl:inline {{ $labelClass }}">{{ $item['label'] }}</span>

                    {{-- Badge numérique --}}
                    @if($badgeValue && $badgeValue !== 'dot-red')
                        <span class="xl:ml-auto px-2 py-0.5 text-xs bg-{{ $badgeColor }}-500 text-white rounded-full">{{ $badgeValue }}</span>
                    @endif

                    {{-- Badge point (sécurité) --}}
                    @if($badgeValue === 'dot-red')
                        <span class="w-2 h-2 bg-red-500 rounded-full xl:ml-auto"></span>
                    @endif

                    {{-- Tooltip tablette (icônes seuls) --}}
                    <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">{{ $item['label'] }}</span>
                </button>
            @endif
        @endforeach
    </nav>
</aside>
