{{-- Onglet système : Réservation (agenda + formulaire embarqué) --}}
@php
    $reservationsUrl = route('site-web.reservations', ['slug' => $slug]);
    $storeUrl = route('site-web.reservation.store', ['slug' => $slug]);
    $reservationFormUrl = route('site-web.reservation-form', ['slug' => $slug]);
    $user = auth()->user();
@endphp

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- En-tête --}}
    <header class="mb-8">
        <div class="flex items-center gap-4">
            @if($entreprise->logo)
                <img src="/media/{{ $entreprise->logo }}" alt="{{ $entreprise->nom }}" class="w-14 h-14 rounded-xl object-cover shadow-md">
            @endif
            <div>
                <h1 class="text-3xl font-bold" style="font-family: var(--site-font-heading); color: var(--site-primary);">
                    Prendre rendez-vous
                </h1>
                <p class="opacity-60" style="color: var(--site-text);">{{ $entreprise->nom }} &bull; {{ $entreprise->type_activite }}</p>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Calendrier --}}
        <div class="xl:col-span-2">
            <div class="rounded-2xl shadow-xl border overflow-hidden" style="background: var(--site-background); border-color: color-mix(in srgb, var(--site-text) 15%, transparent);">
                {{-- Header du calendrier --}}
                <div class="px-6 py-4" style="background: var(--site-primary);">
                    <div class="flex items-center justify-between">
                        <button type="button" id="prev-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="text-center">
                            <h2 class="text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                            <p class="text-sm text-white/80" id="calendar-subtitle"></p>
                        </div>
                        <button type="button" id="next-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Légende --}}
                <div class="px-6 py-3 border-b flex flex-wrap gap-4 text-sm" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background: color-mix(in srgb, var(--site-background) 95%, var(--site-text) 5%);">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full" style="background: var(--site-primary);"></span><span class="opacity-60">Disponible</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-400"></span><span class="opacity-60">Indisponible</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500"></span><span class="opacity-60">Sélectionné</span></div>
                </div>

                {{-- Grille --}}
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers"></div>
                    <div class="grid grid-cols-7 gap-2" id="calendar-grid"></div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t flex justify-center" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                    <button type="button" id="today-btn" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors hover:opacity-80" style="color: var(--site-primary);">
                        Aujourd'hui
                    </button>
                </div>
            </div>
        </div>

        {{-- Formulaire de réservation --}}
        <div class="xl:col-span-1" id="reservation-form-container">
            @include('components.site-web.partials.reservation-form', [
                'entreprise' => $entreprise,
                'slug' => $slug,
                'horaires' => $horaires,
                'membres' => $membres,
                'aGestionMultiPersonnes' => $aGestionMultiPersonnes,
                'userInfo' => $userInfo,
            ])
        </div>
    </div>
</div>

