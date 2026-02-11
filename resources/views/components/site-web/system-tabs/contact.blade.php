{{-- Onglet système : Contact --}}
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <header class="mb-10 text-center">
        <h1 class="text-3xl font-bold mb-2" style="font-family: var(--site-font-heading); color: var(--site-text);">
            Nous contacter
        </h1>
        <p class="opacity-60 max-w-xl mx-auto" style="color: var(--site-text);">
            N'hésitez pas à nous contacter pour toute question ou demande
        </p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Coordonnées --}}
        <div class="space-y-6">
            @if($entreprise->email)
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--site-primary) 15%, transparent);">
                        <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold mb-0.5" style="color: var(--site-text);">Email</p>
                        <a href="mailto:{{ $entreprise->email }}" class="text-sm hover:underline" style="color: var(--site-primary);">{{ $entreprise->email }}</a>
                    </div>
                </div>
            @endif

            @if($entreprise->telephone)
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--site-primary) 15%, transparent);">
                        <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold mb-0.5" style="color: var(--site-text);">Téléphone</p>
                        <a href="tel:{{ $entreprise->telephone }}" class="text-sm hover:underline" style="color: var(--site-primary);">{{ $entreprise->telephone }}</a>
                    </div>
                </div>
            @endif

            @if($entreprise->adresse_rue || $entreprise->ville)
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--site-primary) 15%, transparent);">
                        <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold mb-0.5" style="color: var(--site-text);">Adresse</p>
                        <p class="text-sm opacity-70" style="color: var(--site-text);">
                            @if($entreprise->adresse_rue){{ $entreprise->adresse_rue }}<br>@endif
                            @if($entreprise->code_postal){{ $entreprise->code_postal }} @endif{{ $entreprise->ville }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Horaires --}}
            @php
                $horairesContact = $entreprise->horairesOuverture()
                    ->where('est_exceptionnel', false)
                    ->orderBy('jour_semaine')
                    ->orderBy('ordre_plage')
                    ->get()
                    ->groupBy('jour_semaine');
                $joursNoms = [0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
            @endphp
            @if($horairesContact->count() > 0)
                <div class="mt-6 p-5 rounded-2xl border" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                    <h3 class="text-sm font-bold mb-3" style="color: var(--site-text);">Horaires d'ouverture</h3>
                    <div class="space-y-1.5 text-sm">
                        @for($j = 1; $j <= 6; $j++)
                            @php $plages = $horairesContact->get($j, collect()); @endphp
                            <div class="flex justify-between">
                                <span class="font-medium opacity-70">{{ $joursNoms[$j] }}</span>
                                <span class="opacity-60">
                                    @if($plages->count() > 0)
                                        {{ $plages->map(fn($p) => \Carbon\Carbon::parse($p->heure_ouverture)->format('H:i') . ' - ' . \Carbon\Carbon::parse($p->heure_fermeture)->format('H:i'))->join(', ') }}
                                    @else
                                        Fermé
                                    @endif
                                </span>
                            </div>
                        @endfor
                        @php $plagesDim = $horairesContact->get(0, collect()); @endphp
                        <div class="flex justify-between">
                            <span class="font-medium opacity-70">Dimanche</span>
                            <span class="opacity-60">
                                @if($plagesDim->count() > 0)
                                    {{ $plagesDim->map(fn($p) => \Carbon\Carbon::parse($p->heure_ouverture)->format('H:i') . ' - ' . \Carbon\Carbon::parse($p->heure_fermeture)->format('H:i'))->join(', ') }}
                                @else
                                    Fermé
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Carte --}}
        @if($entreprise->latitude && $entreprise->longitude)
            <div class="rounded-2xl overflow-hidden border shadow-sm h-80 md:h-auto" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                <iframe
                    width="100%" height="100%"
                    style="border:0; min-height: 300px;"
                    loading="lazy"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $entreprise->longitude - 0.01 }}%2C{{ $entreprise->latitude - 0.01 }}%2C{{ $entreprise->longitude + 0.01 }}%2C{{ $entreprise->latitude + 0.01 }}&layer=mapnik&marker={{ $entreprise->latitude }}%2C{{ $entreprise->longitude }}"
                ></iframe>
            </div>
        @endif
    </div>
</div>
