<div id="modal-modify-proposition" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-6xl w-full p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Modifier la proposition</h3>
            <button onclick="closeModifyPropositionModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne gauche : Formulaire -->
            <div class="lg:col-span-2">
                <form id="form-modify-proposition" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="reservation_id" id="modify-reservation-id" value="{{ $reservation->id }}">

            <!-- Service (si gérant) -->
            @if($isGerant)
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Service</label>
                    <select 
                        name="type_service_id" 
                        id="modify-type-service"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        @foreach($prestations as $prestation)
                            <option value="{{ $prestation->id }}" data-prix="{{ $prestation->prix }}" data-duree="{{ $prestation->duree_minutes }}" {{ $reservation->type_service_id == $prestation->id ? 'selected' : '' }}>
                                {{ $prestation->nom }} • {{ number_format($prestation->prix, 0, ',', ' ') }}€ • {{ $prestation->duree_minutes }}min
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Date et heure -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                    <input 
                        type="date" 
                        name="date_rdv" 
                        id="modify-date-rdv"
                        required
                        min="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Heure *</label>
                    <input 
                        type="time" 
                        name="heure_debut" 
                        id="modify-heure-debut"
                        required
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <!-- Durée et Prix -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Durée (minutes) *</label>
                    <input 
                        type="number" 
                        name="duree_minutes" 
                        id="modify-duree"
                        required
                        min="15"
                        step="15"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                    <input 
                        type="number" 
                        name="prix" 
                        id="modify-prix"
                        required
                        min="0"
                        step="0.01"
                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <!-- Lieu -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Lieu (optionnel)</label>
                <input 
                    type="text" 
                    name="lieu" 
                    id="modify-lieu"
                    placeholder="Adresse du rendez-vous"
                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                <textarea 
                    name="notes" 
                    id="modify-notes"
                    rows="3"
                    placeholder="Informations complémentaires..."
                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                ></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button 
                    type="button"
                    onclick="closeModifyPropositionModal()"
                    class="flex-1 px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition"
                >
                    Annuler
                </button>
                <button 
                    type="submit"
                    class="flex-1 px-6 py-3 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition"
                >
                    Envoyer la proposition
                </button>
            </div>
        </form>
            </div>

            <!-- Colonne droite : Agenda -->
            <div class="lg:col-span-1">
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 sticky top-4">
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Agenda du jour
                    </h4>
                    
                    <!-- Date sélectionnée -->
                    <div id="agenda-date-display" class="mb-4 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-600">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white" id="agenda-date-text">Sélectionnez une date</p>
                    </div>

                    <!-- Horaires d'ouverture -->
                    <div id="agenda-horaires" class="mb-4 hidden">
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Horaires d'ouverture</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white" id="agenda-horaires-text"></p>
                    </div>

                    <!-- Alerte de conflit -->
                    <div id="agenda-conflict-alert" class="hidden mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Conflit détecté !</p>
                                <p class="text-xs text-red-700 dark:text-red-400" id="agenda-conflict-details"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des réservations -->
                    <div id="agenda-reservations" class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Sélectionnez une date pour voir l'agenda</p>
                    </div>

                    <!-- Proposition en cours (prévisualisation) -->
                    <div id="agenda-proposition-preview" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hidden">
                        <p class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-1">Votre proposition</p>
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-200" id="agenda-proposition-time"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openModifyPropositionModal(reservationId, date, heure, duree, prix, lieu, notes, isGerant, typeServiceId) {
        // Pré-remplir tous les champs avec les données de la proposition (effet d'ancrage)
        document.getElementById('modify-reservation-id').value = reservationId;
        document.getElementById('modify-date-rdv').value = date;
        document.getElementById('modify-heure-debut').value = heure;
        document.getElementById('modify-duree').value = duree;
        document.getElementById('modify-prix').value = prix;
        document.getElementById('modify-lieu').value = lieu || '';
        document.getElementById('modify-notes').value = notes || '';
        
        // Définir le service si gérant (après avoir pré-rempli les autres champs)
        if (isGerant && typeServiceId && document.getElementById('modify-type-service')) {
            const serviceSelect = document.getElementById('modify-type-service');
            serviceSelect.value = typeServiceId;
            
            // Ne pas écraser les valeurs pré-remplies si elles sont déjà définies
            // On met à jour seulement si les champs sont vides ou si l'utilisateur change manuellement le service
            const currentPrix = document.getElementById('modify-prix').value;
            const currentDuree = document.getElementById('modify-duree').value;
            
            // Si les valeurs sont déjà pré-remplies, on ne les écrase pas
            if (!currentPrix || !currentDuree) {
                const option = serviceSelect.options[serviceSelect.selectedIndex];
                const prixService = option.dataset.prix;
                const dureeService = option.dataset.duree;
                if (prixService && !currentPrix) document.getElementById('modify-prix').value = prixService;
                if (dureeService && !currentDuree) document.getElementById('modify-duree').value = dureeService;
            }
        }
        
        // Définir l'action du formulaire
        const form = document.getElementById('form-modify-proposition');
        if (isGerant) {
            form.action = '{{ route("messagerie.modify-proposition-gerant", [$entreprise->slug, $conversation->id]) }}';
        } else {
            form.action = '{{ route("messagerie.modify-proposition-client", $entreprise->slug) }}';
        }
        
        document.getElementById('modal-modify-proposition').classList.remove('hidden');
        
        // Charger l'agenda si une date est déjà définie
        if (date) {
            setTimeout(() => loadAgenda(date), 100);
        }
    }

    function closeModifyPropositionModal() {
        document.getElementById('modal-modify-proposition').classList.add('hidden');
    }

    // Fermer en cliquant en dehors
    document.getElementById('modal-modify-proposition')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModifyPropositionModal();
        }
    });

    // Mettre à jour prix et durée quand le service change (gérant)
    @if($isGerant)
    document.getElementById('modify-type-service')?.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const prix = option.dataset.prix;
        const duree = option.dataset.duree;
        if (prix) document.getElementById('modify-prix').value = prix;
        if (duree) document.getElementById('modify-duree').value = duree;
        // Recharger l'agenda si une date est sélectionnée
        const date = document.getElementById('modify-date-rdv').value;
        if (date) {
            loadAgenda(date);
        }
    });
    @endif

    // Fonction pour charger l'agenda
    let currentReservationId = null;
    function loadAgenda(date) {
        if (!date) return;
        
        const reservationId = document.getElementById('modify-reservation-id')?.value;
        currentReservationId = reservationId;
        
        fetch(`{{ route('messagerie.agenda', $entreprise->slug) }}?date=${date}${reservationId ? '&reservation_id=' + reservationId : ''}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            // Afficher la date
            document.getElementById('agenda-date-text').textContent = `${data.jour_semaine} ${data.date_formatee}`;
            document.getElementById('agenda-date-display').classList.remove('hidden');
            
            // Afficher les horaires
            if (data.horaires.est_ferme) {
                document.getElementById('agenda-horaires-text').textContent = 'Fermé';
                document.getElementById('agenda-horaires-text').classList.add('text-red-600', 'dark:text-red-400');
            } else {
                document.getElementById('agenda-horaires-text').textContent = `${data.horaires.heure_ouverture} - ${data.horaires.heure_fermeture}`;
                document.getElementById('agenda-horaires-text').classList.remove('text-red-600', 'dark:text-red-400');
            }
            document.getElementById('agenda-horaires').classList.remove('hidden');
            
            // Afficher les réservations
            const container = document.getElementById('agenda-reservations');
            if (data.reservations.length === 0) {
                container.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Aucune réservation ce jour</p>';
            } else {
                container.innerHTML = data.reservations.map(r => `
                    <div class="p-2 rounded-lg border-l-4" style="border-left-color: ${r.color}; background: ${r.color}20;">
                        <p class="text-xs font-semibold text-slate-900 dark:text-white">${r.title}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">${r.start} - ${r.end}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded ${r.statut === 'confirmee' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'}">${r.statut === 'confirmee' ? 'Confirmée' : 'En attente'}</span>
                    </div>
                `).join('');
            }
            
            // Vérifier les conflits si une heure est sélectionnée
            const heure = document.getElementById('modify-heure-debut').value;
            const duree = document.getElementById('modify-duree').value;
            if (heure && duree) {
                checkConflict(date, heure, duree);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement de l\'agenda:', error);
        });
    }

    // Fonction pour vérifier les conflits
    function checkConflict(date, heure, duree) {
        const reservationId = document.getElementById('modify-reservation-id')?.value;
        
        fetch(`{{ route('messagerie.check-conflict', $entreprise->slug) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                date: date,
                heure_debut: heure,
                duree_minutes: duree,
                reservation_id: reservationId || null,
            })
        })
        .then(response => response.json())
        .then(data => {
            const alert = document.getElementById('agenda-conflict-alert');
            const preview = document.getElementById('agenda-proposition-preview');
            
            if (data.has_conflict) {
                alert.classList.remove('hidden');
                const details = data.conflits.map(c => `${c.title} (${c.start} - ${c.end})`).join(', ');
                document.getElementById('agenda-conflict-details').textContent = `Conflit avec : ${details}`;
                
                // Afficher la prévisualisation en rouge
                preview.classList.remove('hidden');
                preview.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-200', 'dark:border-blue-800');
                preview.classList.add('bg-red-50', 'dark:bg-red-900/20', 'border-red-200', 'dark:border-red-800');
                document.getElementById('agenda-proposition-time').textContent = `${heure} - ${calculateEndTime(heure, duree)}`;
            } else {
                alert.classList.add('hidden');
                
                // Afficher la prévisualisation en bleu (pas de conflit)
                preview.classList.remove('hidden');
                preview.classList.remove('bg-red-50', 'dark:bg-red-900/20', 'border-red-200', 'dark:border-red-800');
                preview.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-200', 'dark:border-blue-800');
                document.getElementById('agenda-proposition-time').textContent = `${heure} - ${calculateEndTime(heure, duree)}`;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification des conflits:', error);
        });
    }

    // Fonction pour calculer l'heure de fin
    function calculateEndTime(startTime, durationMinutes) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const start = new Date();
        start.setHours(hours, minutes, 0, 0);
        const end = new Date(start.getTime() + durationMinutes * 60000);
        return `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`;
    }

    // Écouter les changements de date, heure et durée
    document.getElementById('modify-date-rdv')?.addEventListener('change', function() {
        loadAgenda(this.value);
    });

    document.getElementById('modify-heure-debut')?.addEventListener('change', function() {
        const date = document.getElementById('modify-date-rdv').value;
        const duree = document.getElementById('modify-duree').value;
        if (date && duree) {
            checkConflict(date, this.value, duree);
        }
    });

    document.getElementById('modify-duree')?.addEventListener('change', function() {
        const date = document.getElementById('modify-date-rdv').value;
        const heure = document.getElementById('modify-heure-debut').value;
        if (date && heure) {
            checkConflict(date, heure, this.value);
        }
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgb(203, 213, 225);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgb(148, 163, 184);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgb(51, 65, 85);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgb(71, 85, 105);
    }
</style>
