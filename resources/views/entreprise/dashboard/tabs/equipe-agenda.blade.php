<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Agenda de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Visualisez les réservations et disponibilités de ce membre</p>
    </div>

    <!-- Calendrier Tailwind -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <!-- Header du calendrier -->
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

        <!-- Corps du calendrier -->
        <div class="p-4 sm:p-6">
            <!-- En-têtes des jours -->
            <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers"></div>

            <!-- Grille des réservations -->
            <div class="grid grid-cols-7 gap-2" id="calendar-grid"></div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <button type="button" id="today-btn" class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                Aujourd'hui
            </button>
            <div class="text-sm text-slate-500 dark:text-slate-400">
                <span id="reservations-count">0</span> réservation(s) cette semaine
            </div>
        </div>
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
            <div id="reservation-details" class="space-y-4">
                <!-- Rempli par JS -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Données PHP
        const horaires = @json($horaires);
        @php
            $membreId = ($membre->id == 0 || $membre->user_id == $entreprise->user_id) ? 'gerant' : $membre->id;
        @endphp
        const reservationsUrl = '{{ route("entreprise.equipe.agenda", [$entreprise->slug, $membreId]) }}';
        
        // État du calendrier
        let currentWeekOffset = 0;
        let reservations = [];
        
        // Éléments DOM
        const calendarHeaders = document.getElementById('calendar-headers');
        const calendarGrid = document.getElementById('calendar-grid');
        const calendarTitle = document.getElementById('calendar-title');
        const calendarSubtitle = document.getElementById('calendar-subtitle');
        const reservationsCount = document.getElementById('reservations-count');
        
        // Noms
        const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        
        // Horaires par jour
        const horairesParJour = {};
        horaires.forEach(h => {
            if (!h.est_exceptionnel) {
                horairesParJour[h.jour_semaine] = {
                    ouverture: h.heure_ouverture,
                    fermeture: h.heure_fermeture
                };
            }
        });
        
        // Couleurs par statut
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
        
        // Charger les réservations
        async function loadReservations() {
            try {
                const response = await fetch(reservationsUrl);
                reservations = await response.json();
            } catch (error) {
                console.error('Erreur:', error);
                reservations = [];
            }
        }
        
        // Formater une date en ISO
        function formatDateISO(date) {
            const d = new Date(date);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }
        
        // Obtenir les réservations d'un jour
        function getReservationsForDay(dateStr) {
            return reservations.filter(r => {
                const resDate = new Date(r.start);
                return formatDateISO(resDate) === dateStr;
            }).sort((a, b) => new Date(a.start) - new Date(b.start));
        }
        
        // Générer le calendrier
        async function renderCalendar() {
            await loadReservations();
            
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (currentWeekOffset * 7));
            
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            
            // Titre
            if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
                calendarTitle.textContent = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} ${startOfWeek.getFullYear()}`;
            } else {
                calendarTitle.textContent = `${startOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} - ${endOfWeek.getDate()} ${mois[endOfWeek.getMonth()]}`;
            }
            calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : '';
            
            // Compter les réservations de la semaine
            let weekReservations = 0;
            
            // En-têtes
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
            
            // Grille
            calendarGrid.innerHTML = '';
            for (let i = 0; i < 7; i++) {
                const date = new Date(startOfWeek);
                date.setDate(startOfWeek.getDate() + i);
                const dateStr = formatDateISO(date);
                const jourSemaine = date.getDay();
                const horaire = horairesParJour[jourSemaine];
                const dayReservations = getReservationsForDay(dateStr);
                
                weekReservations += dayReservations.length;
                
                const dayColumn = document.createElement('div');
                dayColumn.className = 'space-y-1 min-h-[150px]';
                
                if (!horaire || !horaire.ouverture) {
                    dayColumn.innerHTML = `
                        <div class="h-full min-h-[150px] flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700/50 border-2 border-dashed border-slate-200 dark:border-slate-600">
                            <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">Fermé</span>
                        </div>
                    `;
                } else if (dayReservations.length === 0) {
                    dayColumn.innerHTML = `
                        <div class="h-full min-h-[150px] flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-200 dark:border-green-800">
                            <span class="text-xs text-green-500 dark:text-green-400 font-medium">Libre</span>
                        </div>
                    `;
                } else {
                    dayReservations.forEach(res => {
                        const startTime = new Date(res.start);
                        const time = startTime.toTimeString().substring(0, 5);
                        const colorClass = statutColors[res.extendedProps?.statut] || statutColors['en_attente'];
                        
                        const resEl = document.createElement('button');
                        resEl.type = 'button';
                        resEl.className = `w-full p-2 text-left rounded-lg border-l-4 ${colorClass} hover:shadow-md transition-all cursor-pointer`;
                        resEl.innerHTML = `
                            <div class="text-xs font-bold">${time}</div>
                            <div class="text-xs truncate">${res.title}</div>
                        `;
                        resEl.onclick = () => showReservationDetails(res);
                        dayColumn.appendChild(resEl);
                    });
                }
                
                calendarGrid.appendChild(dayColumn);
            }
            
            reservationsCount.textContent = weekReservations;
        }
        
        // Afficher les détails d'une réservation
        function showReservationDetails(res) {
            const props = res.extendedProps || {};
            const startTime = new Date(res.start);
            const detailsEl = document.getElementById('reservation-details');
            
            detailsEl.innerHTML = `
                <div class="p-4 rounded-xl ${statutColors[props.statut] || 'bg-slate-100 dark:bg-slate-700'}">
                    <span class="text-sm font-bold">${statutLabels[props.statut] || props.statut}</span>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Service</span>
                        <p class="font-semibold text-slate-900 dark:text-white">${props.type_service || res.title}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Date</span>
                            <p class="font-semibold text-slate-900 dark:text-white">${startTime.toLocaleDateString('fr-FR')}</p>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Heure</span>
                            <p class="font-semibold text-slate-900 dark:text-white">${startTime.toTimeString().substring(0, 5)}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Durée</span>
                            <p class="font-semibold text-slate-900 dark:text-white">${props.duree || '-'} min</p>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Prix</span>
                            <p class="font-semibold text-green-600 dark:text-green-400">${props.prix || '-'} €</p>
                        </div>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Client</span>
                        <p class="font-semibold text-slate-900 dark:text-white">${props.client || '-'}</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">${props.client_email || ''}</p>
                        ${props.telephone ? `<p class="text-sm text-slate-600 dark:text-slate-400">📞 ${props.telephone}</p>` : ''}
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Payé</span>
                        <p class="font-semibold ${props.est_paye ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${props.est_paye ? '✓ Oui' : '✗ Non'}</p>
                    </div>
                    ${props.lieu ? `<div><span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Lieu</span><p class="text-slate-900 dark:text-white">${props.lieu}</p></div>` : ''}
                    ${props.notes ? `<div><span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Notes</span><p class="text-slate-600 dark:text-slate-400">${props.notes}</p></div>` : ''}
                </div>
            `;
            
            document.getElementById('modal-reservation').classList.remove('hidden');
        }
        
        // Navigation
        document.getElementById('prev-week')?.addEventListener('click', () => {
            currentWeekOffset--;
            renderCalendar();
        });
        
        document.getElementById('next-week')?.addEventListener('click', () => {
            currentWeekOffset++;
            renderCalendar();
        });
        
        document.getElementById('today-btn')?.addEventListener('click', () => {
            currentWeekOffset = 0;
            renderCalendar();
        });
        
        // Fermer le modal en cliquant dehors
        document.getElementById('modal-reservation')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
        
        // Initialiser
        renderCalendar();
    });
</script>
