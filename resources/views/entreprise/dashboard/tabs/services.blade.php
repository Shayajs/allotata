<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Services</h2>

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
            <button 
                onclick="openServiceModal()"
                class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
            >
                + Ajouter
            </button>
        </div>

        @if($typesServices && $typesServices->count() > 0)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($typesServices as $service)
                    <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow {{ $service->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $service->nom }}</h4>
                                @if($service->images->count() > 0)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">📷 {{ $service->images->count() }} image(s)</span>
                                @endif
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $service->est_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        @if($service->description)
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ $service->description }}</p>
                        @endif
                        <div class="flex items-center gap-4 text-sm mb-4">
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->duree_minutes }} min
                            </span>
                            <span class="flex items-center gap-1 font-bold text-green-600 dark:text-green-400">
                                {{ number_format($service->prix, 0, ',', ' ') }} €
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                onclick="editServiceFromButton(this)"
                                data-service-id="{{ $service->id }}"
                                data-service-nom="{{ addslashes($service->nom) }}"
                                data-service-description="{{ addslashes($service->description ?? '') }}"
                                data-service-duree="{{ $service->duree_minutes }}"
                                data-service-prix="{{ $service->prix }}"
                                data-service-actif="{{ $service->est_actif ? 'true' : 'false' }}"
                                data-service-images="{{ base64_encode(json_encode($service->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values())) }}"
                                class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                            >
                                Modifier
                            </button>
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


<!-- Modal pour ajouter/modifier un service -->
<div id="modal-service" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modal-title">Ajouter un service</h3>
            <button onclick="document.getElementById('modal-service').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne gauche : Formulaire -->
            <div class="lg:col-span-2 overflow-y-auto pr-2">
                <form action="{{ route('agenda.service.store', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" id="service-form">
                    @csrf
                    <input type="hidden" name="type_service_id" id="type_service_id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom du service *</label>
                            <input 
                                type="text" 
                                name="nom" 
                                id="service_nom"
                                required
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                            <textarea 
                                name="description" 
                                id="service_description"
                                rows="3"
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
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
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
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
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
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
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400 transition-colors"
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
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('modal-service').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Colonne droite : Gestion des images -->
            <div class="lg:col-span-1 border-l border-slate-200 dark:border-slate-700 pl-6 overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-slate-800 pb-4 mb-4 border-b border-slate-200 dark:border-slate-700">
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

<script>
    let currentServiceId = null;
    let currentServiceImages = [];
    let newImagesPreview = [];
    
    function editServiceFromButton(button) {
        const serviceId = parseInt(button.getAttribute('data-service-id'));
        const nom = button.getAttribute('data-service-nom') || '';
        const description = button.getAttribute('data-service-description') || '';
        const duree = parseInt(button.getAttribute('data-service-duree')) || 30;
        const prix = parseFloat(button.getAttribute('data-service-prix')) || 0;
        const estActif = button.getAttribute('data-service-actif') === 'true';
        const imagesBase64 = button.getAttribute('data-service-images') || '';
        
        let images = [];
        try {
            // Décoder le base64 puis parser le JSON
            if (imagesBase64) {
                const imagesJson = atob(imagesBase64);
                images = JSON.parse(imagesJson);
            }
        } catch (e) {
            console.error('Erreur parsing images:', e);
            images = [];
        }
        
        editService(serviceId, nom, description, duree, prix, estActif, images);
    }
    
    function openServiceModal() {
        currentServiceId = null;
        currentServiceImages = [];
        newImagesPreview = [];
        
        document.getElementById('modal-service').classList.remove('hidden');
        document.getElementById('type_service_id').value = '';
        document.getElementById('service_nom').value = '';
        document.getElementById('service_description').value = '';
        document.getElementById('service_duree').value = '30';
        document.getElementById('service_prix').value = '25';
        document.getElementById('service_actif').checked = true;
        document.getElementById('service_images').value = '';
        document.getElementById('modal-title').textContent = 'Ajouter un service';
        
        updateImagesDisplay();
    }

    function editService(id, nom, description, duree, prix, estActif, images) {
        currentServiceId = id;
        currentServiceImages = images || [];
        
        document.getElementById('modal-service').classList.remove('hidden');
        document.getElementById('type_service_id').value = id;
        document.getElementById('service_nom').value = nom;
        document.getElementById('service_description').value = description || '';
        document.getElementById('service_duree').value = duree;
        document.getElementById('service_prix').value = prix;
        document.getElementById('service_actif').checked = estActif;
        document.getElementById('service_images').value = '';
        document.getElementById('modal-title').textContent = 'Modifier le service';
        
        updateImagesDisplay();
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
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Retirer l'image de la liste locale
                currentServiceImages = currentServiceImages.filter(img => img.id !== imageId);
                updateImagesDisplay();
                
                // Recharger la page après un court délai pour synchroniser
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Erreur lors de la suppression de l\'image.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'image.');
        });
    }
</script>
