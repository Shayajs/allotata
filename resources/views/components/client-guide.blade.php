@if(!auth()->user()->hasSeenClientGuide())
<div id="client-guide" class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-green-900/20 dark:via-emerald-900/20 dark:to-teal-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-6 mb-6 shadow-lg relative">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                Bienvenue sur Allo Tata !
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                Voici comment profiter au maximum de la plateforme en tant que client.
            </p>
        </div>
        <button 
            onclick="dismissClientGuide()" 
            class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition p-1"
            title="Fermer le guide"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Étape 1 : Chercher -->
        <a href="{{ route('home') }}" class="group p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-400 dark:hover:border-green-500 transition-all hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Cherchez un professionnel</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">Trouvez le prestataire qu'il vous faut par nom, ville ou activité.</p>
        </a>

        <!-- Étape 2 : Réserver -->
        <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Prenez rendez-vous</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">Choisissez un créneau disponible directement dans l'agenda du professionnel.</p>
        </div>

        <!-- Étape 3 : Suivre -->
        <button onclick="if(typeof showTab === 'function') showTab('reservations')" class="text-left p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-orange-400 dark:hover:border-orange-500 transition-all hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Suivez vos réservations</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">Retrouvez toutes vos réservations passées et à venir dans l'onglet dédié.</p>
        </button>

        <!-- Étape 4 : Support -->
        <button onclick="if(typeof showTab === 'function') showTab('support')" class="text-left p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-purple-400 dark:hover:border-purple-500 transition-all hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Besoin d'aide ?</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">Notre équipe est là pour répondre à toutes vos questions via le support.</p>
        </button>
    </div>

    <div class="mt-4 text-center">
        <button 
            onclick="dismissClientGuide()" 
            class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition underline"
        >
            J'ai compris, ne plus afficher
        </button>
    </div>
</div>

<script>
    function dismissClientGuide() {
        const guide = document.getElementById('client-guide');
        if (guide) {
            guide.style.opacity = '0';
            guide.style.transform = 'translateY(-10px)';
            guide.style.transition = 'opacity 0.3s, transform 0.3s';
            setTimeout(() => guide.remove(), 300);
        }
        fetch('{{ route("dashboard.dismiss-guide") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            }
        }).catch(err => console.debug('Guide dismiss error:', err));
    }
</script>
@endif
