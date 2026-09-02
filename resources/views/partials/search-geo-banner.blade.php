@php
    $visitorLocation = $visitorLocation ?? [];
    $geoSource = $visitorLocation['source'] ?? '';
@endphp
@if($geoSource !== 'browser')
<div data-geo-banner class="hidden mb-6 relative overflow-hidden rounded-xl">
    <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white">
        <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-3 text-sm">
            <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="hidden sm:inline">Autorisez la géolocalisation pour voir les entreprises près de vous.</span>
                <span class="sm:hidden">Résultats près de vous ?</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button" data-geo-banner-activate
                    class="px-3 py-1 bg-white text-green-700 text-xs font-semibold rounded-md hover:bg-green-50 transition-colors">
                    Autoriser
                </button>
                <button type="button" data-geo-banner-dismiss
                    class="p-1 hover:bg-green-700/50 rounded transition-colors" aria-label="Plus tard">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
