{{-- Bloc spécial : Mini-calendrier inline (aperçu) --}}
@php
    $slug = $entreprise->slug_web ?? $entreprise->slug;
    $agendaTabUrl = route('site-web.show', ['slug' => $slug]) . '?tab=reservation';
@endphp

<section class="py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-2" style="font-family: var(--site-font-heading); color: var(--site-text);">
                {{ $block['content']['title'] ?? 'Notre agenda' }}
            </h2>
            <p class="opacity-60" style="color: var(--site-text);">
                @if($entreprise->prendRdvSurDemande())
                    {{ $block['content']['subtitle'] ?? 'Les rendez-vous se prennent sur demande.' }}
                @else
                    {{ $block['content']['subtitle'] ?? 'Consultez nos disponibilités et réservez en ligne' }}
                @endif
            </p>
        </div>

        @if($entreprise->prendRdvSurDemande())
            <x-rdv-sur-demande :entreprise="$entreprise" />
        @else
        {{-- Aperçu jours de la semaine --}}
        <div class="grid grid-cols-7 gap-2 mb-6">
            @php
                $joursNoms = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                $aujourdhui = now();
            @endphp
            @for($i = 0; $i < 7; $i++)
                @php
                    $date = $aujourdhui->copy()->startOfWeek()->addDays($i);
                    $isToday = $date->isToday();
                @endphp
                <div class="text-center p-3 rounded-xl {{ $isToday ? 'ring-2' : '' }}"
                     style="{{ $isToday ? 'ring-color: var(--site-primary); background: color-mix(in srgb, var(--site-primary) 10%, transparent);' : '' }}">
                    <div class="text-xs font-medium uppercase opacity-50">{{ $joursNoms[$i] }}</div>
                    <div class="text-lg font-bold {{ $isToday ? '' : '' }}" style="{{ $isToday ? 'color: var(--site-primary);' : 'color: var(--site-text);' }}">
                        {{ $date->day }}
                    </div>
                </div>
            @endfor
        </div>

        <div class="text-center">
            <a href="{{ $agendaTabUrl }}"
               class="inline-block px-8 py-3 text-base font-semibold text-white transition hover:opacity-90"
               style="background: var(--site-primary); border-radius: var(--site-button-radius);">
                Voir les disponibilités
            </a>
        </div>
        @endif
    </div>
</section>
