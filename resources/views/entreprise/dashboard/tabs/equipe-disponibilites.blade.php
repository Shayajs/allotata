<div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Disponibilités de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Gérez les horaires réguliers et les indisponibilités ponctuelles</p>
    </div>

    @if($membre->id == 0 || $membre->user_id == $entreprise->user_id)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-1">Gérant de l'entreprise</h4>
                    <p class="text-sm text-blue-800 dark:text-blue-400">
                        En tant que propriétaire de l'entreprise, vos disponibilités sont gérées via les horaires de l'entreprise dans l'onglet <strong>Agenda</strong> du dashboard. 
                        Les horaires définis ici s'appliquent à tous les membres de l'équipe par défaut.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Horaires réguliers -->
    @if($membre->id != 0 && $membre->user_id != $entreprise->user_id)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Horaires réguliers</h4>
            
            <form action="{{ route('entreprise.equipe.disponibilites.update', [$entreprise->slug, $membre->id == 0 ? 'gerant' : $membre->id]) }}" method="POST">
                @csrf
                <div class="space-y-3">
                    @php
                        $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                        if ($membre->id == 0) {
                            $disponibilites = collect();
                        } else {
                            $disponibilites = $membre->disponibilites ? $membre->disponibilites()->where('est_exceptionnel', false)->get()->keyBy('jour_semaine') : collect();
                        }
                    @endphp
                    @for($i = 0; $i < 7; $i++)
                        @php
                            $dispo = $disponibilites->get($i);
                        @endphp
                        <div class="flex flex-wrap items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            <div class="w-32 flex-shrink-0">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer flex-shrink-0">
                                <input 
                                    type="checkbox" 
                                    name="horaires[{{ $i }}][est_disponible]"
                                    value="1"
                                    {{ !$dispo || ($dispo && $dispo->est_disponible) ? 'checked' : '' }}
                                    onchange="toggleHoraire({{ $i }})"
                                    class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500 focus:ring-2"
                                >
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Disponible</span>
                            </label>
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <input 
                                    type="time" 
                                    name="horaires[{{ $i }}][heure_debut]"
                                    value="{{ $dispo && $dispo->heure_debut ? \Carbon\Carbon::parse($dispo->heure_debut)->format('H:i') : '' }}"
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-medium"
                                    id="heure_debut_{{ $i }}"
                                >
                                <span class="text-slate-500 dark:text-slate-400 font-medium">-</span>
                                <input 
                                    type="time" 
                                    name="horaires[{{ $i }}][heure_fin]"
                                    value="{{ $dispo && $dispo->heure_fin ? \Carbon\Carbon::parse($dispo->heure_fin)->format('H:i') : '' }}"
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-medium"
                                    id="heure_fin_{{ $i }}"
                                >
                            </div>
                            <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                        </div>
                    @endfor
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition shadow-sm hover:shadow-md">
                        💾 Enregistrer les horaires
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Indisponibilités ponctuelles -->
    @if($membre->id != 0 && $membre->user_id != $entreprise->user_id)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Indisponibilités ponctuelles</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Ajoutez des périodes d'indisponibilité temporaires</p>
                </div>
                <button 
                    onclick="document.getElementById('indispo-form').classList.toggle('hidden')"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition shadow-sm hover:shadow-md"
                >
                    + Ajouter
                </button>
            </div>

            <!-- Formulaire d'ajout (masqué par défaut) -->
            <form id="indispo-form" action="{{ route('entreprise.equipe.indisponibilites.store', [$entreprise->slug, $membre->id == 0 ? 'gerant' : $membre->id]) }}" method="POST" class="hidden mb-6 p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700">
            @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de début *</label>
                        <input type="date" name="date_debut" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                        <input type="date" name="date_fin" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure début (optionnel)</label>
                        <input type="time" name="heure_debut" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure fin (optionnel)</label>
                        <input type="time" name="heure_fin" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                        <input type="text" name="raison" placeholder="Ex: Congés, Formation..." class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Ajouter
                    </button>
                    <button type="button" onclick="document.getElementById('indispo-form').classList.add('hidden')" class="px-6 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Annuler
                    </button>
                </div>
            </form>

            <!-- Liste des indisponibilités -->
            @if($membre->indisponibilites && $membre->indisponibilites->count() > 0)
                <div class="space-y-3">
                    @foreach($membre->indisponibilites->sortBy('date_debut') as $indispo)
                        <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                            <div class="flex-1">
                                <p class="font-semibold text-slate-900 dark:text-white mb-1">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($indispo->date_debut)->format('d/m/Y') }}
                                    @if($indispo->date_fin && $indispo->date_fin != $indispo->date_debut)
                                        - {{ \Carbon\Carbon::parse($indispo->date_fin)->format('d/m/Y') }}
                                    @endif
                                    @if($indispo->heure_debut && $indispo->heure_fin)
                                        <span class="text-slate-600 dark:text-slate-400 font-normal">
                                            ({{ \Carbon\Carbon::parse($indispo->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($indispo->heure_fin)->format('H:i') }})
                                        </span>
                                    @endif
                                </p>
                                @if($indispo->raison)
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $indispo->raison }}</p>
                                @endif
                            </div>
                            <form action="{{ route('entreprise.equipe.indisponibilites.delete', [$entreprise->slug, $membre->id == 0 ? 'gerant' : $membre->id, $indispo]) }}" method="POST" onsubmit="return confirm('Supprimer cette indisponibilité ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">Aucune indisponibilité ponctuelle</p>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Cliquez sur "Ajouter" pour en créer une</p>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
    function toggleHoraire(index) {
        const checkbox = document.querySelector(`input[name="horaires[${index}][est_disponible]"]`);
        const heureDebut = document.getElementById(`heure_debut_${index}`);
        const heureFin = document.getElementById(`heure_fin_${index}`);
        
        heureDebut.disabled = !checkbox.checked;
        heureFin.disabled = !checkbox.checked;
    }

    // Initialiser l'état des champs
    document.addEventListener('DOMContentLoaded', function() {
        for (let i = 0; i < 7; i++) {
            toggleHoraire(i);
        }
    });
</script>
