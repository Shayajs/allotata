{{-- Bottom Tab Bar – Visible uniquement en PWA mobile via CSS --}}
@props(['items', 'activeTab' => 'accueil', 'context' => 'dashboard'])

@php
    use App\Services\NavigationService;
    $bottomItems = NavigationService::getPwaBottomItems($items);
    $allItems = NavigationService::filterItems($items);
    // Items restants (pas dans la bottom bar) pour la sheet "Plus"
    $moreItems = array_values(array_filter($allItems, fn($i) => !($i['pwa_bottom'] ?? false)));
@endphp

<div class="pwa-bottom-bar fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-[0_-2px_10px_rgba(0,0,0,0.08)]" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <nav class="flex items-stretch justify-around h-16 w-full px-2" aria-label="Navigation principale">
        @foreach($bottomItems as $bItem)
            @php
                $isActive = $activeTab === ($bItem['tab'] ?? $bItem['key']);
                $iconPath = NavigationService::getIconPath($bItem['icon'] ?? '');
                $badge = $bItem['badge'] ?? null;
                $badgeColor = $bItem['badge_color'] ?? 'green';
            @endphp
            <button 
                onclick="showTab('{{ $bItem['tab'] ?? $bItem['key'] }}')"
                class="pwa-tab-btn flex flex-col items-center justify-center flex-1 gap-0.5 relative transition-colors {{ $isActive ? 'text-green-600 dark:text-green-400' : 'text-slate-400 dark:text-slate-500' }}"
                data-tab="{{ $bItem['tab'] ?? $bItem['key'] }}"
            >
                @if($isActive)
                    <span class="pwa-active-indicator absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-green-500 rounded-full"></span>
                @endif
                <span class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $isActive ? '2.5' : '1.5' }}"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"></path></svg>
                    @if($badge && $badge !== 'dot-red')
                        <span class="absolute -top-1 -right-2 min-w-[16px] h-4 px-1 text-[10px] font-bold bg-{{ $badgeColor }}-500 text-white rounded-full flex items-center justify-center">{{ $badge }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-tight text-center">{{ $bItem['short_label'] ?? $bItem['label'] }}</span>
            </button>
        @endforeach

        {{-- Bouton "Plus" --}}
        <button 
            onclick="document.getElementById('pwa-more-sheet-{{ $context }}').classList.remove('translate-y-full'); document.getElementById('pwa-more-overlay-{{ $context }}').classList.remove('hidden');"
            class="pwa-tab-btn flex flex-col items-center justify-center flex-1 gap-0.5 text-slate-400 dark:text-slate-500"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ NavigationService::getIconPath('dots-h') }}"></path></svg>
            <span class="text-[10px] font-medium leading-tight text-center">Plus</span>
        </button>
    </nav>
</div>

{{-- Sheet "Plus" (slide-up drawer) --}}
<div id="pwa-more-overlay-{{ $context }}" class="pwa-more-overlay fixed inset-0 z-[60] bg-black/40 hidden transition-opacity" onclick="closePwaMoreSheet('{{ $context }}')"></div>
<div id="pwa-more-sheet-{{ $context }}" class="pwa-more-sheet fixed bottom-0 left-0 right-0 z-[70] bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 ease-out max-h-[70vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    {{-- Handle --}}
    <div class="flex justify-center pt-3 pb-2">
        <div class="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
    </div>
    <div class="px-4 pb-2">
        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Plus</h3>
    </div>
    <div class="px-2 pb-6 space-y-1">
        @foreach($moreItems as $mItem)
            @php
                $isActive = $activeTab === ($mItem['tab'] ?? $mItem['key']);
                $iconPath = NavigationService::getIconPath($mItem['icon'] ?? '');
                $isLink = $mItem['is_link'] ?? false;
                $isLocked = $mItem['locked'] ?? false;
                $badge = $mItem['badge'] ?? null;
                $badgeColor = $mItem['badge_color'] ?? 'green';
            @endphp

            @if($isLink && $isLocked)
                @continue
            @endif

            @if($isLink && !$isLocked && ($mItem['url'] ?? null))
                <a href="{{ $mItem['url'] }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path></svg>
                    <span class="text-sm font-medium">{{ $mItem['label'] }}</span>
                    <svg class="w-4 h-4 ml-auto text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            @else
                <button 
                    onclick="showTab('{{ $mItem['tab'] ?? $mItem['key'] }}'); closePwaMoreSheet('{{ $context }}');"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-colors {{ $isActive ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                    data-tab="{{ $mItem['tab'] ?? $mItem['key'] }}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                        @if(isset($mItem['icon_extra']))
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ NavigationService::getIconPath($mItem['icon_extra']) }}"></path>
                        @endif
                    </svg>
                    <span class="text-sm font-medium {{ $mItem['label_class'] ?? '' }}">{{ $mItem['label'] }}</span>
                    @if($badge && $badge !== 'dot-red')
                        <span class="ml-auto px-2 py-0.5 text-xs bg-{{ $badgeColor }}-500 text-white rounded-full">{{ $badge }}</span>
                    @endif
                    @if($badge === 'dot-red')
                        <span class="ml-auto w-2 h-2 bg-red-500 rounded-full"></span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</div>

<script>
    function closePwaMoreSheet(context) {
        const sheet = document.getElementById('pwa-more-sheet-' + context);
        const overlay = document.getElementById('pwa-more-overlay-' + context);
        if (sheet) sheet.classList.add('translate-y-full');
        if (overlay) overlay.classList.add('hidden');
    }
</script>
