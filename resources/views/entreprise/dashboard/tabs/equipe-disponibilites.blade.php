<div>
    <div class="mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Disponibilités de {{ $membre->user->name ?? 'Membre' }}</h3>
        <p class="text-slate-600 dark:text-slate-400">Gérez les horaires réguliers et les indisponibilités ponctuelles</p>
    </div>

    <!-- Horaires réguliers -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Horaires réguliers</h4>
        
        <form action="{{ route('entreprise.equipe.disponibilites.update', [$entreprise->slug, $membre]) }}" method="POST">
            @csrf
            <div class="space-y-3">
                @php
                    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                    $disponibilites = $membre->disponibilites()->where('est_exceptionnel', false)->get()->keyBy('jour_semaine');
                @endphp
                @for($i = 0; $i < 7; $i++)
                    @php
                        $dispo = $disponibilites->get($i);
                    @endphp
                    <div class="flex flex-wrap items-center gap-4 p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <div class="w-28">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="horaires[{{ $i }}][est_disponible]"
                                value="1"
                                {{ !$dispo || ($dispo && $dispo->est_disponible) ? 'checked' : '' }}
                                onchange="toggleHoraire({{ $i }})"
                                class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500"
                            >
                            <span class="text-sm text-slate-700 dark:text-slate-300">Disponible</span>
                        </label>
                        <div class="flex items-center gap-2 flex-1">
                            <input 
                                type="time" 
                                name="horaires[{{ $i }}][heure_debut]"
                                value="{{ $dispo && $dispo->heure_debut ? \Carbon\Carbon::parse($dispo->heure_debut)->format('H:i') : '' }}"
                                class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                id="heure_debut_{{ $i }}"
                            >
                            <span class="text-slate-500">-</span>
                            <input 
                                type="time" 
                                name="horaires[{{ $i }}][heure_fin]"
                                value="{{ $dispo && $dispo->heure_fin ? \Carbon\Carbon::parse($dispo->heure_fin)->format('H:i') : '' }}"
                                class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                id="heure_fin_{{ $i }}"
                            >
                        </div>
                        <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                    </div>
                @endfor
            </div>
            <div class="mt-6">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Enregistrer les horaires
                </button>
            </div>
        </form>
    </div>

    <!-- Indisponibilités ponctuelles -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white">Indisponibilités ponctuelles</h4>
            <button 
                onclick="document.getElementById('indispo-form').classList.toggle('hidden')"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition"
            >
                + Ajouter
            </button>
        </div>

        <!-- Formulaire d'ajout (masqué par défaut) -->
        <form id="indispo-form" action="{{ route('entreprise.equipe.indisponibilites.store', [$entreprise->slug, $membre]) }}" method="POST" class="hidden mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de début *</label>
                    <input type="date" name="date_debut" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                    <input type="date" name="date_fin" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure début (optionnel)</label>
                    <input type="time" name="heure_debut" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure fin (optionnel)</label>
                    <input type="time" name="heure_fin" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Raison (optionnel)</label>
                    <input type="text" name="raison" placeholder="Ex: Congés, Formation..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                    Ajouter
                </button>
                <button type="button" onclick="document.getElementById('indispo-form').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition text-sm">
                    Annuler
                </button>
            </div>
        </form>

        <!-- Liste des indisponibilités -->
        @if($membre->indisponibilites->count() > 0)
            <div class="space-y-3">
                @foreach($membre->indisponibilites->sortBy('date_debut') as $indispo)
                    <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($indispo->date_debut)->format('d/m/Y') }}
                                @if($indispo->date_fin && $indispo->date_fin != $indispo->date_debut)
                                    - {{ \Carbon\Carbon::parse($indispo->date_fin)->format('d/m/Y') }}
                                @endif
                                @if($indispo->heure_debut && $indispo->heure_fin)
                                    ({{ \Carbon\Carbon::parse($indispo->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($indispo->heure_fin)->format('H:i') }})
                                @endif
                            </p>
                            @if($indispo->raison)
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $indispo->raison }}</p>
                            @endif
                        </div>
                        <form action="{{ route('entreprise.equipe.indisponibilites.delete', [$entreprise->slug, $membre, $indispo]) }}" method="POST" onsubmit="return confirm('Supprimer cette indisponibilité ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                                Supprimer
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-slate-500 dark:text-slate-400 text-center py-8">Aucune indisponibilité ponctuelle</p>
        @endif
    </div>
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
