
<!-- Modal Ajout/Modification Service -->
<!-- Modal Ajout/Modification Service -->
<div id="modal-service" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Overlay avec Blur -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-service').classList.add('hidden')"></div>

    <!-- Conteneur Flex pour centrage -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
            <!-- Contenu de la modal : scroll sur mobile -->
            <div class="modal-content relative w-full max-w-5xl max-h-[90vh] overflow-y-auto transform rounded-2xl text-left transition-all mx-auto bg-white dark:bg-slate-800" onclick="event.stopPropagation()">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <!-- Colonne gauche : Formulaire (ordre 1 sur mobile, après les images en lg on garde l'ordre logique) -->
                <div class="p-4 sm:p-6 lg:p-8 order-1">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white" id="modal-title">
                            Ajouter un service
                        </h3>
                        <button type="button" onclick="document.getElementById('modal-service').classList.add('hidden')" class="p-2 -m-2 text-slate-400 hover:text-slate-500 touch-manipulation" aria-label="Fermer">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('agenda.service.store', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" id="service-form">
                        @csrf
                        <input type="hidden" name="type_service_id" id="type_service_id_unique_modal">
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom du service *</label>
                                <input 
                                    type="text" 
                                    name="nom" 
                                    id="service_nom"
                                    required
                                    class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    placeholder="Ex: Coupe homme"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                                <textarea 
                                    name="description" 
                                    id="service_description"
                                    rows="3"
                                    class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
                                ></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Durée (min) *</label>
                                    <input 
                                        type="number" 
                                        name="duree_minutes" 
                                        id="service_duree"
                                        required
                                        min="1"
                                        value="30"
                                        class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                                    <input 
                                        type="number" 
                                        name="prix" 
                                        id="service_prix"
                                        required
                                        min="0"
                                        step="0.01"
                                        value="25"
                                        class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Type de structure *</label>
                                <select 
                                    name="type_structure" 
                                    id="service_type_structure"
                                    required
                                    class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    onchange="toggleStructureFields()"
                                >
                                    <option value="ponctuel">Ponctuel (quelques heures dans une journée)</option>
                                    <option value="multi_jours">Multi-jours (s'étend sur plusieurs jours)</option>
                                    <option value="multi_rendez_vous">Multi-rendez-vous (plusieurs rendez-vous liés)</option>
                                    <option value="date_butoire">À date butoire (jour demandé, pas de créneau)</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span id="structure-help-ponctuel" class="structure-help">Service classique qui prend du temps dans une journée (ex: coiffure, massage)</span>
                                    <span id="structure-help-multi_jours" class="structure-help hidden">Service qui s'étend sur plusieurs jours (ex: photographie de mariage, tournage)</span>
                                    <span id="structure-help-multi_rendez_vous" class="structure-help hidden">Service avec plusieurs rendez-vous pour la même commande (ex: création de site web, suivi personnalisé)</span>
                                    <span id="structure-help-date_butoire" class="structure-help hidden">Le client choisit une date butoire ; l'entreprise gère la préparation et peut proposer une autre date si besoin.</span>
                                </p>
                            </div>
                            
                            <!-- Upload d'images -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Ajouter des images
                                </label>
                                <input 
                                    type="file" 
                                    name="images[]" 
                                    id="service_images"
                                    multiple
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    class="w-full px-4 py-2.5 sm:py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400 transition-colors"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sélectionnez une ou plusieurs images à ajouter</p>
                            </div>
                            
                            <label class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="est_actif" 
                                    id="service_actif"
                                    value="1"
                                    checked
                                    class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                >
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Service actif</span>
                            </label>

                            <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-lg font-bold text-slate-900 dark:text-white">Variantes / Options</h4>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="enable_options" class="sr-only peer" onchange="toggleOptions()">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="options-wrapper" class="hidden">
                                    <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600 mb-4">
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                            Ajoutez des variantes pour ce service (ex: longueur de cheveux, taille, etc.). Le client devra obligatoirement faire un choix.
                                        </p>
                                        
                                        <!-- Champs cachés pour simuler un groupe unique -->
                                        <input type="hidden" name="options[0][nom]" value="Options">
                                        <input type="hidden" name="options[0][type]" value="choix_unique">
                                        <input type="hidden" name="options[0][obligatoire]" value="1">
                                        
                                        <div id="choices-container-0" class="space-y-2">
                                            <!-- Les choix seront insérés ici -->
                                        </div>
                                        
                                        <button type="button" onclick="addChoice(0)" class="mt-3 text-sm px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition w-full border-dashed">
                                            + Ajouter une option
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <button type="button" onclick="document.getElementById('modal-service').classList.add('hidden')" class="w-full sm:flex-1 px-4 py-2.5 sm:py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition touch-manipulation">
                                Annuler
                            </button>
                            <button type="submit" class="w-full sm:flex-1 px-4 py-2.5 sm:py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl touch-manipulation">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Colonne droite : Gestion des images (après formulaire sur mobile) -->
                <div class="lg:col-span-1 order-2 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-700 p-4 sm:p-6 lg:p-8 overflow-y-auto lg:max-h-[80vh]">
                    <div class="sticky top-0 bg-white dark:bg-slate-800 pb-4 mb-4 border-b border-slate-200 dark:border-slate-700 z-10">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Images du service</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cliquez sur une image pour la définir comme couverture</p>
                    </div>
                    
                    <!-- Zone d'upload d'images -->
                    <div id="upload-zone" class="mb-6">
                        <label for="image-upload-input" id="upload-zone-label" class="block w-full p-6 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl cursor-pointer hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/10 transition-all">
                            <input 
                                type="file" 
                                id="image-upload-input"
                                multiple
                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                class="hidden"
                            >
                            <div class="text-center">
                                <svg class="w-10 h-10 mx-auto mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Ajouter des photos</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cliquez ou glissez-déposez</p>
                            </div>
                        </label>
                        <div id="upload-progress" class="hidden mt-2">
                            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Upload en cours...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Images existantes -->
                    <div id="existing-images-section" class="hidden">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Images actuelles</p>
                        <div id="existing-images-list" class="space-y-3"></div>
                    </div>
                    
                    <!-- Nouvelles images sélectionnées (pour nouveau service) -->
                    <div id="new-images-section" class="hidden">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Nouvelles images</p>
                        <div id="new-images-list" class="space-y-3"></div>
                    </div>
                    
                    <!-- Message si aucune image -->
                    <div id="no-images-message" class="text-center py-8 text-slate-400 dark:text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">Aucune image</p>
                        <p class="text-xs mt-1">Ajoutez des images ci-dessus</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    let currentServiceId = null;
    let currentServiceImages = [];
    let currentServiceOptions = [];
    let newImagesPreview = [];
    
    function editServiceFromButton(button) {
        const serviceId = parseInt(button.getAttribute('data-service-id'));
        const nom = button.getAttribute('data-service-nom') || '';
        const description = button.getAttribute('data-service-description') || '';
        const duree = parseInt(button.getAttribute('data-service-duree')) || 30;
        const prix = parseFloat(button.getAttribute('data-service-prix')) || 0;
        const estActif = button.getAttribute('data-service-actif') === 'true';
        const typeStructure = button.getAttribute('data-service-type-structure') || 'ponctuel';
        const imagesBase64 = button.getAttribute('data-service-images') || '';
        const optionsBase64 = button.getAttribute('data-service-options') || '';
        
        let images = [];
        try {
            if (imagesBase64) {
                const imagesJson = atob(imagesBase64);
                images = JSON.parse(imagesJson);
            }
        } catch (e) {
            console.error('Erreur parsing images:', e);
            images = [];
        }

        let options = [];
        try {
            if (optionsBase64) {
                const optionsJson = atob(optionsBase64);
                options = JSON.parse(optionsJson);
            }
        } catch (e) {
            console.error('Erreur parsing options:', e);
            options = [];
        }
        
        editService(serviceId, nom, description, duree, prix, estActif, images, typeStructure, options);
    }
    
    function openServiceModal() {
        currentServiceId = null;
        currentServiceImages = [];
        currentServiceOptions = [];
        newImagesPreview = [];
        
        document.getElementById('modal-service').classList.remove('hidden');
        
        // Réinitialiser l'ID du service
        const idInput = document.getElementById('type_service_id_unique_modal');
        if (idInput) idInput.value = '';
        
        document.getElementById('service_nom').value = '';
        document.getElementById('service_description').value = '';
        document.getElementById('service_duree').value = '30';
        document.getElementById('service_prix').value = '25';
        document.getElementById('service_type_structure').value = 'ponctuel';
        document.getElementById('service_actif').checked = true;
        document.getElementById('service_images').value = '';
        document.getElementById('modal-title').textContent = 'Ajouter un service';
        
        document.getElementById('choices-container-0').innerHTML = '';
        document.getElementById('enable_options').checked = false;
        toggleOptions();
        
        toggleStructureFields();
        
        // Cacher la zone d'upload direct quand on crée un nouveau service (car pas d'ID)
        document.getElementById('upload-zone').classList.add('hidden');
        
        updateImagesDisplay();
    }
    
    function toggleStructureFields() {
        const typeStructure = document.getElementById('service_type_structure').value;
        
        // Masquer tous les messages d'aide
        document.querySelectorAll('.structure-help').forEach(el => el.classList.add('hidden'));
        
        // Afficher le message d'aide correspondant
        const helpElement = document.getElementById('structure-help-' + typeStructure);
        if (helpElement) {
            helpElement.classList.remove('hidden');
        }
    }

    function editService(id, nom, description, duree, prix, estActif, images, typeStructure = 'ponctuel', options = []) {
        currentServiceId = id;
        currentServiceImages = images || [];
        currentServiceOptions = options || [];
        
        document.getElementById('modal-service').classList.remove('hidden');
        
        // Assigner l'ID au champ caché
        const idInput = document.getElementById('type_service_id_unique_modal');
        if (idInput) idInput.value = id;
        
        document.getElementById('service_nom').value = nom;
        document.getElementById('service_description').value = description || '';
        document.getElementById('service_duree').value = duree;
        document.getElementById('service_prix').value = prix;
        document.getElementById('service_type_structure').value = typeStructure || 'ponctuel';
        toggleStructureFields();
        document.getElementById('service_actif').checked = estActif;
        document.getElementById('service_images').value = '';
        document.getElementById('modal-title').textContent = 'Modifier le service';
        
        // Afficher la zone d'upload direct
        document.getElementById('upload-zone').classList.remove('hidden');
        
        // Gérer les options simplifiées
        const container = document.getElementById('choices-container-0');
        container.innerHTML = '';
        const enableOptionsCheckbox = document.getElementById('enable_options');
        
        if (options && options.length > 0) {
            enableOptionsCheckbox.checked = true;
            toggleOptions(true); // true = skipDefaultRow
            
            // On prend le premier groupe d'options (mode simplifié)
            const firstGroup = options[0];
            if (firstGroup && firstGroup.choices) {
                firstGroup.choices.forEach(choice => {
                    addChoice(0, choice);
                });
            }
        } else {
            enableOptionsCheckbox.checked = false;
            toggleOptions();
        }
        
        updateImagesDisplay();
    }

    function toggleOptions(skipDefaultRow = false) {
        const checkbox = document.getElementById('enable_options');
        const wrapper = document.getElementById('options-wrapper');
        const container = document.getElementById('choices-container-0');
        
        // Sélectionner tous les inputs et selects dans le wrapper pour les activer/désactiver
        const inputs = wrapper.querySelectorAll('input, select');
        
        if (checkbox.checked) {
            wrapper.classList.remove('hidden');
            
            // Réactiver les champs
            inputs.forEach(input => input.disabled = false);
            
            // Si le conteneur est vide et qu'on ne demande pas de sauter l'ajout, ajouter une ligne par défaut
            if (!skipDefaultRow && container.children.length === 0) {
                addChoice(0);
            }
        } else {
            wrapper.classList.add('hidden');
            
            // Désactiver les champs pour qu'ils ne soient pas envoyés (évite l'erreur de validation "required")
            inputs.forEach(input => input.disabled = true);
        }
    }

    function addChoice(optionIdx, choiceData = null) {
        const container = document.getElementById(`choices-container-${optionIdx}`);
        const choiceIdx = container.children.length;
        
        const nom = choiceData ? choiceData.nom : '';
        const prix = choiceData ? choiceData.prix_supplementaire : 0;
        const temps = choiceData ? choiceData.temps_supplementaire : 0;
        
        const choiceDiv = document.createElement('div');
        choiceDiv.className = 'flex items-center gap-2 choice-item bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm transition-all hover:shadow-md';
        
        choiceDiv.innerHTML = `
            <div class="flex-1">
                <input type="text" name="options[${optionIdx}][choices][${choiceIdx}][nom]" value="${nom}" placeholder="Nom (ex: 15 tresses)" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
            </div>
            <div class="w-32">
                <div class="relative group">
                    <input type="number" name="options[${optionIdx}][choices][${choiceIdx}][prix]" value="${prix}" step="0.01" placeholder="0" class="w-full pl-3 pr-10 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold transition-colors group-focus-within:text-green-500 pointer-events-none">€</span>
                </div>
            </div>
            <div class="w-40">
                <div class="relative group">
                    <input type="number" name="options[${optionIdx}][choices][${choiceIdx}][temps]" value="${temps}" placeholder="0" class="w-full pl-3 pr-20 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold transition-colors group-focus-within:text-green-500 text-xs pointer-events-none">minutes</span>
                </div>
            </div>
            <button type="button" onclick="this.closest('.choice-item').remove()" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all" title="Supprimer ce choix">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;
        
        container.appendChild(choiceDiv);
    }
    
    function updateImagesDisplay() {
        const existingSection = document.getElementById('existing-images-section');
        const existingList = document.getElementById('existing-images-list');
        const newSection = document.getElementById('new-images-section');
        const newList = document.getElementById('new-images-list');
        const noImagesMessage = document.getElementById('no-images-message');
        
        const hasExisting = currentServiceImages && currentServiceImages.length > 0;
        const hasNew = newImagesPreview.length > 0;
        
        // Afficher/masquer les sections
        if (hasExisting) {
            existingSection.classList.remove('hidden');
            existingList.innerHTML = '';
            
            currentServiceImages.forEach((img) => {
                const div = document.createElement('div');
                div.className = 'relative group cursor-pointer';
                div.onclick = () => setImageAsCover(currentServiceId, img.id, img.est_couverture);
                
                const borderClass = img.est_couverture ? 'border-green-500 ring-2 ring-green-500' : 'border-slate-200 dark:border-slate-600';
                
                div.innerHTML = `
                    <div class="relative overflow-hidden rounded-lg border-2 ${borderClass} hover:border-green-400 transition-all">
                        <img src="${String(img.path || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;')}" alt="Image" class="w-full h-32 object-cover">
                        ${img.est_couverture ? '<div class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold bg-green-500 text-white rounded shadow-lg">⭐ Couverture</div>' : '<div class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold bg-slate-800/70 text-white rounded opacity-0 group-hover:opacity-100 transition">Cliquer pour définir comme couverture</div>'}
                        <button type="button" class="absolute top-2 right-2 p-1.5 bg-red-500 hover:bg-red-600 text-white rounded opacity-0 group-hover:opacity-100 transition shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                const deleteBtn = div.querySelector('button');
                deleteBtn.onclick = (e) => {
                    e.stopPropagation();
                    deleteServiceImage(currentServiceId, img.id);
                };
                
                existingList.appendChild(div);
            });
        } else {
            existingSection.classList.add('hidden');
        }
        
        if (hasNew) {
            newSection.classList.remove('hidden');
            newList.innerHTML = '';
            
            newImagesPreview.forEach((preview, index) => {
                const div = document.createElement('div');
                div.className = 'relative overflow-hidden rounded-lg border-2 border-slate-200 dark:border-slate-600';
                div.innerHTML = `
                    <img src="${String(preview.url || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;')}" alt="Nouvelle image ${index + 1}" class="w-full h-32 object-cover">
                    <div class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold bg-blue-500 text-white rounded">Nouvelle</div>
                `;
                newList.appendChild(div);
            });
        } else {
            newSection.classList.add('hidden');
        }
        
        // Afficher le message si aucune image
        if (!hasExisting && !hasNew) {
            noImagesMessage.classList.remove('hidden');
        } else {
            noImagesMessage.classList.add('hidden');
        }
    }
    
    // Fonction pour traiter les fichiers uploadés
    function handleImageFiles(files) {
        if (files.length === 0) return;
        
        // Si on a un service existant, uploader immédiatement
        if (currentServiceId) {
            uploadImagesImmediately(files);
        } else {
            // Pour un nouveau service, ajouter les fichiers au champ images[] du formulaire
            const formImagesInput = document.getElementById('service_images');
            const dataTransfer = new DataTransfer();
            
            // Ajouter les fichiers existants
            if (formImagesInput.files) {
                Array.from(formImagesInput.files).forEach(file => {
                    dataTransfer.items.add(file);
                });
            }
            
            // Ajouter les nouveaux fichiers
            files.forEach(file => {
                dataTransfer.items.add(file);
                
                // Créer un aperçu
                const reader = new FileReader();
                reader.onload = function(e) {
                    newImagesPreview.push({
                        url: e.target.result,
                        file: file,
                        index: newImagesPreview.length
                    });
                    updateImagesDisplay();
                };
                reader.readAsDataURL(file);
            });
            
            // Mettre à jour l'input du formulaire
            formImagesInput.files = dataTransfer.files;
            
            // Déclencher l'événement change pour mettre à jour l'affichage
            formImagesInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    
    // Upload immédiat depuis la zone d'upload (section droite)
    document.getElementById('image-upload-input')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        handleImageFiles(files);
        e.target.value = '';
    });
    
    // Gestion du drag & drop
    const uploadZoneLabel = document.getElementById('upload-zone-label');
    if (uploadZoneLabel) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZoneLabel.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZoneLabel.addEventListener(eventName, function() {
                uploadZoneLabel.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadZoneLabel.addEventListener(eventName, function() {
                uploadZoneLabel.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
            }, false);
        });
        
        uploadZoneLabel.addEventListener('drop', function(e) {
            const files = Array.from(e.dataTransfer.files).filter(file => 
                file.type.startsWith('image/')
            );
            handleImageFiles(files);
        }, false);
    }
    
    // Fonction pour uploader immédiatement les images (une par une)
    async function uploadImagesImmediately(files) {
        const uploadProgress = document.getElementById('upload-progress');
        uploadProgress.classList.remove('hidden');
        
        const url = '{{ route("agenda.service.image.upload", ["slug" => $entreprise->slug, "typeServiceId" => ":serviceId"]) }}'
            .replace(':serviceId', currentServiceId);
        
        let uploadedCount = 0;
        let errors = [];
        
        // Uploader chaque image séquentiellement
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const formData = new FormData();
            formData.append('image', file);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.image) {
                    // Ajouter la nouvelle image à la liste
                    currentServiceImages.push({
                        id: data.image.id,
                        path: data.image.path,
                        est_couverture: data.image.est_couverture
                    });
                    
                    // Si c'est la première image, elle devient couverture
                    if (data.image.est_couverture) {
                        // Retirer le statut couverture des autres
                        currentServiceImages.forEach(img => {
                            if (img.id !== data.image.id) {
                                img.est_couverture = false;
                            }
                        });
                    }
                    
                    uploadedCount++;
                    updateImagesDisplay();
                } else {
                    errors.push(`Erreur pour ${file.name}`);
                }
            } catch (error) {
                console.error('Erreur upload:', error);
                errors.push(`Erreur pour ${file.name}`);
            }
        }
        
        uploadProgress.classList.add('hidden');
        
        if (errors.length > 0) {
            alert(`${uploadedCount} image(s) uploadée(s) avec succès. ${errors.length} erreur(s).`);
        } else if (uploadedCount > 0) {
            // Message de succès silencieux (optionnel)
        }
    }
    
    // Aperçu des nouvelles images sélectionnées (depuis le formulaire)
    document.getElementById('service_images')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        newImagesPreview = [];
        
        if (files.length > 0) {
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    newImagesPreview.push({
                        url: e.target.result,
                        file: file,
                        index: index
                    });
                    
                    // Mettre à jour l'affichage quand toutes les images sont chargées
                    if (newImagesPreview.length === files.length) {
                        updateImagesDisplay();
                    }
                };
                reader.readAsDataURL(file);
            });
        } else {
            updateImagesDisplay();
        }
    });
    
    function setImageAsCover(serviceId, imageId, isCurrentlyCover) {
        if (isCurrentlyCover) {
            return; // Déjà couverture
        }
        
        const url = '{{ route("agenda.service.image.cover", ["slug" => $entreprise->slug, "typeServiceId" => ":serviceId", "imageId" => ":imageId"]) }}'
            .replace(':serviceId', serviceId)
            .replace(':imageId', imageId);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'état local
                currentServiceImages.forEach(img => {
                    img.est_couverture = (img.id === imageId);
                });
                updateImagesDisplay();
            } else {
                alert('Erreur lors de la mise à jour de l\'image de couverture.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la mise à jour de l\'image de couverture.');
        });
    }
    
    function deleteServiceImage(serviceId, imageId) {
        if (!confirm('Supprimer cette image ?')) return;
        
        const url = '{{ route("agenda.service.image.delete", ["slug" => $entreprise->slug, "typeServiceId" => ":serviceId", "imageId" => ":imageId"]) }}'
            .replace(':serviceId', serviceId)
            .replace(':imageId', imageId);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Retirer l'image de la liste locale
                currentServiceImages = currentServiceImages.filter(img => img.id !== imageId);
                
                // Si l'image supprimée était la couverture, mettre à jour le statut des autres
                // Le serveur définit automatiquement la première image restante comme couverture
                if (currentServiceImages.length > 0) {
                    // Mettre la première image comme couverture (le serveur l'a déjà fait)
                    currentServiceImages.forEach((img, index) => {
                        img.est_couverture = index === 0;
                    });
                }
                
                updateImagesDisplay();
            } else {
                alert('Erreur lors de la suppression de l\'image.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'image.');
        });
    }

    // Protection contre double soumission
    const serviceForm = document.getElementById('service-form');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerText;
                submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Enregistrement...';
            }
        });
    }
</script>
