<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Paramètres de l'entreprise</h2>

    <!-- Logo et Image de fond (en dehors du formulaire principal) -->
    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">🖼️ Logo et Image de fond</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Logo -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Logo de l'entreprise
                </label>
                
                @if($entreprise->logo)
                    <div class="mb-4 relative inline-block">
                        <img 
                            src="{{ asset('storage/' . $entreprise->logo) }}" 
                            alt="Logo {{ $entreprise->nom }}"
                            class="w-32 h-32 object-contain rounded-lg border-2 border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-2"
                        >
                        <form action="{{ route('settings.entreprise.logo.delete', $entreprise->slug) }}" method="POST" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit"
                                onclick="return confirm('Supprimer le logo ?')"
                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                            >
                                Supprimer
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mb-4 w-32 h-32 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                        <span class="text-slate-400 text-sm">Aucun logo</span>
                    </div>
                @endif
                
                <form action="{{ route('settings.entreprise.logo.upload', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" id="logo-form">
                    @csrf
                    <input 
                        type="file" 
                        name="logo" 
                        id="logo-input"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                        onchange="document.getElementById('logo-form').submit()"
                    >
                </form>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Format recommandé : PNG ou JPG, max 2MB. Le logo sera affiché sur votre page publique.
                </p>
            </div>

            <!-- Image de fond -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Image de fond
                </label>
                
                @if($entreprise->image_fond)
                    <div class="mb-4 relative inline-block">
                        <img 
                            src="{{ asset('storage/' . $entreprise->image_fond) }}" 
                            alt="Image de fond {{ $entreprise->nom }}"
                            class="w-full max-w-md h-48 object-cover rounded-lg border-2 border-slate-200 dark:border-slate-600"
                        >
                        <form action="{{ route('settings.entreprise.image-fond.delete', $entreprise->slug) }}" method="POST" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit"
                                onclick="return confirm('Supprimer l\'image de fond ?')"
                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                            >
                                Supprimer
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mb-4 w-full max-w-md h-48 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                        <span class="text-slate-400 text-sm">Aucune image de fond</span>
                    </div>
                @endif
                
                <form action="{{ route('settings.entreprise.image-fond.upload', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" id="image-fond-form">
                    @csrf
                    <input 
                        type="file" 
                        name="image_fond" 
                        id="image-fond-input"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        required
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                        onchange="document.getElementById('image-fond-form').submit()"
                    >
                </form>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Format recommandé : JPG ou PNG, max 2MB. L'image sera affichée en arrière-plan de votre page publique.
                </p>
            </div>
        </div>
    </div>

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
                            src="{{ asset('storage/' . $photo->photo_path) }}" 
                            alt="{{ $photo->titre ? $photo->titre : 'Réalisation' }}"
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

    <!-- Zone de danger -->
    <div class="mt-8 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Zone de danger
        </h3>
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">
            Ces actions sont irréversibles ou ont des conséquences importantes.
        </p>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">Archiver cette entreprise</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        L'entreprise sera masquée de votre tableau de bord et de la recherche publique. 
                        Vous aurez 30 jours pour la restaurer avant sa suppression définitive.
                    </p>
                </div>
                
                @if($entreprise->canBeArchived())
                    <button 
                        type="button"
                        onclick="openArchiveModal()"
                        class="flex-shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        Archiver
                    </button>
                @else
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 font-medium rounded-lg cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Archivage impossible
                        </span>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                            ⚠️ Vous devez d'abord annuler vos abonnements actifs.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'archivage -->
    <div id="archive-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Archiver "{{ $entreprise->nom }}" ?</h3>
                <p class="text-slate-600 dark:text-slate-400 text-sm">
                    Cette action va masquer votre entreprise du tableau de bord et de la recherche publique.
                </p>
            </div>
            
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-6">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Important :</strong> Vous aurez 30 jours pour restaurer votre entreprise depuis votre tableau de bord principal. Passé ce délai, elle sera définitivement supprimée.
                </p>
            </div>

            <div class="flex gap-3">
                <button 
                    type="button"
                    onclick="closeArchiveModal()"
                    class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition"
                >
                    Annuler
                </button>
                <form action="{{ route('settings.entreprise.delete', $entreprise->slug) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit"
                        class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition"
                    >
                        Confirmer l'archivage
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openArchiveModal() {
            document.getElementById('archive-modal').classList.remove('hidden');
        }

        function closeArchiveModal() {
            document.getElementById('archive-modal').classList.add('hidden');
        }

        // Fermer la modal en cliquant en dehors
        document.getElementById('archive-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeArchiveModal();
            }
        });
    </script>
</div>
