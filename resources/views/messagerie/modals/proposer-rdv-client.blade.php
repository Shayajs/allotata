<div id="modal-proposer-rdv-client" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-blue-500 flex items-center justify-center text-white text-2xl">
                    📅
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Proposer un rendez-vous</h2>
            </div>
            <button onclick="document.getElementById('modal-proposer-rdv-client').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="{{ route('messagerie.proposer-rdv-client', $entreprise->slug) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <!-- Prestation sélectionnée (si sélectionnée depuis la liste) -->
                <div id="prestation-selected" class="hidden bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-1">Prestation sélectionnée</p>
                            <p class="font-bold text-slate-900 dark:text-white" id="prestation-selected-name"></p>
                            <div class="flex items-center gap-4 mt-2 text-sm">
                                <span class="text-slate-600 dark:text-slate-400" id="prestation-selected-duree"></span>
                                <span class="font-bold text-green-600 dark:text-green-400" id="prestation-selected-price"></span>
                            </div>
                        </div>
                        <button type="button" onclick="clearPrestationSelection()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Sélection de prestation -->
                @if($prestations->count() > 0)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Choisir une prestation (optionnel)
                        </label>
                        <select 
                            id="proposition-type-service"
                            name="type_service_id" 
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            onchange="updatePrestationInfo(this)"
                        >
                            <option value="">Aucune prestation (service personnalisé)</option>
                            @foreach($prestations as $prestation)
                                <option value="{{ $prestation->id }}" data-nom="{{ $prestation->nom }}" data-prix="{{ $prestation->prix }}" data-duree="{{ $prestation->duree_minutes }}">
                                    {{ $prestation->nom }} - {{ number_format($prestation->prix, 2, ',', ' ') }} € ({{ $prestation->duree_minutes }} min)
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Vous pouvez sélectionner une prestation existante ou créer une proposition personnalisée
                        </p>
                    </div>
                @endif

                <input type="hidden" id="prestation-selected-id" name="prestation_selected_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                        <input type="date" name="date_rdv" id="proposition-date" required min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Heure de début *</label>
                        <input type="time" name="heure_debut" required class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Durée (minutes) *</label>
                        <input type="number" name="duree_minutes" id="proposition-duree" required min="15" max="480" step="15" value="30" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                        <input type="number" name="prix" id="proposition-prix" required min="0" step="0.01" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Lieu (optionnel)</label>
                    <input type="text" name="lieu" maxlength="255" placeholder="Adresse ou lieu du rendez-vous" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                    <textarea name="notes" rows="3" maxlength="1000" placeholder="Informations supplémentaires, préférences, etc." class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"></textarea>
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" onclick="document.getElementById('modal-proposer-rdv-client').classList.add('hidden')" class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition font-semibold">
                        Annuler
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all shadow-lg">
                        Envoyer la proposition
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function updatePrestationInfo(select) {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption.value) {
            const nom = selectedOption.dataset.nom;
            const prix = parseFloat(selectedOption.dataset.prix);
            const duree = parseInt(selectedOption.dataset.duree);
            
            document.getElementById('prestation-selected-name').textContent = nom;
            document.getElementById('prestation-selected-price').textContent = prix.toFixed(2) + ' €';
            document.getElementById('prestation-selected-duree').textContent = duree + ' min';
            document.getElementById('prestation-selected').classList.remove('hidden');
            document.getElementById('proposition-prix').value = prix;
            document.getElementById('proposition-duree').value = duree;
            document.getElementById('prestation-selected-id').value = selectedOption.value;
        } else {
            clearPrestationSelection();
        }
    }

    function clearPrestationSelection() {
        document.getElementById('prestation-selected').classList.add('hidden');
        document.getElementById('proposition-type-service').value = '';
        document.getElementById('prestation-selected-id').value = '';
    }

    // Si une prestation a été sélectionnée depuis la liste, l'afficher
    if (document.getElementById('prestation-selected-id').value) {
        const select = document.getElementById('proposition-type-service');
        const prestationId = document.getElementById('prestation-selected-id').value;
        select.value = prestationId;
        updatePrestationInfo(select);
    }
</script>

