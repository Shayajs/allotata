<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <style>
        /* Styles FullCalendar avec thème dark mode et Utility-First */
        .fc {
            font-family: inherit;
        }
        
        /* Header et toolbar */
        .fc-header-toolbar {
            @apply mb-4 flex flex-wrap items-center justify-between gap-4;
        }
        
        .fc-toolbar-title {
            @apply text-2xl font-bold text-slate-900 dark:text-white;
        }
        
        .fc-button-group {
            @apply flex gap-2;
        }
        
        .fc-button {
            @apply px-4 py-2 text-sm font-medium rounded-lg transition-all;
            @apply bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300;
            @apply border border-slate-300 dark:border-slate-600;
            @apply hover:bg-slate-200 dark:hover:bg-slate-600;
        }
        
        .fc-button-primary:not(:disabled):active,
        .fc-button-primary:not(:disabled).fc-button-active {
            @apply bg-gradient-to-r from-green-600 to-green-500 text-white border-green-600;
        }
        
        .fc-button-today {
            @apply bg-gradient-to-r from-green-600 to-green-500 text-white border-green-600;
            @apply hover:from-green-700 hover:to-green-600;
        }
        
        /* Tableau du calendrier */
        .fc-theme-standard td, .fc-theme-standard th {
            @apply border-slate-200 dark:border-slate-700;
        }
        
        .fc-col-header-cell {
            @apply bg-slate-50 dark:bg-slate-800;
        }
        
        .fc-col-header-cell-cushion {
            @apply text-slate-700 dark:text-slate-300 font-semibold py-2;
        }
        
        .fc-daygrid-day-number {
            @apply text-slate-900 dark:text-slate-100 font-medium;
        }
        
        .fc-timegrid-slot {
            @apply border-slate-200 dark:border-slate-700;
        }
        
        .fc-timegrid-slot-label {
            @apply text-slate-600 dark:text-slate-400 text-xs;
        }
        
        /* Événements */
        .fc-event {
            @apply cursor-move rounded-lg border-0 shadow-sm;
            @apply transition-all duration-200;
        }
        
        .fc-event:hover {
            @apply shadow-md scale-[1.02];
        }
        
        .fc-event-title {
            @apply font-medium px-2 py-1;
        }
        
        .fc-event-time {
            @apply font-semibold px-2 pt-1;
        }
        
        /* Jours désactivés */
        .fc-day-disabled {
            @apply bg-slate-100 dark:bg-slate-800 opacity-50;
        }
        
        .fc-daygrid-day.fc-day-disabled {
            @apply opacity-40;
        }
        
        /* Business hours */
        .fc-non-business {
            @apply bg-slate-50 dark:bg-slate-900/50;
        }
        
        /* Drag preview */
        .fc-event-dragging {
            @apply opacity-75 shadow-lg;
        }
        
        /* Scrollbar personnalisée */
        .fc-scroller::-webkit-scrollbar {
            @apply w-2;
        }
        
        .fc-scroller::-webkit-scrollbar-track {
            @apply bg-slate-100 dark:bg-slate-800;
        }
        
        .fc-scroller::-webkit-scrollbar-thumb {
            @apply bg-slate-300 dark:bg-slate-600 rounded-full;
        }
        
        .fc-scroller::-webkit-scrollbar-thumb:hover {
            @apply bg-slate-400 dark:bg-slate-500;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <header class="mb-8 pb-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if($entreprise->logo)
                        <img 
                            src="{{ asset('storage/' . $entreprise->logo) }}" 
                            alt="Logo {{ $entreprise->nom }}"
                            class="w-14 h-14 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 shadow-sm"
                        >
                    @endif
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                                {{ $entreprise->nom }}
                            </h1>
                            @if(!$entreprise->est_verifiee)
                                <span class="px-2.5 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800">
                                    ⏳ En cours
                                </span>
                            @endif
                        </div>
                        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400">
                            Agenda et réservation
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('home') }}" class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition-colors rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                        🏠 Accueil
                    </a>
                    <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition-colors rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                        ← Retour
                    </a>
                </div>
            </div>
        </header>

        <!-- Messages de session -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-400 text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-800 dark:text-red-400 text-sm">{{ session('error') }}</p>
            </div>
        @endif

        @if(!$entreprise->est_verifiee)
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-300 text-sm">Cette entreprise est en cours de création</p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">
                            Vous pouvez tout de même prendre rendez-vous. L'entreprise sera vérifiée prochainement.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($isOwner) && $isOwner && !$entreprise->aAbonnementActif())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-red-800 dark:text-red-300 text-sm">⚠️ Votre entreprise n'est pas visible en ligne</p>
                        <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                            Vous consultez votre propre entreprise, mais elle n'est pas visible pour les autres utilisateurs car vous n'avez pas d'abonnement actif. 
                            <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="underline font-semibold">Souscrivez à un abonnement</a> pour rendre votre entreprise visible dans les recherches.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Calendrier interactif -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                    <!-- Contrôles de l'agenda -->
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Heure de début :
                            </label>
                            <select 
                                id="heure-debut-select"
                                class="px-3 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="06:00">06:00</option>
                                <option value="07:00">07:00</option>
                                <option value="08:00" selected>08:00</option>
                                <option value="09:00">09:00</option>
                                <option value="10:00">10:00</option>
                            </select>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            💡 Glissez-déposez les créneaux pour les repositionner
                        </div>
                    </div>
                    
                    <div id="calendar" class="calendar-container"></div>
                </div>
            </div>

            <!-- Formulaire de réservation -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 sticky top-6">
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-6">Réserver un créneau</h2>
                    
                    @auth
                        <form action="{{ route('public.reservation.store', $entreprise->slug) }}" method="POST" id="reservation-form">
                            @csrf
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Type de service *
                                    </label>
                                    <select 
                                        name="type_service_id" 
                                        id="type_service_id"
                                        required
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-all"
                                    >
                                        <option value="">Sélectionnez un service</option>
                                        @foreach($entreprise->typesServices as $service)
                                            <option value="{{ $service->id }}" data-duree="{{ $service->duree_minutes }}" data-prix="{{ $service->prix }}">
                                                {{ $service->nom }} - {{ number_format($service->prix, 2, ',', ' ') }} € ({{ $service->duree_minutes }} min)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type_service_id')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Date *
                                    </label>
                                    <input 
                                        type="date" 
                                        name="date_reservation" 
                                        id="date_reservation"
                                        required
                                        min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('date_reservation')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Heure *
                                    </label>
                                    <input 
                                        type="time" 
                                        name="heure_reservation" 
                                        id="heure_reservation"
                                        required
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('heure_reservation')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Cliquez sur un créneau ou glissez-déposez
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Numéro de téléphone *
                                    </label>
                                    <input 
                                        type="tel" 
                                        name="telephone_client" 
                                        id="telephone_client"
                                        required
                                        placeholder="06 12 34 56 78"
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('telephone_client')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="flex items-center gap-2">
                                        <input 
                                            type="checkbox" 
                                            name="telephone_cache" 
                                            value="1"
                                            class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                        >
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Masquer mon numéro</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Lieu (optionnel)
                                    </label>
                                    <input 
                                        type="text" 
                                        name="lieu" 
                                        id="lieu"
                                        placeholder="Adresse ou lieu du rendez-vous"
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('lieu')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Notes (optionnel)
                                    </label>
                                    <textarea 
                                        name="notes" 
                                        id="notes"
                                        rows="3"
                                        placeholder="Informations supplémentaires..."
                                        class="w-full px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                                    ></textarea>
                                    @error('notes')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button 
                                    type="submit" 
                                    class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all shadow-sm hover:shadow-md"
                                >
                                    Confirmer la réservation
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-8">
                            <p class="text-slate-600 dark:text-slate-400 mb-4 text-sm">Vous devez être connecté pour prendre un rendez-vous.</p>
                            <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all shadow-sm hover:shadow-md">
                                Se connecter
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/fr.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const reservationsUrl = '{{ route("public.agenda.reservations", $entreprise->slug) }}';
            const heureDebutSelect = document.getElementById('heure-debut-select');
            let calendar;
            
            // Récupérer les horaires d'ouverture
            const horaires = @json($horaires);
            const joursFermes = [];
            const joursOuverts = {};
            const joursExceptionnels = {};
            
            horaires.forEach(horaire => {
                if (horaire.est_exceptionnel && horaire.date_exception) {
                    joursExceptionnels[horaire.date_exception] = {
                        ouverture: horaire.heure_ouverture,
                        fermeture: horaire.heure_fermeture,
                        est_ferme: !horaire.heure_ouverture || !horaire.heure_fermeture
                    };
                } else if (!horaire.est_exceptionnel) {
                    if (!horaire.heure_ouverture || !horaire.heure_fermeture) {
                        joursFermes.push(horaire.jour_semaine);
                    } else {
                        joursOuverts[horaire.jour_semaine] = {
                            ouverture: horaire.heure_ouverture,
                            fermeture: horaire.heure_fermeture
                        };
                    }
                }
            });
            
            // Fonction pour créer le calendrier
            function createCalendar(slotMinTime) {
                if (calendar) {
                    calendar.destroy();
                }
                
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    locale: 'fr',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Aujourd\'hui',
                        month: 'Mois',
                        week: 'Semaine',
                        day: 'Jour'
                    },
                    firstDay: 1,
                    slotMinTime: slotMinTime,
                    slotMaxTime: '22:00:00',
                    slotDuration: '00:30:00',
                    allDaySlot: false,
                    height: 'auto',
                    events: reservationsUrl,
                    editable: true, // Activer le drag & drop
                    eventStartEditable: true, // Permettre de déplacer les événements
                    eventDurationEditable: false, // DÉSACTIVER le redimensionnement
                    eventResize: false, // Désactiver complètement le redimensionnement
                    eventConstraint: 'businessHours', // Limiter aux heures d'ouverture
                    eventClick: function(info) {
                        // Ne pas afficher de détails dans l'agenda public
                        // Juste indiquer que le créneau est indisponible
                        alert('Ce créneau est indisponible.');
                    },
                    dateClick: function(info) {
                        if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                            const date = new Date(info.dateStr);
                            const dateStr = date.toISOString().split('T')[0];
                            const timeStr = date.toTimeString().split(' ')[0].substring(0, 5);
                            
                            document.getElementById('date_reservation').value = dateStr;
                            document.getElementById('heure_reservation').value = timeStr;
                            
                            if (window.innerWidth < 1024) {
                                document.getElementById('reservation-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }
                    },
                    eventDrop: function(info) {
                        // Quand un événement est déplacé, mettre à jour le formulaire
                        const start = new Date(info.event.start);
                        const dateStr = start.toISOString().split('T')[0];
                        const timeStr = start.toTimeString().split(' ')[0].substring(0, 5);
                        
                        document.getElementById('date_reservation').value = dateStr;
                        document.getElementById('heure_reservation').value = timeStr;
                        
                        // Mettre à jour le type de service si nécessaire
                        const duree = info.event.extendedProps.duree;
                        const typeServiceSelect = document.getElementById('type_service_id');
                        if (typeServiceSelect) {
                            for (let option of typeServiceSelect.options) {
                                if (option.dataset.duree == duree) {
                                    typeServiceSelect.value = option.value;
                                    break;
                                }
                            }
                        }
                    },
                    dayCellClassNames: function(info) {
                        const dayOfWeek = info.date.getDay();
                        const dateStr = info.date.toISOString().split('T')[0];
                        
                        if (joursExceptionnels[dateStr]) {
                            if (joursExceptionnels[dateStr].est_ferme) {
                                return ['fc-day-disabled'];
                            }
                        } else if (joursFermes.includes(dayOfWeek)) {
                            return ['fc-day-disabled'];
                        }
                        return [];
                    },
                    businessHours: function(nowDate) {
                        const businessHours = [];
                        Object.keys(joursOuverts).forEach(day => {
                            const horaire = joursOuverts[day];
                            businessHours.push({
                                daysOfWeek: [parseInt(day)],
                                startTime: horaire.ouverture,
                                endTime: horaire.fermeture
                            });
                        });
                        return businessHours;
                    },
                    validRange: function(nowDate) {
                        return { start: nowDate };
                    },
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    }
                });
                
                calendar.render();
            }
            
            // Créer le calendrier avec l'heure par défaut
            createCalendar('08:00:00');
            
            // Changer l'heure de début quand on change le select
            heureDebutSelect.addEventListener('change', function() {
                createCalendar(this.value + ':00');
            });
            
            // Mettre à jour la durée quand on change le type de service
            const typeServiceSelect = document.getElementById('type_service_id');
            if (typeServiceSelect) {
                typeServiceSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const duree = selectedOption.dataset.duree;
                    
                    // Créer un événement temporaire pour visualiser la durée
                    if (duree && calendar) {
                        // On pourrait ajouter un événement de prévisualisation ici
                        // Pour l'instant, on met juste à jour l'heure de fin suggérée
                    }
                });
            }
        });
    </script>
</body>
</html>