{{-- Script calendrier --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const horaires = @json($horaires);
    const reservationsUrl = '{{ $reservationsUrl }}';
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--site-primary').trim();

    let currentWeekOffset = 0;
    let selectedSlot = null;
    let reservations = [];

    const calendarHeaders = document.getElementById('calendar-headers');
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.getElementById('calendar-title');
    const calendarSubtitle = document.getElementById('calendar-subtitle');

    const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    const joursComplets = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    const horairesParJour = {};
    horaires.forEach(h => {
        if (!h.est_exceptionnel) {
            if (!horairesParJour[h.jour_semaine]) horairesParJour[h.jour_semaine] = [];
            horairesParJour[h.jour_semaine].push({ ouverture: h.heure_ouverture, fermeture: h.heure_fermeture });
        }
    });

    async function loadReservations() {
        try {
            const r = await fetch(reservationsUrl);
            reservations = await r.json();
        } catch (e) { reservations = []; }
    }

    function isSlotReserved(dateStr, time) {
        const s = new Date(dateStr + 'T' + time + ':00');
        const e = new Date(s.getTime() + 30 * 60 * 1000);
        return reservations.some(r => new Date(r.start) < e && new Date(r.end) > s);
    }

    function isTimeInPlages(timeStr, plages) {
        if (!plages || !plages.length) return false;
        const [h, m] = timeStr.split(':').map(Number);
        const t = h * 60 + m;
        return plages.some(p => {
            if (!p.ouverture || !p.fermeture) return false;
            const [sh, sm] = p.ouverture.split(':').map(Number);
            const [eh, em] = p.fermeture.split(':').map(Number);
            return t >= sh * 60 + sm && (t + 30) <= eh * 60 + em;
        });
    }

    function formatDateISO(d) {
        d = new Date(d);
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }

    function generateSlots(date, jour) {
        const plages = horairesParJour[jour] || [];
        const slots = [];
        const hasValid = plages.length > 0 && plages.some(p => p.ouverture && p.fermeture);
        let minH = 8, maxH = 20;
        if (hasValid) {
            plages.forEach(p => {
                if (p.ouverture && p.fermeture) {
                    minH = Math.min(minH, parseInt(p.ouverture));
                    maxH = Math.max(maxH, parseInt(p.fermeture));
                }
            });
        }
        for (let h = minH; h <= maxH; h++) {
            for (let m = 0; m < 60; m += 30) {
                if (h === maxH && m > 0) break;
                const ts = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
                const ds = formatDateISO(date);
                const isIn = isTimeInPlages(ts, plages);
                const isPast = new Date(ds + 'T' + ts + ':00') <= new Date(Date.now() + 3600000);
                const isRes = isSlotReserved(ds, ts);
                slots.push({ time: ts, available: isIn && !isPast && !isRes, isInPlage: isIn });
            }
        }
        return slots;
    }

    async function renderCalendar() {
        await loadReservations();
        const today = new Date();
        const start = new Date(today);
        start.setDate(today.getDate() - today.getDay() + 1 + currentWeekOffset * 7);
        const end = new Date(start); end.setDate(start.getDate() + 6);

        calendarTitle.textContent = start.getMonth() === end.getMonth()
            ? `${start.getDate()} - ${end.getDate()} ${mois[start.getMonth()]} ${start.getFullYear()}`
            : `${start.getDate()} ${mois[start.getMonth()]} - ${end.getDate()} ${mois[end.getMonth()]}`;
        calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : (currentWeekOffset > 0 ? `Dans ${currentWeekOffset} semaine(s)` : '');

        calendarHeaders.innerHTML = '';
        calendarGrid.innerHTML = '';

        for (let i = 0; i < 7; i++) {
            const d = new Date(start); d.setDate(start.getDate() + i);
            const isToday = formatDateISO(d) === formatDateISO(today);
            const hdr = document.createElement('div');
            hdr.className = `text-center p-2 rounded-xl ${isToday ? 'ring-2' : ''}`;
            if (isToday) hdr.style.cssText = `--tw-ring-color: ${primaryColor}; background: color-mix(in srgb, ${primaryColor} 10%, transparent);`;
            hdr.innerHTML = `<div class="text-xs font-medium uppercase opacity-50">${joursSemaine[d.getDay()]}</div><div class="text-lg font-bold" ${isToday ? `style="color:${primaryColor}"` : ''}>${d.getDate()}</div>`;
            calendarHeaders.appendChild(hdr);

            const col = document.createElement('div');
            col.className = 'space-y-1';
            const slots = generateSlots(d, d.getDay());
            const ds = formatDateISO(d);

            slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = slot.time;
                const isSel = selectedSlot && selectedSlot.date === ds && selectedSlot.time === slot.time;
                btn.className = 'w-full px-2 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 ';
                if (isSel) {
                    btn.className += 'bg-amber-500 text-white shadow-md transform scale-105';
                } else if (slot.available) {
                    btn.className += 'hover:scale-105 cursor-pointer';
                    btn.style.cssText = `background: color-mix(in srgb, ${primaryColor} 15%, transparent); color: ${primaryColor};`;
                } else {
                    btn.className += 'opacity-40 cursor-not-allowed';
                    btn.style.cssText = 'background: color-mix(in srgb, var(--site-text) 5%, transparent); color: var(--site-text);';
                }
                if (slot.available) btn.addEventListener('click', () => selectSlot(ds, slot.time));
                col.appendChild(btn);
            });
            calendarGrid.appendChild(col);
        }
    }

    function selectSlot(date, time) {
        selectedSlot = { date, time };
        // Mettre à jour les champs cachés dans le formulaire
        document.querySelectorAll('[name="date_reservation"]').forEach(el => el.value = date);
        document.querySelectorAll('[name="heure_reservation"]').forEach(el => el.value = time);
        renderCalendar();
    }

    document.getElementById('prev-week')?.addEventListener('click', () => { if (currentWeekOffset > 0) { currentWeekOffset--; renderCalendar(); } });
    document.getElementById('next-week')?.addEventListener('click', () => { if (currentWeekOffset < 8) { currentWeekOffset++; renderCalendar(); } });
    document.getElementById('today-btn')?.addEventListener('click', () => { currentWeekOffset = 0; renderCalendar(); });

    // Écouter l'auth popup
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'auth_success') {
            // Recharger le formulaire de réservation en AJAX
            fetch('{{ $reservationFormUrl }}', { credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('reservation-form-container').innerHTML = html;
                });
        }
    });

    renderCalendar();
});
</script>
