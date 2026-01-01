
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Calendrier Tailwind -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <button type="button" id="prev-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="text-center">
                            <h2 class="text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                            <p class="text-sm text-white/80" id="calendar-subtitle"></p>
                        </div>
                        <button type="button" id="next-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Légende -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">En attente</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Confirmée</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Terminée</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Annulée</span>
                    </div>
                </div>

                <!-- Corps -->
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers"></div>
                    <div class="grid grid-cols-7 gap-2" id="calendar-grid"></div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <button type="button" id="today-btn" class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                        Aujourd'hui
                    </button>
                    <a href="{{ route('agenda.index', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                        Gérer l'agenda →
                    </a>
                </div>
            </div>

            <!-- Section Horaires d'ouverture -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </span>
                    Horaires d'ouverture
                </h2>
                
                <form action="{{ route('agenda.horaires.store', $entreprise->slug) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        @php
                            $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                        @endphp
                        @for($i = 0; $i < 7; $i++)
                            @php
                                $horaire = $horaires->firstWhere('jour_semaine', $i);
                                $isFerme = !$horaire || ($horaire->heure_ouverture === null);
                            @endphp
                            <div class="flex flex-wrap items-center gap-4 p-4 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <div class="w-28">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="horaires[{{ $i }}][ferme]" 
                                        value="1"
                                        class="horaire-ferme-checkbox w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500"
                                        data-index="{{ $i }}"
                                        {{ $isFerme ? 'checked' : '' }}
                                    >
                                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">Fermé</span>
                                </label>
                                <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                                <div class="flex-1 grid grid-cols-2 gap-4 horaire-inputs" data-index="{{ $i }}" style="{{ $isFerme ? 'opacity: 0.5' : '' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-slate-500 dark:text-slate-400">De</span>
                                        <input 
                                            type="time" 
                                            name="horaires[{{ $i }}][heure_ouverture]" 
                                            value="{{ $horaire && $horaire->heure_ouverture ? \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') : '' }}"
                                            class="flex-1 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            {{ $isFerme ? 'disabled' : '' }}
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-slate-500 dark:text-slate-400">à</span>
                                        <input 
                                            type="time" 
                                            name="horaires[{{ $i }}][heure_fermeture]" 
                                            value="{{ $horaire && $horaire->heure_fermeture ? \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') : '' }}"
                                            class="flex-1 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            {{ $isFerme ? 'disabled' : '' }}
                                        >
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                            Enregistrer les horaires
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modal détails réservation -->
            <div id="modal-reservation" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-md w-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Détails de la réservation</h3>
                        <button onclick="document.getElementById('modal-reservation').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="reservation-details" class="space-y-4"></div>
                </div>
            </div>

