@php
    $visitorLocation = $visitorLocation ?? [];
    $geoSource = $visitorLocation['source'] ?? '';
    $geoActive = $geoSource === 'browser';
@endphp
<div
    class="search-geo flex flex-wrap items-center gap-3"
    data-search-geo-root
    data-geo-source="{{ $geoSource }}"
    @if($geoActive)
        data-geo-lat="{{ $visitorLocation['latitude'] }}"
        data-geo-lng="{{ $visitorLocation['longitude'] }}"
    @endif
>
    <button
        type="button"
        data-geo-enable
        class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-700 dark:hover:text-green-300 flex items-center gap-1.5 px-2 py-1 rounded-lg transition {{ $geoActive ? 'ring-2 ring-green-500 text-green-700 dark:text-green-300' : '' }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span data-geo-label>{{ $geoActive ? 'Position activée' : 'Utiliser ma position' }}</span>
    </button>
    <button
        type="button"
        data-geo-disable
        class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 {{ $geoActive ? '' : 'hidden' }}"
    >
        Ne plus utiliser
    </button>
    <p data-geo-status class="hidden text-xs w-full sm:w-auto"></p>
</div>
