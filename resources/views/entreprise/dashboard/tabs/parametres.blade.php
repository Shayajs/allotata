<div>
    <div class="flex items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Paramètres de l'entreprise</h2>
        <x-course-link-badge page-key="entreprise.parametres" :course-links="$courseLinks ?? []" />
    </div>

    <!-- Logo et Image de fond (en dehors du formulaire principal) -->
    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Logo et Image de fond
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Logo -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Logo de l'entreprise
                </label>
                
                @if($entreprise->logo)
                    <div class="mb-4 relative inline-block">
                        <img 
                            src="{{ asset('media/' . $entreprise->logo) }}" 
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
                    <x-file-upload 
                        name="logo" 
                        id="logo-input"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" 
                        :required="true"
                        maxSize="2 Mo"
                    />
                </form>
            </div>

            <!-- Image de fond -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Image de fond
                </label>
                
                @if($entreprise->image_fond)
                    <div class="mb-4 relative inline-block">
                        <img 
                            src="{{ asset('media/' . $entreprise->image_fond) }}" 
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
                    <x-file-upload 
                        name="image_fond" 
                        id="image-fond-input"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" 
                        :required="true"
                        maxSize="2 Mo"
                    />
                </form>
            </div>
        </div>
    </div>

    <!-- Formulaire principal -->
    <form id="parametres-form" action="{{ route('settings.entreprise.update', $entreprise->slug) }}" method="POST" class="space-y-6">
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
                        <optgroup label="Beauté & Bien-être">
                            <option value="Coiffeuse" {{ $entreprise->type_activite == 'Coiffeuse' ? 'selected' : '' }}>Coiffure / Tressage</option>
                            <option value="Esthéticienne" {{ $entreprise->type_activite == 'Esthéticienne' ? 'selected' : '' }}>Soins esthétiques</option>
                            <option value="Massage" {{ $entreprise->type_activite == 'Massage' ? 'selected' : '' }}>Massage / Relaxation</option>
                            <option value="Onglerie" {{ $entreprise->type_activite == 'Onglerie' ? 'selected' : '' }}>Onglerie / Manucure</option>
                            <option value="Maquillage" {{ $entreprise->type_activite == 'Maquillage' ? 'selected' : '' }}>Maquillage professionnel</option>
                            <option value="Barbier" {{ $entreprise->type_activite == 'Barbier' ? 'selected' : '' }}>Barbier</option>
                        </optgroup>
                        <optgroup label="Restauration & Alimentation">
                            <option value="Restauration" {{ $entreprise->type_activite == 'Restauration' ? 'selected' : '' }}>Restauration</option>
                            <option value="Cuisinière" {{ $entreprise->type_activite == 'Cuisinière' ? 'selected' : '' }}>Traiteur / Cuisine à domicile</option>
                            <option value="Pâtisserie" {{ $entreprise->type_activite == 'Pâtisserie' ? 'selected' : '' }}>Pâtisserie / Boulangerie</option>
                            <option value="Catering" {{ $entreprise->type_activite == 'Catering' ? 'selected' : '' }}>Catering / Événements</option>
                        </optgroup>
                        <optgroup label="Photo & Vidéo">
                            <option value="Photographie" {{ $entreprise->type_activite == 'Photographie' ? 'selected' : '' }}>Photographie</option>
                            <option value="Vidéographie" {{ $entreprise->type_activite == 'Vidéographie' ? 'selected' : '' }}>Vidéographie</option>
                            <option value="Photographe_Mariage" {{ $entreprise->type_activite == 'Photographe_Mariage' ? 'selected' : '' }}>Photographe de mariage</option>
                            <option value="Studio_Photo" {{ $entreprise->type_activite == 'Studio_Photo' ? 'selected' : '' }}>Studio photo</option>
                        </optgroup>
                        <optgroup label="Éducation & Formation">
                            <option value="Cours_Particuliers" {{ $entreprise->type_activite == 'Cours_Particuliers' ? 'selected' : '' }}>Cours particuliers</option>
                            <option value="Formation" {{ $entreprise->type_activite == 'Formation' ? 'selected' : '' }}>Formation professionnelle</option>
                            <option value="Soutien_Scolaire" {{ $entreprise->type_activite == 'Soutien_Scolaire' ? 'selected' : '' }}>Soutien scolaire</option>
                            <option value="Langues" {{ $entreprise->type_activite == 'Langues' ? 'selected' : '' }}>Cours de langues</option>
                        </optgroup>
                        <optgroup label="Services à domicile">
                            <option value="Ménage" {{ $entreprise->type_activite == 'Ménage' ? 'selected' : '' }}>Ménage / Aide à domicile</option>
                            <option value="Repassage" {{ $entreprise->type_activite == 'Repassage' ? 'selected' : '' }}>Repassage</option>
                            <option value="Garde_Enfants" {{ $entreprise->type_activite == 'Garde_Enfants' ? 'selected' : '' }}>Garde d'enfants / Baby-sitting</option>
                            <option value="Assistant_Virtuel" {{ $entreprise->type_activite == 'Assistant_Virtuel' ? 'selected' : '' }}>Assistant(e) virtuel(le)</option>
                        </optgroup>
                        <optgroup label="Bricolage & Rénovation">
                            <option value="Peinture" {{ $entreprise->type_activite == 'Peinture' ? 'selected' : '' }}>Peinture / Rénovation</option>
                            <option value="Plomberie" {{ $entreprise->type_activite == 'Plomberie' ? 'selected' : '' }}>Plomberie</option>
                            <option value="Électricité" {{ $entreprise->type_activite == 'Électricité' ? 'selected' : '' }}>Électricité</option>
                            <option value="Menuiserie" {{ $entreprise->type_activite == 'Menuiserie' ? 'selected' : '' }}>Menuiserie</option>
                        </optgroup>
                        <optgroup label="Événements">
                            <option value="Organisation_Événements" {{ $entreprise->type_activite == 'Organisation_Événements' ? 'selected' : '' }}>Organisation d'événements</option>
                            <option value="Animation" {{ $entreprise->type_activite == 'Animation' ? 'selected' : '' }}>Animation / DJ</option>
                            <option value="Décoration" {{ $entreprise->type_activite == 'Décoration' ? 'selected' : '' }}>Décoration événementielle</option>
                        </optgroup>
                        <optgroup label="Santé & Sport">
                            <option value="Coach_Sportif" {{ $entreprise->type_activite == 'Coach_Sportif' ? 'selected' : '' }}>Coach sportif / Fitness</option>
                            <option value="Yoga" {{ $entreprise->type_activite == 'Yoga' ? 'selected' : '' }}>Yoga / Pilates</option>
                            <option value="Nutritionniste" {{ $entreprise->type_activite == 'Nutritionniste' ? 'selected' : '' }}>Nutritionniste / Diététicien</option>
                        </optgroup>
                        <optgroup label="Mode & Création">
                            <option value="Couture" {{ $entreprise->type_activite == 'Couture' ? 'selected' : '' }}>Couture / Retouches</option>
                            <option value="Styliste" {{ $entreprise->type_activite == 'Styliste' ? 'selected' : '' }}>Styliste</option>
                            <option value="Accessoires" {{ $entreprise->type_activite == 'Accessoires' ? 'selected' : '' }}>Création d'accessoires</option>
                        </optgroup>
                        <optgroup label="Autres">
                            <option value="Autre" {{ $entreprise->type_activite == 'Autre' ? 'selected' : '' }}>Autre</option>
                        </optgroup>
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

            <!-- Section Vidéo -->
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Vidéo de présentation
                </h4>
                
                @if($entreprise->video_url)
                    <div class="mb-4 p-3 bg-white dark:bg-slate-800 rounded-lg border border-blue-200 dark:border-blue-700">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                            <strong>URL actuelle :</strong> 
                            <a href="{{ $entreprise->video_url }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                                {{ strlen($entreprise->video_url) > 60 ? substr($entreprise->video_url, 0, 60) . '...' : $entreprise->video_url }}
                            </a>
                        </p>
                        <button 
                            type="button"
                            onclick="if(confirm('Supprimer la vidéo ?')) { document.getElementById('video_url').value = ''; const afficherVideo = document.getElementById('afficher_video'); if(afficherVideo) afficherVideo.checked = false; document.getElementById('parametres-form').submit(); }"
                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                        >
                            Supprimer la vidéo
                        </button>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        URL de la vidéo (YouTube, Dailymotion, Vimeo, etc.)
                    </label>
                    <input 
                        type="url" 
                        name="video_url" 
                        id="video_url"
                        value="{{ old('video_url', $entreprise->video_url) }}"
                        placeholder="https://www.youtube.com/watch?v=..."
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Collez le lien complet de votre vidéo (YouTube, Dailymotion, Vimeo, etc.)
                    </p>
                </div>

                <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
                    <input 
                        type="checkbox" 
                        name="afficher_video" 
                        id="afficher_video"
                        value="1"
                        {{ old('afficher_video', $entreprise->afficher_video ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            Afficher la vidéo sur la page publique
                        </span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Si activé, la vidéo sera affichée juste au-dessus de la description sur votre page publique.
                        </p>
                    </div>
                </label>
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

            <div class="mb-6">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Localisation</h4>
                <x-entreprise.localisation-form :entreprise="$entreprise" />
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
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Prix négociables
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

                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
                    <input 
                        type="checkbox" 
                        name="accepter_reservations_auto" 
                        value="1"
                        {{ old('accepter_reservations_auto', $entreprise->accepter_reservations_auto) ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                    >
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            ✅ Accepter automatiquement les réservations
                        </span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Si activé, les réservations seront automatiquement confirmées sans attendre votre validation. Désactivé par défaut.
                        </p>
                    </div>
                </label>

                <div class="p-4 border border-slate-200 dark:border-slate-600 rounded-lg">
                    <label for="intervalle_creneaux_minutes" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">
                        Intervalle entre les créneaux proposés (minutes)
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                        Écart entre deux heures proposées sur l'agenda public et le site (ex. 15, 30, 36). La valeur 30 conserve le comportement actuel par défaut.
                    </p>
                    <input
                        id="intervalle_creneaux_minutes"
                        type="number"
                        name="intervalle_creneaux_minutes"
                        min="5"
                        max="180"
                        step="1"
                        required
                        value="{{ old('intervalle_creneaux_minutes', $entreprise->intervalle_creneaux_minutes ?? 30) }}"
                        class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500"
                    >
                    @error('intervalle_creneaux_minutes')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                Enregistrer les modifications
            </button>
        </div>
    </form>

    <!-- Google Calendar -->
    <div class="mt-8 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6" id="google-calendar-section">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Synchronisation Google Agenda
            </h3>
            @if($entreprise->aGoogleCalendar())
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs font-bold rounded-full border border-green-300 dark:border-green-700">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Connecté
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-600 text-slate-500 dark:text-slate-400 text-xs font-medium rounded-full">
                    <span class="w-2 h-2 bg-slate-400 rounded-full"></span>
                    Non connecté
                </span>
            @endif
        </div>

        {{-- Messages flash spécifiques Google Calendar --}}
        @if(session('success') && str_contains(session('success'), 'Google'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error') && str_contains(session('error'), 'Google'))
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        @if($entreprise->aGoogleCalendar())
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-white dark:bg-green-800 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                            Google Agenda est connecté
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            Vos réservations sont automatiquement synchronisées avec votre agenda Google. Les événements ajoutés sur Google créent des indisponibilités dans Allotata.
                        </p>
                        @if($entreprise->google_token_expires_at)
                            <p class="text-xs text-green-500 dark:text-green-500 mt-2">
                                Token valide jusqu'au {{ $entreprise->google_token_expires_at->format('d/m/Y à H:i') }} (rafraîchi automatiquement)
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <form action="{{ route('google-calendar.disconnect', $entreprise->slug) }}" method="POST">
                @csrf
                <button
                    type="submit"
                    onclick="return confirm('Déconnecter Google Agenda ? La synchronisation sera arrêtée.')"
                    class="px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg transition border border-red-200 dark:border-red-800 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnecter Google Agenda
                </button>
            </form>
        @else
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                <p class="text-sm text-blue-800 dark:text-blue-300 mb-2">
                    Connectez votre Google Agenda pour synchroniser automatiquement vos réservations. Les nouvelles réservations apparaîtront sur votre calendrier Google, et les événements ajoutés sur Google bloqueront les créneaux dans Allotata.
                </p>
                <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                    <li>Synchronisation bidirectionnelle automatique</li>
                    <li>Évitez les doublons de rendez-vous</li>
                    <li>Vos créneaux occupés sur Google deviennent indisponibles ici</li>
                </ul>
            </div>

            <a
                href="{{ route('google-calendar.redirect', $entreprise->slug) }}"
                class="inline-flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500 rounded-lg transition shadow-sm hover:shadow-md"
            >
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Connecter Google Agenda
                </span>
            </a>
        @endif
    </div>

    <!-- Galerie de réalisations -->
    <div class="mt-8 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📸 Photos de réalisations</h3>
        
        @if($entreprise->realisationPhotos->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach($entreprise->realisationPhotos as $photo)
                    <div class="relative group">
                        <img 
                            src="{{ asset('media/' . $photo->photo_path) }}" 
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
                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Vous devez d'abord annuler vos abonnements actifs.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'archivage -->
    <div id="archive-modal" class="hidden fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="modal-content max-w-md w-full p-6">
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
