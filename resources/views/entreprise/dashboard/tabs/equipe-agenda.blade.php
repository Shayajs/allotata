<div>
    <div class="mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Agenda de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Visualisez les réservations et disponibilités de ce membre</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div id="equipe-calendar" class="p-6" style="min-height: 600px;"></div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('equipe-calendar');
        if (!calendarEl) return;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'fr',
            firstDay: 1,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            slotDuration: '00:30:00',
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch('{{ route("entreprise.equipe.agenda", [$entreprise->slug, $membre]) }}')
                    .then(response => response.json())
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(error => {
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                // Optionnel : afficher détails de la réservation
            },
        });

        calendar.render();
    });
</script>
