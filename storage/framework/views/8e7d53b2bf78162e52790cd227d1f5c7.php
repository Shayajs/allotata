
<!-- Modal Ajout/Modification Produit -->
<div id="modal-produit" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-produit-title" role="dialog" aria-modal="true">
    <!-- Overlay avec Blur -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-produit').classList.add('hidden')"></div>

    <!-- Conteneur Flex pour centrage -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4 text-center">
            <!-- Contenu de la modal -->
            <div class="modal-content relative w-full max-w-5xl transform overflow-hidden rounded-2xl text-left transition-all mx-auto" onclick="event.stopPropagation()">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <!-- Colonne gauche : Formulaire -->
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white" id="modal-produit-title">
                            Ajouter un produit
                        </h3>
                        <button type="button" onclick="document.getElementById('modal-produit').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="<?php echo e(route('stock.produit.store', $entreprise->slug)); ?>" method="POST" enctype="multipart/form-data" id="produit-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="produit_id" id="produit_id">
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom du produit *</label>
                                <input 
                                    type="text" 
                                    name="nom" 
                                    id="produit_nom"
                                    required
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    placeholder="Ex: T-shirt"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                                <textarea 
                                    name="description" 
                                    id="produit_description"
                                    rows="3"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                                <input 
                                    type="number" 
                                    name="prix" 
                                    id="produit_prix"
                                    required
                                    min="0"
                                    step="0.01"
                                    value="0"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Type de gestion *</label>
                                <select 
                                    name="gestion_stock" 
                                    id="produit_gestion_stock"
                                    onchange="toggleStockFields()"
                                    required
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                                    <option value="disponible_immediatement">Disponible immédiatement (gestion stock)</option>
                                    <option value="en_attente_commandes">En attente de commandes</option>
                                </select>
                            </div>
                            
                            <div id="stock-fields" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité disponible</label>
                                    <input 
                                        type="number" 
                                        name="quantite_disponible" 
                                        id="produit_quantite_disponible"
                                        min="0"
                                        value="0"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Quantité minimum (alerte)</label>
                                    <input 
                                        type="number" 
                                        name="quantite_minimum" 
                                        id="produit_quantite_minimum"
                                        min="0"
                                        value="0"
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                            </div>

                            <!-- Options de livraison/vente pour ce produit -->
                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Options de livraison/vente</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                                    Si non spécifié, les paramètres par défaut de l'entreprise seront utilisés
                                </p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="livraison_disponible" 
                                            id="produit_livraison_disponible"
                                            value="1"
                                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Livraison disponible pour ce produit</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="vente_sur_place_disponible" 
                                            id="produit_vente_sur_place_disponible"
                                            value="1"
                                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Vente sur place disponible pour ce produit</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Upload d'images (pour nouveau produit) -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Ajouter des images
                                </label>
                                <input 
                                    type="file" 
                                    name="images[]" 
                                    id="produit_images"
                                    multiple
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-700 dark:file:text-blue-400 transition-colors"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sélectionnez une ou plusieurs images à ajouter</p>
                            </div>
                            
                            <label class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="est_actif" 
                                    id="produit_est_actif"
                                    value="1"
                                    checked
                                    class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Produit actif</span>
                            </label>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button" onclick="document.getElementById('modal-produit').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                                Annuler
                            </button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Colonne droite : Gestion des images -->
                <div class="lg:col-span-1 border-l border-slate-200 dark:border-slate-700 p-8 overflow-y-auto max-h-[80vh]">
                    <div class="sticky top-0 bg-white dark:bg-slate-800 pb-4 mb-4 border-b border-slate-200 dark:border-slate-700 z-10">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Images du produit</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cliquez sur une image pour la définir comme couverture</p>
                    </div>
                    
                    <!-- Zone d'upload d'images -->
                    <div id="upload-zone-produit" class="mb-6">
                        <label for="image-upload-input-produit" id="upload-zone-label-produit" class="block w-full p-6 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all">
                            <input 
                                type="file" 
                                id="image-upload-input-produit"
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
                        <div id="upload-progress-produit" class="hidden mt-2">
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
                    <div id="existing-images-section-produit" class="hidden">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Images actuelles</p>
                        <div id="existing-images-list-produit" class="space-y-3"></div>
                    </div>
                    
                    <!-- Nouvelles images sélectionnées (pour nouveau produit) -->
                    <div id="new-images-section-produit" class="hidden">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Nouvelles images</p>
                        <div id="new-images-list-produit" class="space-y-3"></div>
                    </div>
                    
                    <!-- Message si aucune image -->
                    <div id="no-images-message-produit" class="text-center py-8 text-slate-400 dark:text-slate-500">
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
    let currentProduitId = null;
    let currentProduitImages = [];
    let newImagesPreviewProduit = [];
    
    function editProduitFromButton(button) {
        const produitId = parseInt(button.getAttribute('data-produit-id'));
        const nom = button.getAttribute('data-produit-nom') || '';
        const description = button.getAttribute('data-produit-description') || '';
        const prix = parseFloat(button.getAttribute('data-produit-prix')) || 0;
        const gestionStock = button.getAttribute('data-produit-gestion-stock') || 'disponible_immediatement';
        const quantiteDisponible = parseInt(button.getAttribute('data-produit-quantite-disponible')) || 0;
        const quantiteMinimum = parseInt(button.getAttribute('data-produit-quantite-minimum')) || 0;
        const estActif = button.getAttribute('data-produit-actif') === 'true';
        const livraisonDisponible = button.getAttribute('data-produit-livraison-disponible');
        const venteSurPlaceDisponible = button.getAttribute('data-produit-vente-sur-place-disponible');
        const imagesBase64 = button.getAttribute('data-produit-images') || '';
        
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
        
        // Convertir les valeurs de livraison/vente
        let livraison = null;
        if (livraisonDisponible && livraisonDisponible !== 'null') {
            livraison = livraisonDisponible === 'true';
        }
        let ventePlace = null;
        if (venteSurPlaceDisponible && venteSurPlaceDisponible !== 'null') {
            ventePlace = venteSurPlaceDisponible === 'true';
        }
        
        editProduit(produitId, nom, description, prix, gestionStock, quantiteDisponible, quantiteMinimum, estActif, images, livraison, ventePlace);
    }
    
    function openProduitModal() {
        currentProduitId = null;
        currentProduitImages = [];
        newImagesPreviewProduit = [];
        
        document.getElementById('modal-produit').classList.remove('hidden');
        document.getElementById('produit_id').value = '';
        document.getElementById('produit_nom').value = '';
        document.getElementById('produit_description').value = '';
        document.getElementById('produit_prix').value = '0';
        document.getElementById('produit_gestion_stock').value = 'disponible_immediatement';
        document.getElementById('produit_quantite_disponible').value = '0';
        document.getElementById('produit_quantite_minimum').value = '0';
        document.getElementById('produit_est_actif').checked = true;
        document.getElementById('produit_images').value = '';
        document.getElementById('modal-produit-title').textContent = 'Ajouter un produit';
        
        // Cacher la zone d'upload direct quand on crée un nouveau produit (car pas d'ID)
        document.getElementById('upload-zone-produit').classList.add('hidden');
        
        toggleStockFields();
        updateImagesDisplayProduit();
    }

    function editProduit(id, nom, description, prix, gestionStock, quantiteDisponible, quantiteMinimum, estActif, images, livraisonDisponible, venteSurPlaceDisponible) {
        currentProduitId = id;
        currentProduitImages = images || [];
        
        document.getElementById('modal-produit').classList.remove('hidden');
        document.getElementById('produit_id').value = id;
        document.getElementById('produit_nom').value = nom;
        document.getElementById('produit_description').value = description || '';
        document.getElementById('produit_prix').value = prix;
        document.getElementById('produit_gestion_stock').value = gestionStock;
        document.getElementById('produit_quantite_disponible').value = quantiteDisponible || 0;
        document.getElementById('produit_quantite_minimum').value = quantiteMinimum || 0;
        document.getElementById('produit_est_actif').checked = estActif;
        document.getElementById('produit_images').value = '';
        document.getElementById('modal-produit-title').textContent = 'Modifier le produit';
        
        // Options de livraison/vente
        if (livraisonDisponible !== undefined && livraisonDisponible !== null) {
            document.getElementById('produit_livraison_disponible').checked = livraisonDisponible === true || livraisonDisponible === 'true' || livraisonDisponible === 1;
        }
        if (venteSurPlaceDisponible !== undefined && venteSurPlaceDisponible !== null) {
            document.getElementById('produit_vente_sur_place_disponible').checked = venteSurPlaceDisponible === true || venteSurPlaceDisponible === 'true' || venteSurPlaceDisponible === 1;
        }
        
        // Afficher la zone d'upload direct
        document.getElementById('upload-zone-produit').classList.remove('hidden');
        
        toggleStockFields();
        updateImagesDisplayProduit();
    }
    
    function toggleStockFields() {
        const gestionStock = document.getElementById('produit_gestion_stock').value;
        const stockFields = document.getElementById('stock-fields');
        if (gestionStock === 'disponible_immediatement') {
            stockFields.style.display = 'grid';
            document.getElementById('produit_quantite_disponible').required = true;
        } else {
            stockFields.style.display = 'none';
            document.getElementById('produit_quantite_disponible').required = false;
        }
    }
    
    function updateImagesDisplayProduit() {
        const existingSection = document.getElementById('existing-images-section-produit');
        const existingList = document.getElementById('existing-images-list-produit');
        const newSection = document.getElementById('new-images-section-produit');
        const newList = document.getElementById('new-images-list-produit');
        const noImagesMessage = document.getElementById('no-images-message-produit');
        
        const hasExisting = currentProduitImages && currentProduitImages.length > 0;
        const hasNew = newImagesPreviewProduit.length > 0;
        
        // Afficher/masquer les sections
        if (hasExisting) {
            existingSection.classList.remove('hidden');
            existingList.innerHTML = '';
            
            currentProduitImages.forEach((img) => {
                const div = document.createElement('div');
                div.className = 'relative group cursor-pointer';
                div.onclick = () => setImageAsCoverProduit(currentProduitId, img.id, img.est_couverture);
                
                const borderClass = img.est_couverture ? 'border-blue-500 ring-2 ring-blue-500' : 'border-slate-200 dark:border-slate-600';
                
                div.innerHTML = `
                    <div class="relative overflow-hidden rounded-lg border-2 ${borderClass} hover:border-blue-400 transition-all">
                        <img src="${String(img.path || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;')}" alt="Image" class="w-full h-32 object-cover">
                        ${img.est_couverture ? '<div class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold bg-blue-500 text-white rounded shadow-lg">⭐ Couverture</div>' : '<div class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold bg-slate-800/70 text-white rounded opacity-0 group-hover:opacity-100 transition">Cliquer pour définir comme couverture</div>'}
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
                    deleteProduitImage(currentProduitId, img.id);
                };
                
                existingList.appendChild(div);
            });
        } else {
            existingSection.classList.add('hidden');
        }
        
        if (hasNew) {
            newSection.classList.remove('hidden');
            newList.innerHTML = '';
            
            newImagesPreviewProduit.forEach((preview, index) => {
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
    function handleImageFilesProduit(files) {
        if (files.length === 0) return;
        
        // Si on a un produit existant, uploader immédiatement
        if (currentProduitId) {
            uploadImagesImmediatelyProduit(files);
        } else {
            // Pour un nouveau produit, ajouter les fichiers au champ images[] du formulaire
            const formImagesInput = document.getElementById('produit_images');
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
                    newImagesPreviewProduit.push({
                        url: e.target.result,
                        file: file,
                        index: newImagesPreviewProduit.length
                    });
                    updateImagesDisplayProduit();
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
    document.getElementById('image-upload-input-produit')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        handleImageFilesProduit(files);
        e.target.value = '';
    });
    
    // Gestion du drag & drop
    const uploadZoneLabelProduit = document.getElementById('upload-zone-label-produit');
    if (uploadZoneLabelProduit) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZoneLabelProduit.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZoneLabelProduit.addEventListener(eventName, function() {
                uploadZoneLabelProduit.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadZoneLabelProduit.addEventListener(eventName, function() {
                uploadZoneLabelProduit.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            }, false);
        });
        
        uploadZoneLabelProduit.addEventListener('drop', function(e) {
            const files = Array.from(e.dataTransfer.files).filter(file => 
                file.type.startsWith('image/')
            );
            handleImageFilesProduit(files);
        }, false);
    }
    
    // Fonction pour uploader immédiatement les images (une par une)
    async function uploadImagesImmediatelyProduit(files) {
        const uploadProgress = document.getElementById('upload-progress-produit');
        uploadProgress.classList.remove('hidden');
        
        const url = '<?php echo e(route("stock.produit.image.upload", ["slug" => $entreprise->slug, "produitId" => ":produitId"])); ?>'
            .replace(':produitId', currentProduitId);
        
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
                    currentProduitImages.push({
                        id: data.image.id,
                        path: data.image.path,
                        est_couverture: data.image.est_couverture
                    });
                    
                    // Si c'est la première image, elle devient couverture
                    if (data.image.est_couverture) {
                        // Retirer le statut couverture des autres
                        currentProduitImages.forEach(img => {
                            if (img.id !== data.image.id) {
                                img.est_couverture = false;
                            }
                        });
                    }
                    
                    uploadedCount++;
                    updateImagesDisplayProduit();
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
    document.getElementById('produit_images')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        newImagesPreviewProduit = [];
        
        if (files.length > 0) {
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    newImagesPreviewProduit.push({
                        url: e.target.result,
                        file: file,
                        index: index
                    });
                    
                    // Mettre à jour l'affichage quand toutes les images sont chargées
                    if (newImagesPreviewProduit.length === files.length) {
                        updateImagesDisplayProduit();
                    }
                };
                reader.readAsDataURL(file);
            });
        } else {
            updateImagesDisplayProduit();
        }
    });
    
    function setImageAsCoverProduit(produitId, imageId, isCurrentlyCover) {
        if (isCurrentlyCover) {
            return; // Déjà couverture
        }
        
        const url = '<?php echo e(route("stock.produit.image.cover", ["slug" => $entreprise->slug, "produitId" => ":produitId", "imageId" => ":imageId"])); ?>'
            .replace(':produitId', produitId)
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
                currentProduitImages.forEach(img => {
                    img.est_couverture = (img.id === imageId);
                });
                updateImagesDisplayProduit();
            } else {
                alert('Erreur lors de la mise à jour de l\'image de couverture.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la mise à jour de l\'image de couverture.');
        });
    }
    
    function deleteProduitImage(produitId, imageId) {
        if (!confirm('Supprimer cette image ?')) return;
        
        const url = '<?php echo e(route("stock.produit.image.delete", ["slug" => $entreprise->slug, "produitId" => ":produitId", "imageId" => ":imageId"])); ?>'
            .replace(':produitId', produitId)
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
                currentProduitImages = currentProduitImages.filter(img => img.id !== imageId);
                
                // Si l'image supprimée était la couverture, mettre à jour le statut des autres
                // Le serveur définit automatiquement la première image restante comme couverture
                if (currentProduitImages.length > 0) {
                    // Mettre la première image comme couverture (le serveur l'a déjà fait)
                    currentProduitImages.forEach((img, index) => {
                        img.est_couverture = index === 0;
                    });
                }
                
                updateImagesDisplayProduit();
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
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/stock-modal-content.blade.php ENDPATH**/ ?>