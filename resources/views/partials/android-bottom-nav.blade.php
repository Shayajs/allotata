@php
    use App\Services\NavigationService;
    $context = $context ?? 'dashboard';
    $activeTab = $activeTab ?? 'accueil';
    $items = $items ?? [];
    $bottomItems = $items !== []
        ? NavigationService::getPwaBottomItems($items)
        : [];
    $allItems = $items !== []
        ? array_values(array_filter(
            NavigationService::filterItems($items),
            fn ($item) => ($item['key'] ?? '') !== 'installer'
        ))
        : [];
    $moreItems = array_values(array_filter($allItems, fn ($i) => ! ($i['pwa_bottom'] ?? false)));
@endphp

<nav class="android-bottom-nav" aria-label="Navigation application">
    <div class="flex items-stretch justify-around w-full h-16 px-1">
        @forelse($bottomItems as $bItem)
            @php
                $isActive = $activeTab === ($bItem['tab'] ?? $bItem['key']);
                $iconPath = NavigationService::getIconPath($bItem['icon'] ?? '');
                $badge = $bItem['badge'] ?? null;
            @endphp
            <button
                type="button"
                onclick="showTab('{{ $bItem['tab'] ?? $bItem['key'] }}')"
                class="android-tab-btn flex flex-col items-center justify-center flex-1 gap-1 {{ $isActive ? 'text-white' : 'text-slate-400' }}"
                data-tab="{{ $bItem['tab'] ?? $bItem['key'] }}"
            >
                <span class="relative flex items-center justify-center w-12 h-8 rounded-full {{ $isActive ? 'bg-green-500/25' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $isActive ? '2.4' : '1.7' }}"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"></path></svg>
                    @if($badge && $badge !== 'dot-red')
                        <span class="absolute -top-1 -right-1 min-w-[14px] h-3.5 px-1 text-[9px] font-bold bg-orange-500 text-white rounded-full flex items-center justify-center">{{ $badge }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-none">{{ $bItem['short_label'] ?? $bItem['label'] }}</span>
            </button>
        @empty
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 gap-1 text-slate-200">
                <span class="flex items-center justify-center w-12 h-8 rounded-full bg-green-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </span>
                <span class="text-[10px] font-medium">Accueil</span>
            </a>
            <a href="{{ route('settings.index') }}" class="flex flex-col items-center justify-center flex-1 gap-1 text-slate-400">
                <span class="flex items-center justify-center w-12 h-8">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                </span>
                <span class="text-[10px] font-medium">Réglages</span>
            </a>
        @endforelse

        @if($items !== [])
            <button
                type="button"
                onclick="openAndroidMoreSheet('{{ $context }}')"
                class="flex flex-col items-center justify-center flex-1 gap-1 text-slate-400"
            >
                <span class="flex items-center justify-center w-12 h-8">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ NavigationService::getIconPath('dots-h') }}"></path></svg>
                </span>
                <span class="text-[10px] font-medium">Plus</span>
            </button>
        @endif
    </div>
</nav>

@if($items !== [])
<div id="android-more-overlay-{{ $context }}" class="android-more-overlay fixed inset-0 z-[60] bg-black/50 hidden" onclick="closeAndroidMoreSheet('{{ $context }}')"></div>
<div id="android-more-sheet-{{ $context }}" class="android-more-sheet fixed bottom-0 left-0 right-0 z-[70] bg-slate-900 text-slate-100 rounded-t-3xl shadow-2xl transform translate-y-full transition-transform duration-300 max-h-[70vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="flex justify-center pt-3 pb-2">
        <div class="w-10 h-1 bg-slate-600 rounded-full"></div>
    </div>
    <div class="px-4 pb-2">
        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Plus</h3>
    </div>
    <div class="px-2 pb-6 space-y-1">
        @auth
            @if(auth()->user()->is_admin)
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-300 hover:bg-white/5">Administration</a>
            @endif
            @if($context === 'dashboard')
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5">Paramètres</a>
                <a href="{{ route('checkout.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5">Espace Paiement</a>
            @endif
            @if($context === 'settings')
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5">Tableau de bord</a>
            @endif
        @endauth
        @foreach($moreItems as $mItem)
            @php
                $isLink = $mItem['is_link'] ?? false;
                $isLocked = $mItem['locked'] ?? false;
            @endphp
            @if($isLink && $isLocked)
                @continue
            @endif
            @if($isLink && ($mItem['url'] ?? null))
                <a href="{{ $mItem['url'] }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5">{{ $mItem['label'] }}</a>
            @else
                <button type="button" onclick="showTab('{{ $mItem['tab'] ?? $mItem['key'] }}'); closeAndroidMoreSheet('{{ $context }}');" class="w-full text-left px-4 py-3 rounded-xl hover:bg-white/5">
                    {{ $mItem['label'] }}
                </button>
            @endif
        @endforeach
    </div>
</div>
<script>
    function openAndroidMoreSheet(context) {
        document.getElementById('android-more-sheet-' + context)?.classList.remove('translate-y-full');
        document.getElementById('android-more-overlay-' + context)?.classList.remove('hidden');
    }
    function closeAndroidMoreSheet(context) {
        document.getElementById('android-more-sheet-' + context)?.classList.add('translate-y-full');
        document.getElementById('android-more-overlay-' + context)?.classList.add('hidden');
    }
</script>
@endif
