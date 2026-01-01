<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Paramètres de l'entreprise</h2>

    <!-- Formulaire principal -->
    <form action="{{ route('settings.entreprise.update', $entreprise->slug) }}" method="POST" class="space-y-6">
        @csrf

        <!-- Informations de base -->
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nom de l'entreprise *
                    </label>
                    <input 
                        type="text" 
                        name="nom" 
                        value="{{ old('nom', $entreprise->nom) }}"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Type d'activité *
                    </label>
                    <select 
                        name="type_activite" 
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="Coiffeuse" {{ $entreprise->type_activite == 'Coiffeuse' ? 'selected' : '' }}>Coiffeuse / Tressage</option>
                        <option value="Cuisinière" {{ $entreprise->type_activite == 'Cuisinière' ? 'selected' : '' }}>Cuisinière / Restauration</option>
                        <option value="Esthéticienne" {{ $entreprise->type_activite == 'Esthéticienne' ? 'selected' : '' }}>Esthéticienne</option>
                        <option value="Autre" {{ $entreprise->type_activite == 'Autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email *
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email', $entreprise->email) }}"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Téléphone
                    </label>
                    <input 
                        type="tel" 
                        name="telephone" 
                        value="{{ old('telephone', $entreprise->telephone) }}"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Description
                </label>
                <textarea 
                    name="description" 
                    rows="4"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >{{ old('description', $entreprise->description) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Mots-clés (séparés par des virgules)
                </label>
                <input 
                    type="text" 
                    name="mots_cles" 
                    value="{{ old('mots_cles', $entreprise->mots_cles) }}"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Ville
                    </label>
                    <input 
                        type="text" 
                        name="ville" 
                        value="{{ old('ville', $entreprise->ville) }}"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Rayon de déplacement (km)
                    </label>
                    <input 
                        type="number" 
                        name="rayon_deplacement" 
                        value="{{ old('rayon_deplacement', $entreprise->rayon_deplacement) }}"
                        min="0"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>
        </div>

        <!-- Options -->
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Options</h3>
            
            <div class="space-y-4">
                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
                    <input 
                        type="checkbox" 
                        name="afficher_nom_gerant" 
                        value="1"
                        {{ old('afficher_nom_gerant', $entreprise->afficher_nom_gerant) ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            Afficher mon nom avec l'entreprise
                        </span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Si activé, votre nom sera visible sur la page publique de l'entreprise.
                        </p>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
                    <input 
                        type="checkbox" 
                        name="prix_negociables" 
                        value="1"
                        {{ old('prix_negociables', $entreprise->prix_negociables) ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            💰 Prix négociables
                        </span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Les clients pourront négocier les prix via la messagerie.
                        </p>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
                    <input 
                        type="checkbox" 
                        name="rdv_uniquement_messagerie" 
                        value="1"
                        {{ old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            💬 Rendez-vous uniquement via messagerie
                        </span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            L'agenda public sera désactivé, les clients devront passer par la messagerie.
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                Enregistrer les modifications
            </button>
        </div>
    </form>

    <!-- Galerie de réalisations -->
    <div class="mt-8 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📸 Photos de réalisations</h3>
        
        @if($entreprise->realisationPhotos->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach($entreprise->realisationPhotos as $photo)
                    <div class="relative group">
                        <img 
                            src="{{ asset('media/' . $photo->photo_path) }}" 
                            alt="{{ $photo->titre ?? 'Réalisation' }}"
                            class="w-full h-32 object-cover rounded-lg border border-slate-200 dark:border-slate-600"
                        >
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                            <form action="{{ route('settings.entreprise.photo.delete', [$entreprise->slug, $photo->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit"
                                    onclick="return confirm('Supprimer cette photo ?')"
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                                >
                                    Supprimer
                                </button>
                            </form>
                        </div>
                        @if($photo->titre)
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400 truncate">{{ $photo->titre }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('settings.entreprise.photo.add', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" class="border border-slate-200 dark:border-slate-600 rounded-lg p-4 bg-white dark:bg-slate-800">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Ajouter une photo
                    </label>
                    <input 
                        type="file" 
                        name="photo" 
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                    >
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Titre (optionnel)
                        </label>
                        <input 
                            type="text" 
                            name="titre" 
                            placeholder="Ex: Tressage cheveux crépus"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Description (optionnel)
                        </label>
                        <input 
                            type="text" 
                            name="description" 
                            placeholder="Description..."
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                </div>
                <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    Ajouter la photo
                </button>
            </div>
        </form>
    </div>
</div>
