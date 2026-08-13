{{-- Bloc spécial : Widget de réservation inline (placé dans un onglet custom) --}}
@php
    $slug = $entreprise->slug_web ?? $entreprise->slug;
    $reservationTabUrl = route('site-web.show', ['slug' => $slug]) . '?tab=reservation';
@endphp

<section class="py-12 px-4">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl font-bold mb-4" style="font-family: var(--site-font-heading); color: var(--site-text);">
            {{ $block['content']['title'] ?? 'Prendre rendez-vous' }}
        </h2>
        <p class="text-lg opacity-60 mb-8 max-w-2xl mx-auto" style="color: var(--site-text);">
            @if($entreprise->prendRdvSurDemande())
                {{ $block['content']['subtitle'] ?? 'Les rendez-vous se prennent sur demande. Contactez-nous pour convenir d’un créneau.' }}
            @else
                {{ $block['content']['subtitle'] ?? 'Réservez votre créneau en quelques clics' }}
            @endif
        </p>

        {{-- Aperçu des services --}}
        @if($entreprise->typesServices && $entreprise->typesServices->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                @foreach($entreprise->typesServices->where('est_actif', true)->take(3) as $service)
                    <div class="p-4 rounded-xl border text-left" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                        <h3 class="font-semibold text-sm mb-1" style="color: var(--site-text);">{{ $service->nom }}</h3>
                        <div class="flex items-center gap-2 text-sm opacity-60">
                            @if($service->prix)<span style="color: var(--site-primary); font-weight: 600;">{{ number_format($service->prix, 0, ',', ' ') }}&euro;</span>@endif
                            @if($service->duree_minutes && !$service->estDateButoire())<span>&bull; {{ $service->duree_minutes }} min</span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <a href="{{ $reservationTabUrl }}"
           class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90"
           style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
            {{ $entreprise->prendRdvSurDemande() ? ($block['content']['buttonText'] ?? 'Demander un rendez-vous') : ($block['content']['buttonText'] ?? 'Réserver maintenant') }}
        </a>
    </div>
</section>
