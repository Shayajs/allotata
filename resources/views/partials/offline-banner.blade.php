{{-- Bannière hors connexion (cachée par défaut, contrôlée par JS via is-offline) --}}
<div id="offline-banner" class="hidden offline-banner fixed top-0 left-0 right-0 z-[9999] bg-amber-500 text-white text-center py-2 px-4 text-sm font-medium shadow-lg">
    <div class="flex items-center justify-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 12h.01"/>
        </svg>
        <span>Hors connexion &mdash; mode lecture seule</span>
    </div>
</div>

{{-- Bannière de reconnexion (brièvement affichée quand la connexion revient) --}}
<div id="reconnect-banner" class="hidden fixed top-0 left-0 right-0 z-[9999] bg-green-500 text-white text-center py-2 px-4 text-sm font-medium shadow-lg transition-opacity">
    <div class="flex items-center justify-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Connexion rétablie</span>
    </div>
</div>
