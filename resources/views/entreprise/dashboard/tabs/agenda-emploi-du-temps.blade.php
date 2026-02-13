{{-- Sous-onglet Emploi du temps -- Calendrier visuel style Google Agenda --}}
<div>
    {{-- Conteneur du calendrier --}}
    <div id="emploi-du-temps-calendar"></div>

    {{-- Modal : Détails d'une réservation Allotata --}}
    <div id="edt-modal-reservation" class="hidden fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
        <div class="modal-content rounded-2xl shadow-2xl p-6 max-w-md w-full">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Détails de la réservation</h3>
                <button onclick="document.getElementById('edt-modal-reservation').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="edt-reservation-details" class="space-y-4"></div>
        </div>
    </div>

    {{-- Modal 1 : Bloquer ce créneau --}}
    <div id="edt-modal-bloquer" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-lg w-full">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </span>
                    Bloquer ce créneau
                </h3>
                <button onclick="closeEdtBlockModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Infos événement --}}
            <div id="edt-block-info" class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                <p class="text-sm font-semibold text-slate-900 dark:text-white" id="edt-block-title"></p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1" id="edt-block-datetime"></p>
            </div>

            {{-- Option de fermeture --}}
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Fermeture exceptionnelle de <strong id="edt-block-heure-debut"></strong> à <strong id="edt-block-heure-fin"></strong>
                </p>
            </div>

            <input type="hidden" id="edt-block-date-value">
            <input type="hidden" id="edt-block-heure-debut-value">
            <input type="hidden" id="edt-block-heure-fin-value">
            <input type="hidden" id="edt-block-event-data">

            <div class="flex gap-3">
                <button type="button" onclick="closeEdtBlockModal()" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                    Annuler
                </button>
                <button type="button" onclick="confirmEdtBlock()" id="edt-block-confirm-btn" class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                    Confirmer le blocage
                </button>
            </div>
        </div>
    </div>

    {{-- Modal 2 : Propagation aux autres entreprises --}}
    <div id="edt-modal-propagation" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-lg w-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Bloquer aussi sur vos autres entreprises ?</h3>
                <button onclick="closeEdtPropagationModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-400 mb-5">
                Ce créneau a été bloqué sur <strong class="text-slate-900 dark:text-white">{{ $entreprise->nom }}</strong>. 
                Vous pouvez également bloquer le même créneau sur vos autres entreprises.
            </p>

            <div id="edt-propagation-list" class="space-y-2 mb-6 max-h-64 overflow-y-auto">
                {{-- Rempli dynamiquement par JS --}}
            </div>

            <button type="button" onclick="closeEdtPropagationModal()" class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                Fermer
            </button>
        </div>
    </div>
</div>

@php
    $autresEntreprisesData = Auth::user()->entreprises()
        ->where('id', '!=', $entreprise->id)
        ->select('id', 'nom', 'slug', 'logo')
        ->get();
@endphp
<script>
(function() {
    const entrepriseSlug = '{{ $entreprise->slug }}';
    const eventsEndpoint = '{{ route("emploi-du-temps.events", $entreprise->slug) }}';
    const jourExceptionnelUrl = '{{ route("agenda.jour-exceptionnel.store", $entreprise->slug) }}';
    const csrfToken = '{{ csrf_token() }}';
    const googleConnected = {{ $entreprise->aGoogleCalendar() ? 'true' : 'false' }};
    const googleConnectUrl = '{{ route("google-calendar.redirect", $entreprise->slug) }}';
    const googleSyncUrl = '{{ route("google-calendar.sync", $entreprise->slug) }}';

    // Autres entreprises de l'utilisateur pour la propagation
    const autresEntreprises = @json($autresEntreprisesData);

    let calendarInstance = null;

    function initEmploiDuTemps() {
        if (calendarInstance) return;
        if (typeof EmploiDuTemps === 'undefined') {
            // Le script n'est pas encore chargé, réessayer
            setTimeout(initEmploiDuTemps, 200);
            return;
        }

        calendarInstance = new EmploiDuTemps('emploi-du-temps-calendar', {
            endpoint: eventsEndpoint,
            view: 'week',
            googleConnected: googleConnected,
            showGoogleBanner: !googleConnected,
            googleConnectUrl: googleConnectUrl,
            syncUrl: googleConnected ? googleSyncUrl : '',
            csrfToken: csrfToken,
            onEventClick: function(event) {
                showEdtReservationDetails(event);
            },
            onBlockClick: function(event) {
                openEdtBlockModal(event);
            }
        });
    }

    // === Modal détails réservation ===
    function showEdtReservationDetails(event) {
        const meta = event.meta || {};
        const startTime = new Date(event.start);
        const endTime = event.end ? new Date(event.end) : null;

        const statutColors = {
            'en_attente': 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300',
            'confirmee': 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
            'terminee': 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
            'annulee': 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
        };

        const statutLabels = {
            'en_attente': 'En attente',
            'confirmee': 'Confirmée',
            'terminee': 'Terminée',
            'annulee': 'Annulée'
        };

        const colorClass = statutColors[event.status] || 'bg-slate-100 dark:bg-slate-700';
        const statutLabel = statutLabels[event.status] || event.status;
        const hash = meta.hash;
        const reservationUrl = hash ? `/r/${hash}` : `/m/${entrepriseSlug}/reservations/${event.id}`;

        document.getElementById('edt-reservation-details').innerHTML = `
            <div class="p-3 rounded-xl ${colorClass}">
                <span class="text-sm font-bold">${statutLabel}</span>
            </div>
            <div class="space-y-3">
                <div>
                    <span class="text-xs text-slate-500 uppercase">Service</span>
                    <p class="font-semibold text-slate-900 dark:text-white">${event.service_name || event.title}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Date</span>
                        <p class="font-semibold text-slate-900 dark:text-white">${startTime.toLocaleDateString('fr-FR')}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Heure</span>
                        <p class="font-semibold text-slate-900 dark:text-white">${startTime.toTimeString().substring(0, 5)}${endTime ? ' - ' + endTime.toTimeString().substring(0, 5) : ''}</p>
                    </div>
                </div>
                ${event.client_name ? `<div><span class="text-xs text-slate-500 uppercase">Client</span><p class="font-semibold text-slate-900 dark:text-white">${event.client_name}</p></div>` : ''}
                ${meta.prix ? `<div><span class="text-xs text-slate-500 uppercase">Prix</span><p class="font-semibold text-green-600">${meta.prix} €</p></div>` : ''}
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                    <a href="${reservationUrl}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Voir la réservation
                    </a>
                </div>
            </div>
        `;
        document.getElementById('edt-modal-reservation').classList.remove('hidden');
    }

    // === Modal bloquer créneau ===
    function openEdtBlockModal(event) {
        const startTime = new Date(event.start);
        const endTime = event.end ? new Date(event.end) : new Date(startTime.getTime() + 3600000);

        const dateStr = startTime.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const heureDebut = startTime.toTimeString().substring(0, 5);
        const heureFin = endTime.toTimeString().substring(0, 5);

        document.getElementById('edt-block-title').textContent = event.title || 'Événement';
        document.getElementById('edt-block-datetime').textContent = `${dateStr}`;
        document.getElementById('edt-block-heure-debut').textContent = heureDebut;
        document.getElementById('edt-block-heure-fin').textContent = heureFin;

        // Stocker les valeurs
        document.getElementById('edt-block-date-value').value = startTime.getFullYear() + '-' + String(startTime.getMonth() + 1).padStart(2, '0') + '-' + String(startTime.getDate()).padStart(2, '0');
        document.getElementById('edt-block-heure-debut-value').value = heureDebut;
        document.getElementById('edt-block-heure-fin-value').value = heureFin;
        document.getElementById('edt-block-event-data').value = JSON.stringify(event);

        // Reset bouton
        const confirmBtn = document.getElementById('edt-block-confirm-btn');
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmer le blocage';

        document.getElementById('edt-modal-bloquer').classList.remove('hidden');
    }

    async function confirmEdtBlock() {
        const confirmBtn = document.getElementById('edt-block-confirm-btn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Blocage en cours...';

        const date = document.getElementById('edt-block-date-value').value;
        const heureDebut = document.getElementById('edt-block-heure-debut-value').value;
        const heureFin = document.getElementById('edt-block-heure-fin-value').value;

        try {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('type_exception', 'jour');
            formData.append('date_exception', date);
            formData.append('est_ferme', '0');
            formData.append('heure_ouverture', heureDebut);
            formData.append('heure_fermeture', heureFin);

            const response = await fetch(jourExceptionnelUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.ok || response.status === 302) {
                closeEdtBlockModal();

                // Rafraîchir le calendrier
                if (calendarInstance) {
                    calendarInstance.fetchEvents();
                }

                // Si l'utilisateur a d'autres entreprises, proposer la propagation
                if (autresEntreprises.length > 0) {
                    openEdtPropagationModal(date, heureDebut, heureFin);
                }
            } else {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Erreur — Réessayer';
            }
        } catch (e) {
            console.error('Erreur blocage:', e);
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Erreur — Réessayer';
        }
    }

    function closeEdtBlockModal() {
        document.getElementById('edt-modal-bloquer').classList.add('hidden');
    }

    // === Modal propagation ===
    function openEdtPropagationModal(date, heureDebut, heureFin) {
        const list = document.getElementById('edt-propagation-list');
        list.innerHTML = '';

        autresEntreprises.forEach(ent => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl';

            const logoUrl = ent.logo ? `/storage/${ent.logo}` : null;
            const logoHtml = logoUrl
                ? `<img src="${logoUrl}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">`
                : `<div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0"><span class="text-white font-bold text-sm">${(ent.nom || '?')[0].toUpperCase()}</span></div>`;

            row.innerHTML = `
                ${logoHtml}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">${ent.nom}</p>
                </div>
                <button type="button" class="edt-propagation-btn flex-shrink-0 px-4 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-all" data-slug="${ent.slug}" data-date="${date}" data-heure-debut="${heureDebut}" data-heure-fin="${heureFin}">
                    Bloquer
                </button>
            `;

            // Event listener pour le bouton
            const btn = row.querySelector('.edt-propagation-btn');
            btn.addEventListener('click', async function() {
                await propagateBlock(this, ent.slug, date, heureDebut, heureFin);
            });

            list.appendChild(row);
        });

        document.getElementById('edt-modal-propagation').classList.remove('hidden');
    }

    async function propagateBlock(btn, slug, date, heureDebut, heureFin) {
        btn.disabled = true;
        btn.textContent = '...';

        try {
            const url = `/m/${slug}/agenda/jour-exceptionnel`;
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('type_exception', 'jour');
            formData.append('date_exception', date);
            formData.append('est_ferme', '0');
            formData.append('heure_ouverture', heureDebut);
            formData.append('heure_fermeture', heureFin);

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.ok || response.status === 302) {
                btn.textContent = 'Bloqué';
                btn.className = 'edt-propagation-btn flex-shrink-0 px-4 py-2 text-sm font-semibold text-slate-500 dark:text-slate-400 bg-slate-200 dark:bg-slate-600 rounded-lg cursor-default';
                btn.disabled = true;
            } else {
                btn.textContent = 'Erreur';
                btn.disabled = false;
            }
        } catch (e) {
            btn.textContent = 'Erreur';
            btn.disabled = false;
        }
    }

    function closeEdtPropagationModal() {
        document.getElementById('edt-modal-propagation').classList.add('hidden');
    }

    // Fermer modals en cliquant dehors
    ['edt-modal-reservation', 'edt-modal-bloquer', 'edt-modal-propagation'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    });

    // Exposer pour le système de sous-onglets
    window.initEmploiDuTemps = initEmploiDuTemps;
    window.closeEdtBlockModal = closeEdtBlockModal;
    window.closeEdtPropagationModal = closeEdtPropagationModal;
    window.confirmEdtBlock = confirmEdtBlock;
})();
</script>