<script>
    // Données PHP
    const horaires = @json($horaires);
    const reservationsUrl = '{{ route("agenda.reservations", $entreprise->slug) }}';
    
    // État
    let currentWeekOffset = 0;
    let reservations = [];
    
    // Éléments
    const calendarHeaders = document.getElementById('calendar-headers');
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.getElementById('calendar-title');
    const calendarSubtitle = document.getElementById('calendar-subtitle');
    
    // Noms
    const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    
    // Horaires par jour
    const horairesParJour = {};
    horaires.forEach(h => {
        if (!h.est_exceptionnel) {
            horairesParJour[h.jour_semaine] = { ouverture: h.heure_ouverture, fermeture: h.heure_fermeture };
        }
    });
    
    // Couleurs
    const statutColors = {
        'en_attente': 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        'confirmee': 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-300 dark:border-green-700',
        'terminee': 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
        'annulee': 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-300 dark:border-red-700'
    };
    
    const statutLabels = {
        'en_attente': '⏳ En attente',
        'confirmee': '✓ Confirmée',
        'terminee': '✓ Terminée',
        'annulee': '✗ Annulée'
    };
    
    async function loadReservations() {
        try {
            const response = await fetch(reservationsUrl);
            reservations = await response.json();
        } catch (error) {
            reservations = [];
        }
    }
    
    function formatDateISO(date) {
        const d = new Date(date);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    
    function getReservationsForDay(dateStr) {
        return reservations.filter(r => formatDateISO(new Date(r.start)) === dateStr).sort((a, b) => new Date(a.start) - new Date(b.start));
    }
    
    async function renderCalendar() {
        await loadReservations();
        
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (currentWeekOffset * 7));
        
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        
        if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
            calendarTitle.textContent = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${mois[startOfWeek.getMonth()]}`;
        } else {
            calendarTitle.textContent = `${startOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} - ${endOfWeek.getDate()} ${mois[endOfWeek.getMonth()]}`;
        }
        calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : '';
        
        calendarHeaders.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            const isToday = formatDateISO(date) === formatDateISO(today);
            
            const header = document.createElement('div');
            header.className = `text-center p-2 rounded-xl ${isToday ? 'bg-green-100 dark:bg-green-900/30' : ''}`;
            header.innerHTML = `
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">${joursSemaine[(date.getDay() + 7) % 7]}</div>
                <div class="text-lg font-bold ${isToday ? 'text-green-600 dark:text-green-400' : 'text-slate-900 dark:text-white'}">${date.getDate()}</div>
            `;
            calendarHeaders.appendChild(header);
        }
        
        calendarGrid.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            const dateStr = formatDateISO(date);
            const jourSemaine = date.getDay();
            const horaire = horairesParJour[jourSemaine];
            const dayReservations = getReservationsForDay(dateStr);
            
            const dayColumn = document.createElement('div');
            dayColumn.className = 'space-y-1 min-h-[120px]';
            
            if (!horaire || !horaire.ouverture) {
                dayColumn.innerHTML = `<div class="h-full min-h-[120px] flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700/50 border-2 border-dashed border-slate-200 dark:border-slate-600"><span class="text-xs text-slate-400 font-medium">Fermé</span></div>`;
            } else if (dayReservations.length === 0) {
                dayColumn.innerHTML = `<div class="h-full min-h-[120px] flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-200 dark:border-green-800"><span class="text-xs text-green-500 font-medium">Libre</span></div>`;
            } else {
                dayReservations.forEach(res => {
                    const time = new Date(res.start).toTimeString().substring(0, 5);
                    const colorClass = statutColors[res.extendedProps?.statut] || statutColors['en_attente'];
                    
                    const resEl = document.createElement('button');
                    resEl.type = 'button';
                    resEl.className = `w-full p-2 text-left rounded-lg border-l-4 ${colorClass} hover:shadow-md transition-all cursor-pointer`;
                    resEl.innerHTML = `<div class="text-xs font-bold">${time}</div><div class="text-xs truncate">${res.title}</div>`;
                    resEl.onclick = () => showReservationDetails(res);
                    dayColumn.appendChild(resEl);
                });
            }
            
            calendarGrid.appendChild(dayColumn);
        }
    }
    
    function showReservationDetails(res) {
        const props = res.extendedProps || {};
        const startTime = new Date(res.start);
        
        document.getElementById('reservation-details').innerHTML = `
            <div class="p-4 rounded-xl ${statutColors[props.statut] || 'bg-slate-100 dark:bg-slate-700'}">
                <span class="text-sm font-bold">${statutLabels[props.statut] || props.statut}</span>
            </div>
            <div class="space-y-3">
                <div><span class="text-xs text-slate-500 uppercase">Service</span><p class="font-semibold text-slate-900 dark:text-white">${props.type_service || res.title}</p></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="text-xs text-slate-500 uppercase">Date</span><p class="font-semibold text-slate-900 dark:text-white">${startTime.toLocaleDateString('fr-FR')}</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Heure</span><p class="font-semibold text-slate-900 dark:text-white">${startTime.toTimeString().substring(0, 5)}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="text-xs text-slate-500 uppercase">Durée</span><p class="font-semibold text-slate-900 dark:text-white">${props.duree || '-'} min</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Prix</span><p class="font-semibold text-green-600">${props.prix || '-'} €</p></div>
                </div>
                <div><span class="text-xs text-slate-500 uppercase">Client</span><p class="font-semibold text-slate-900 dark:text-white">${props.client || '-'}</p><p class="text-sm text-slate-600">${props.client_email || ''}</p>${props.telephone ? `<p class="text-sm text-slate-600">📞 ${props.telephone}</p>` : ''}</div>
                <div><span class="text-xs text-slate-500 uppercase">Payé</span><p class="font-semibold ${props.est_paye ? 'text-green-600' : 'text-red-600'}">${props.est_paye ? '✓ Oui' : '✗ Non'}</p></div>
                ${props.lieu ? `<div><span class="text-xs text-slate-500 uppercase">Lieu</span><p class="text-slate-900 dark:text-white">${props.lieu}</p></div>` : ''}
                ${props.notes ? `<div><span class="text-xs text-slate-500 uppercase">Notes</span><p class="text-slate-600">${props.notes}</p></div>` : ''}
            </div>
        `;
        
        document.getElementById('modal-reservation').classList.remove('hidden');
    }
    
    document.getElementById('prev-week')?.addEventListener('click', () => { currentWeekOffset--; renderCalendar(); });
    document.getElementById('next-week')?.addEventListener('click', () => { currentWeekOffset++; renderCalendar(); });
    document.getElementById('today-btn')?.addEventListener('click', () => { currentWeekOffset = 0; renderCalendar(); });
    
    document.querySelectorAll('.horaire-ferme-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const inputs = document.querySelector(`.horaire-inputs[data-index="${this.dataset.index}"]`);
            const timeInputs = inputs.querySelectorAll('input[type="time"]');
            
            if (this.checked) {
                // Si on coche "Fermé", désactiver et vider les champs
                inputs.style.opacity = '0.5';
                timeInputs.forEach(input => { 
                    input.disabled = true; 
                    input.value = ''; 
                });
            } else {
                // Si on décoche "Fermé", activer et remplir avec les horaires par défaut (8h00 - 19h00)
                inputs.style.opacity = '1';
                timeInputs.forEach((input, index) => {
                    input.disabled = false;
                    // Premier input = heure d'ouverture (8h00), deuxième = heure de fermeture (19h00)
                    if (index === 0) {
                        input.value = '08:00';
                    } else if (index === 1) {
                        input.value = '19:00';
                    }
                });
            }
        });
    });
    
    document.getElementById('modal-reservation')?.addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
    
    renderCalendar();
</script>
