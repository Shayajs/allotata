{{-- Édition profil entreprise (admin) — médias + infos + options RDV --}}
<div id="profil-edit" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
    <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Profil & médias</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Modifier les infos visibles comme le gérant, sans entrer dans son compte.</p>
        </div>
    </div>

    <div class="p-6 space-y-8">
        @if($errors->any())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Médias --}}
        <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Logo et image de fond</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Logo</label>
                    @if($entreprise->logo)
                        <div class="mb-3">
                            <img src="{{ asset('media/' . $entreprise->logo) }}" alt="Logo" class="w-28 h-28 object-contain rounded-lg border border-slate-200 dark:border-slate-600 bg-white p-2">
                            <form action="{{ route('admin.entreprises.logo.delete', $entreprise) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Supprimer le logo ?')" class="px-3 py-1.5 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded-lg">Supprimer</button>
                            </form>
                        </div>
                    @else
                        <div class="mb-3 w-28 h-28 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-xs text-slate-400">Aucun logo</div>
                    @endif
                    <form action="{{ route('admin.entreprises.logo.upload', $entreprise) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" required class="block w-full text-sm text-slate-600 dark:text-slate-300">
                        <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-lg">Uploader le logo</button>
                    </form>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Image de fond</label>
                    @if($entreprise->image_fond)
                        <div class="mb-3">
                            <img src="{{ asset('media/' . $entreprise->image_fond) }}" alt="Fond" class="w-full max-w-sm h-36 object-cover rounded-lg border border-slate-200 dark:border-slate-600">
                            <form action="{{ route('admin.entreprises.image-fond.delete', $entreprise) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Supprimer l\'image de fond ?')" class="px-3 py-1.5 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded-lg">Supprimer</button>
                            </form>
                        </div>
                    @else
                        <div class="mb-3 w-full max-w-sm h-36 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-xs text-slate-400">Aucune image</div>
                    @endif
                    <form action="{{ route('admin.entreprises.image-fond.upload', $entreprise) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="file" name="image_fond" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" required class="block w-full text-sm text-slate-600 dark:text-slate-300">
                        <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-lg">Uploader le fond</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Photos réalisations --}}
        <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Photos de réalisations</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                @forelse($entreprise->realisationPhotos as $photo)
                    <div class="relative group rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                        <img src="{{ asset('media/' . $photo->photo_path) }}" alt="{{ $photo->titre }}" class="w-full h-32 object-cover">
                        <form action="{{ route('admin.entreprises.photos.destroy', [$entreprise, $photo->id]) }}" method="POST" class="absolute top-2 right-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Supprimer cette photo ?')" class="px-2 py-1 text-[10px] font-bold bg-red-600 hover:bg-red-700 text-white rounded-lg opacity-90">Suppr.</button>
                        </form>
                        @if($photo->titre)
                            <p class="p-2 text-xs text-slate-600 dark:text-slate-300 truncate">{{ $photo->titre }}</p>
                        @endif
                    </div>
                @empty
                    <p class="col-span-full text-sm text-slate-500 dark:text-slate-400">Aucune photo de réalisation.</p>
                @endforelse
            </div>
            <form action="{{ route('admin.entreprises.photos.store', $entreprise) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Photo *</label>
                    <input type="file" name="photo" accept="image/*" required class="block w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Titre</label>
                    <input type="text" name="titre" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Description</label>
                    <input type="text" name="description" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-bold bg-green-600 hover:bg-green-700 text-white rounded-lg">Ajouter</button>
            </form>
        </div>

        {{-- Formulaire infos --}}
        <form action="{{ route('admin.entreprises.update', $entreprise) }}" method="POST" class="space-y-6">
            @csrf
            {{-- Email géré via le formulaire dédié (historique) — on conserve la valeur pour la validation --}}
            <input type="hidden" name="email" value="{{ old('email', $entreprise->email) }}">

            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom *</label>
                        <input type="text" name="nom" value="{{ old('nom', $entreprise->nom) }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type d'activité *</label>
                        <input type="text" name="type_activite" value="{{ old('type_activite', $entreprise->type_activite) }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                        <input type="tel" name="telephone" value="{{ old('telephone', $entreprise->telephone) }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mots-clés</label>
                        <input type="text" name="mots_cles" value="{{ old('mots_cles', $entreprise->mots_cles) }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea name="description" rows="4"
                                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ old('description', $entreprise->description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL vidéo</label>
                        <input type="url" name="video_url" value="{{ old('video_url', $entreprise->video_url) }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="afficher_video" value="1" {{ old('afficher_video', $entreprise->afficher_video) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                            Afficher la vidéo sur la page publique
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site web externe</label>
                        <input type="url" name="site_web_externe" value="{{ old('site_web_externe', $entreprise->site_web_externe) }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="mb-2">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Localisation</h4>
                    <x-entreprise.localisation-form :entreprise="$entreprise" />
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Options RDV</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="afficher_nom_gerant" value="1" {{ old('afficher_nom_gerant', $entreprise->afficher_nom_gerant) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">Afficher le nom du gérant</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="prix_negociables" value="1" {{ old('prix_negociables', $entreprise->prix_negociables) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">Prix négociables</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="rdv_uniquement_messagerie" value="1" {{ old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">RDV uniquement via messagerie</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="accepter_reservations_auto" value="1" {{ old('accepter_reservations_auto', $entreprise->accepter_reservations_auto) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">Accepter automatiquement les réservations</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="livraison_disponible_par_defaut" value="1" {{ old('livraison_disponible_par_defaut', $entreprise->livraison_disponible_par_defaut) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">Livraison disponible par défaut</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg">
                        <input type="checkbox" name="vente_sur_place_disponible_par_defaut" value="1" {{ old('vente_sur_place_disponible_par_defaut', $entreprise->vente_sur_place_disponible_par_defaut) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600">
                        <span class="text-sm text-slate-900 dark:text-white">Vente sur place par défaut</span>
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Intervalle créneaux (minutes) *</label>
                        <input type="number" name="intervalle_creneaux_minutes" min="5" max="180" required
                               value="{{ old('intervalle_creneaux_minutes', $entreprise->intervalle_creneaux_minutes ?? 30) }}"
                               class="w-full max-w-xs px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition">
                    Enregistrer le profil
                </button>
            </div>
        </form>

        <p class="text-xs text-slate-500 dark:text-slate-400">
            Pour changer l'email professionnel, utilisez le formulaire email dédié (avec historique) plus bas sur cette page.
        </p>
    </div>
</div>
