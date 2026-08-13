<div>
    <div class="flex items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Services</h2>
        <x-course-link-badge page-key="entreprise.mes-services" :course-links="$courseLinks ?? []" />
    </div>

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

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-red-800 dark:text-red-300 font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Configuration de l'ordre d'affichage -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Ordre d'affichage des services</h3>
        <form action="{{ route('entreprise.dashboard.update-mode-ordre', $entreprise->slug) }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="type" value="services">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300 sm:shrink-0">Mode de tri :</label>
            <select name="mode_ordre" onchange="this.form.submit()" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="manuel" {{ ($entreprise->mode_ordre_services ?? 'manuel') === 'manuel' ? 'selected' : '' }}>Manuel (ordre personnalisé)</option>
                <option value="ventes" {{ ($entreprise->mode_ordre_services ?? 'manuel') === 'ventes' ? 'selected' : '' }}>Par nombre de réservations</option>
                <option value="statistiques" {{ ($entreprise->mode_ordre_services ?? 'manuel') === 'statistiques' ? 'selected' : '' }}>Par statistiques (clics)</option>
            </select>
            @if(($entreprise->mode_ordre_services ?? 'manuel') === 'manuel')
                <button 
                    type="button"
                    onclick="enableReorderServices()"
                    class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition"
                >
                    Réorganiser manuellement
                </button>
            @endif
        </form>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Les 9 premiers services s'affichent directement, les autres dans un menu déroulant sur la page publique.
        </p>
    </div>

    <!-- Section Types de services -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </span>
                Types de services
            </h3>
            <div class="flex items-center gap-2">
                <button 
                    onclick="openServiceModal()"
                    class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
                >
                    + Ajouter
                </button>
                <div class="relative" id="services-options-dropdown">
                    <button 
                        type="button"
                        onclick="document.getElementById('services-options-menu').classList.toggle('hidden')"
                        class="px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-xl transition border border-slate-200 dark:border-slate-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </button>
                    <div id="services-options-menu" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden">
                        <button type="button" onclick="document.getElementById('modal-bulk-create').classList.remove('hidden'); document.getElementById('services-options-menu').classList.add('hidden');" class="w-full px-4 py-3 text-left text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-3">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Création rapide
                        </button>
                        <button type="button" onclick="document.getElementById('modal-prestation-libre').classList.remove('hidden'); document.getElementById('services-options-menu').classList.add('hidden');" class="w-full px-4 py-3 text-left text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-3 border-t border-slate-100 dark:border-slate-700">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Prestation libre
                            @if($entreprise->prestation_libre_active ?? false)
                                <span class="ml-auto px-1.5 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Actif</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($typesServices && $typesServices->count() > 0)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" id="services-list">
                @foreach($typesServices as $service)
                    <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow {{ $service->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $service->nom }}</h4>
                                @if($service->images->count() > 0)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">📷 {{ $service->images->count() }} image(s)</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 justify-end">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $service->est_actif ? 'Actif' : 'Inactif' }}
                                </span>
                                @if(($service->type_structure ?? '') === 'date_butoire')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Date butoire</span>
                                @endif
                            </div>
                        </div>
                        @if($service->description)
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ $service->description }}</p>
                        @endif
                        <div class="flex items-center gap-4 text-sm mb-4">
                            @if(($service->type_structure ?? '') === 'date_butoire')
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $service->duree_minutes }} jour{{ $service->duree_minutes > 1 ? 's' : '' }} de délai
                            </span>
                            @elseif(($service->type_structure ?? '') === 'multi_jours')
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->duree_minutes }} min/session
                            </span>
                            @elseif(($service->type_structure ?? '') === 'multi_rendez_vous')
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->duree_minutes }} min/RDV
                            </span>
                            @else
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->duree_minutes }} min
                            </span>
                            @endif
                            <span class="flex items-center gap-1 font-bold text-green-600 dark:text-green-400">
                                {{ number_format($service->prix, 0, ',', ' ') }} €
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                type="button"
                                onclick="editServiceFromButton(this)"
                                data-service="{{ json_encode([
                                    'id' => $service->id,
                                    'nom' => $service->nom,
                                    'description' => $service->description ?? '',
                                    'duree' => $service->duree_minutes,
                                    'prix' => (float) $service->prix,
                                    'est_actif' => (bool) $service->est_actif,
                                    'type_structure' => $service->type_structure ?? 'ponctuel',
                                    'frequence_recurrence' => $service->frequence_recurrence,
                                    'intervalle_jours' => $service->intervalle_jours,
                                    'capacite_max' => $service->capacite_max,
                                    'seuil_minimum' => $service->seuil_minimum,
                                    'est_prix_par_personne' => (bool) ($service->est_prix_par_personne ?? true),
                                    'images' => $service->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values(),
                                    'options' => $service->options->map(fn($opt) => ['id' => $opt->id, 'nom' => $opt->nom, 'type' => $opt->type, 'obligatoire' => $opt->obligatoire, 'choices' => $opt->choices])->values(),
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                                class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                            >
                                Modifier
                            </button>
                            <form action="{{ route('agenda.service.duplicate', [$entreprise->slug, $service->id]) }}" method="POST" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="px-3 py-2 text-sm font-medium bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-400 rounded-lg transition" title="Dupliquer ce service">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </form>
                            <form action="{{ route('agenda.service.delete', [$entreprise->slug, $service->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce service ?');" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 text-sm font-medium bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <script>
                window.enableReorderServices = function() {
                    alert('Fonctionnalité de réordonnancement à venir. Pour l\'instant, vous pouvez modifier l\'ordre manuellement en éditant chaque service.');
                    // TODO: Implémenter le drag & drop avec Sortable.js ou similaire
                };
            </script>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="mb-4">Aucun service configuré</p>
                <button 
                    onclick="openServiceModal()"
                    class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all"
                >
                    Créer votre premier service
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Modal Création Rapide -->
<div id="modal-bulk-create" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" onclick="document.getElementById('modal-bulk-create').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 sm:p-8" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Création rapide</h3>
                    <button type="button" onclick="document.getElementById('modal-bulk-create').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form action="{{ route('agenda.service.bulk', $entreprise->slug) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Un service par ligne</label>
                            <textarea 
                                name="services_text" 
                                id="bulk-services-text"
                                rows="10"
                                required
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm transition-colors resize-none"
                                placeholder="Coupe homme, 30, 25&#10;Coupe femme, 45, 35&#10;Brushing, 20, 15&#10;Coloration, 90, 60&#10;Mèches, 120, 80"
                                oninput="updateBulkCount()"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Format : <strong>nom, durée, prix</strong> (durée et prix optionnels, défaut : 30 min / 0 €)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Type de structure</label>
                            <select name="type_structure" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-purple-500 dark:focus:border-purple-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                <option value="ponctuel" selected>Ponctuel</option>
                                <option value="multi_jours">Multi-jours</option>
                                <option value="multi_rendez_vous">Multi-rendez-vous</option>
                                <option value="date_butoire">Date butoire</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('modal-bulk-create').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" id="bulk-submit-btn" class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600 text-white font-bold rounded-xl transition-all shadow-lg">
                            Créer 0 services
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Prestation Libre -->
<div id="modal-prestation-libre" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" onclick="document.getElementById('modal-prestation-libre').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 sm:p-8" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Prestation libre</h3>
                    <button type="button" onclick="document.getElementById('modal-prestation-libre').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                    Proposez un tarif horaire pour des prestations sur demande. Les clients pourront vous contacter via la messagerie pour décrire leur besoin.
                </p>
                <form action="{{ route('entreprise.prestation-libre.update', $entreprise->slug) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-5">
                        <label class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <input 
                                type="checkbox" 
                                name="prestation_libre_active" 
                                id="prestation_libre_toggle"
                                value="1"
                                {{ ($entreprise->prestation_libre_active ?? false) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                onchange="document.getElementById('prestation-libre-fields').classList.toggle('hidden', !this.checked)"
                            >
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Activer la prestation libre</span>
                        </label>
                        
                        <div id="prestation-libre-fields" class="{{ ($entreprise->prestation_libre_active ?? false) ? '' : 'hidden' }} space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Tarif horaire (€/h) *</label>
                                <input 
                                    type="number" 
                                    name="tarif_horaire" 
                                    min="0" 
                                    step="0.01"
                                    value="{{ $entreprise->tarif_horaire ?? '' }}"
                                    placeholder="Ex : 30"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description courte (optionnel)</label>
                                <input 
                                    type="text" 
                                    name="prestation_libre_description" 
                                    maxlength="255"
                                    value="{{ $entreprise->prestation_libre_description ?? '' }}"
                                    placeholder="Ex : Jardinage, bricolage, sur devis"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('modal-prestation-libre').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Fermer le dropdown Options quand on clique en dehors
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('services-options-dropdown');
        const menu = document.getElementById('services-options-menu');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // Compteur de lignes pour la création rapide
    function updateBulkCount() {
        const text = document.getElementById('bulk-services-text').value;
        const lines = text.split('\n').filter(l => l.trim().length > 0);
        const btn = document.getElementById('bulk-submit-btn');
        const count = lines.length;
        btn.textContent = 'Créer ' + count + ' service' + (count > 1 ? 's' : '');
    }
</script>
