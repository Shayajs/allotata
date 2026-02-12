{{-- Bloc spécial : Bouton "Se connecter" avec overlay popup --}}
@php
    $user = auth()->user();
    $popupUrl = route('auth.popup');
@endphp

<section class="py-12 px-4">
    <div class="max-w-2xl mx-auto text-center">
        @if($user)
            {{-- Utilisateur connecté --}}
            <div class="inline-flex items-center gap-3 px-6 py-4 rounded-2xl border"
                 style="border-color: color-mix(in srgb, var(--site-primary) 25%, transparent); background: color-mix(in srgb, var(--site-primary) 5%, var(--site-background));">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background: var(--site-primary);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="text-left">
                    <p class="font-semibold text-sm" style="color: var(--site-text);">{{ $user->name }}</p>
                    <p class="text-xs opacity-50" style="color: var(--site-text);">Vous êtes connecté</p>
                </div>
            </div>
        @else
            <h2 class="text-2xl font-bold mb-3" style="font-family: var(--site-font-heading); color: var(--site-text);">
                {{ $block['content']['title'] ?? 'Connectez-vous' }}
            </h2>
            <p class="opacity-60 mb-6" style="color: var(--site-text);">
                {{ $block['content']['subtitle'] ?? 'Accédez à votre espace personnel pour gérer vos réservations' }}
            </p>
            <button type="button"
                    onclick="window.__openAuthPopup ? window.__openAuthPopup() : window.open('{{ $popupUrl }}', 'allotata_auth', 'width=500,height=650,left=' + ((screen.width-500)/2) + ',top=' + ((screen.height-650)/2))"
                    class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90 cursor-pointer"
                    style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                {{ $block['content']['buttonText'] ?? 'Se connecter' }}
            </button>
        @endif
    </div>
</section>
