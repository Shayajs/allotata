// Media Library Manager
class MediaLibrary {
    constructor() {
        this.currentFolder = '/';
        this.currentType = '';
        this.searchTerm = '';
        this.currentPage = 1;
        this.selectedFile = null;
        this.onSelectCallback = null;
        
        // Pour la création de dossiers et le déplacement
        this.folderSelectorMode = null; // 'create' ou 'move'
        this.selectedFolderPath = '/';
        this.filesToMove = [];
        this.createFolderParentFolder = '/';
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadFiles();
        this.loadFolderTree();
    }

    bindEvents() {
        // Recherche
        const searchInput = document.getElementById('media-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value;
                    this.currentPage = 1;
                    this.loadFiles();
                }, 300);
            });
        }

        // Modale de sélection
        const modal = document.getElementById('media-selector-modal');
        if (modal) {
            // Fermer la modale quand on clique en dehors
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeSelectorModal();
                }
            });
            
            // Boutons de fermeture
            document.getElementById('close-selector-modal')?.addEventListener('click', () => this.closeSelectorModal());
            document.getElementById('cancel-selector')?.addEventListener('click', () => this.closeSelectorModal());
            document.getElementById('confirm-selector')?.addEventListener('click', () => this.confirmSelection());
        }
        
        // Modale de détails
        const detailsModal = document.getElementById('file-details-modal');
        if (detailsModal) {
            detailsModal.addEventListener('click', (e) => {
                if (e.target === detailsModal) {
                    detailsModal.classList.add('hidden');
                }
            });
        }

        // Filtre par type
        const typeFilter = document.getElementById('media-type-filter');
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                this.currentType = e.target.value;
                this.currentPage = 1;
                this.loadFiles();
            });
        }

        // Upload de fichiers
        const fileUpload = document.getElementById('file-upload');
        if (fileUpload) {
            fileUpload.addEventListener('change', (e) => {
                this.uploadFiles(Array.from(e.target.files));
            });
        }

        // Glisser-déposer pour upload
        this.setupDragAndDrop();
        
        // Glisser-déposer pour déplacer des fichiers
        this.setupFileDragAndDrop();

        // La création de dossier se fait maintenant via openCreateFolderModal()

        // Recherche dans la modale
        const modalSearch = document.getElementById('modal-search');
        if (modalSearch) {
            let modalSearchTimeout;
            modalSearch.addEventListener('input', (e) => {
                clearTimeout(modalSearchTimeout);
                modalSearchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value;
                    this.loadFiles(true); // true = mode modale
                }, 300);
            });
        }
    }

    async loadFiles(isModal = false) {
        const loadingEl = document.getElementById('loading');
        const emptyEl = document.getElementById('empty-state');
        const gridEl = isModal ? document.getElementById('modal-media-grid') : document.getElementById('media-grid');

        if (loadingEl) loadingEl.classList.remove('hidden');
        if (emptyEl) emptyEl.classList.add('hidden');

        try {
            // Normaliser le dossier pour la requête (sans slash au début sauf si c'est la racine)
            const folderParam = this.currentFolder === '/' ? '/' : this.currentFolder;
            
            const params = new URLSearchParams({
                folder: folderParam,
                page: this.currentPage,
            });

            if (this.currentType) params.append('type', this.currentType);
            if (this.searchTerm) params.append('search', this.searchTerm);

            const url = `/admin/api/media/list?${params}`;
            console.log('Chargement des fichiers depuis:', url, 'Dossier:', this.currentFolder, 'Param folder:', folderParam);
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Fichiers reçus:', data.files?.length || 0, 'fichiers', 'Dossier courant serveur:', data.current_folder);
            
            // Vérifier si le dossier courant du serveur diffère de celui attendu
            if (data.current_folder && this.currentFolder && data.current_folder !== this.currentFolder && data.current_folder !== this.currentFolder.replace(/^\//, '')) {
                console.warn('Incohérence de dossier:', 'Attendu:', this.currentFolder, 'Reçu du serveur:', data.current_folder);
            }

            if (loadingEl) loadingEl.classList.add('hidden');

            if (data.files && data.files.length > 0) {
                console.log('Affichage de', data.files.length, 'fichiers');
                this.renderFiles(data.files, isModal);
                if (!isModal) {
                    this.renderPagination(data.pagination, isModal);
                }
            } else {
                console.log('Aucun fichier trouvé pour le dossier:', this.currentFolder);
                if (gridEl) gridEl.innerHTML = '';
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (!isModal) {
                    const paginationEl = document.getElementById('pagination');
                    if (paginationEl) paginationEl.innerHTML = '';
                }
            }

            // Mettre à jour le breadcrumb si pas en mode modale
            if (!isModal) {
                this.updateBreadcrumb(data.current_folder || this.currentFolder);
            } else {
                this.updateBreadcrumb(data.current_folder || this.currentFolder, true);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des fichiers:', error);
            if (loadingEl) loadingEl.classList.add('hidden');
            alert('Erreur lors du chargement des fichiers: ' + error.message);
        }
    }

    renderFiles(files, isModal = false) {
        const gridEl = isModal ? document.getElementById('modal-media-grid') : document.getElementById('media-grid');
        if (!gridEl) return;

        gridEl.innerHTML = files.map(file => this.renderFileCard(file, isModal)).join('');
        
        // Réattacher les événements après le rendu
        if (isModal) {
            gridEl.querySelectorAll('.media-card').forEach(card => {
                const fileId = card.getAttribute('data-file-id');
                card.addEventListener('click', () => this.selectFile(parseInt(fileId)));
            });
            } else {
                gridEl.querySelectorAll('.media-card').forEach(card => {
                    const fileId = card.getAttribute('data-file-id');
                    if (this.isMultiSelectMode) {
                        card.addEventListener('click', (e) => {
                            if (!e.target.closest('button')) {
                                this.toggleFileSelection(parseInt(fileId), e);
                            }
                        });
                    } else {
                        card.addEventListener('click', () => this.showFileDetails(parseInt(fileId)));
                    }
                });
            }
            
            // Mettre à jour l'UI de sélection multiple
            if (!isModal) {
                this.updateMultiSelectUI();
            }
    }

    renderFileCard(file, isModal = false) {
        const url = `/media/${file.path}`;
        const isSelected = this.selectedFile?.id === file.id;
        const isMultiSelected = this.selectedFiles.has(file.id);
        const dragClass = !isModal ? 'draggable-file' : '';

        return `
            <div class="media-card group bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-3 hover:border-green-500 dark:hover:border-green-500 transition cursor-pointer ${isSelected && isModal ? 'ring-2 ring-green-500' : ''} ${isMultiSelected && !isModal ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20' : ''} ${dragClass}" 
                 data-file-id="${file.id}"
                 draggable="${!isModal ? 'true' : 'false'}"
                 ondragstart="mediaLibrary.draggedFileId = ${file.id}; event.currentTarget.classList.add('opacity-50');"
                 ondragend="mediaLibrary.draggedFileId = null; event.currentTarget.classList.remove('opacity-50');">
                <div class="relative aspect-square mb-2 bg-slate-100 dark:bg-slate-700 rounded overflow-hidden">
                    ${this.renderFilePreview(file, url)}
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition flex items-center justify-center">
                        <button class="opacity-0 group-hover:opacity-100 text-white p-2 hover:bg-white hover:bg-opacity-20 rounded transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-900 dark:text-white truncate" title="${this.escapeHtml(file.name)}">
                            ${this.escapeHtml(file.name)}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            ${this.formatFileSize(file.size)}
                        </p>
                    </div>
                    ${!isModal ? `
                        <div class="opacity-0 group-hover:opacity-100 transition relative flex items-center gap-1">
                            ${this.isMultiSelectMode ? `
                                <button onclick="event.stopPropagation(); mediaLibrary.toggleFileSelection(${file.id}, event)" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded">
                                    ${isMultiSelected ? `
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                    ` : `
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    `}
                                </button>
                            ` : ''}
                            <button onclick="event.stopPropagation(); mediaLibrary.showFileContextMenu(${file.id}, event)" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    renderFilePreview(file, url) {
        if (file.type === 'image') {
            return `<img src="${url}" alt="${this.escapeHtml(file.name)}" class="w-full h-full object-cover">`;
        } else if (file.type === 'video') {
            // Utiliser la miniature si elle existe
            const thumbnailUrl = file.thumbnail_path ? `/media/${file.thumbnail_path}` : null;
            if (thumbnailUrl) {
                return `
                    <img src="${thumbnailUrl}" alt="Miniature" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                `;
            } else {
                return `
                    <video class="w-full h-full object-cover" preload="metadata">
                        <source src="${url}" type="${file.mime_type}">
                    </video>
                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                        <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                `;
            }
        } else if (file.type === 'audio') {
            // Utiliser la miniature si elle existe
            const thumbnailUrl = file.thumbnail_path ? `/media/${file.thumbnail_path}` : null;
            if (thumbnailUrl) {
                return `
                    <img src="${thumbnailUrl}" alt="Miniature" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                `;
            } else {
                const icon = this.getFileIcon(file.type, file.mime_type);
                return `
                    <div class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100 dark:bg-slate-700">
                        ${icon}
                    </div>
                `;
            }
        } else {
            const icon = this.getFileIcon(file.type, file.mime_type);
            return `
                <div class="w-full h-full flex items-center justify-center text-slate-400">
                    ${icon}
                </div>
            `;
        }
    }

    getFileIcon(type, mimeType) {
        const icons = {
            image: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
            video: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>`,
            audio: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>`,
            pdf: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`,
            text: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
            document: `<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
        };

        return icons[type] || icons.document;
    }

    async loadFolderTree() {
        try {
            const response = await fetch(`/admin/api/media/list?folder=${this.currentFolder}`);
            const data = await response.json();

            const treeEl = document.getElementById('folder-tree');
            if (treeEl) {
                treeEl.innerHTML = this.renderFolderTree(data.folder_tree || [], '');
            }

            const modalTreeEl = document.getElementById('modal-folder-tree');
            if (modalTreeEl) {
                modalTreeEl.innerHTML = this.renderFolderTree(data.folder_tree || [], '', true);
            }
        } catch (error) {
            console.error('Erreur lors du chargement de l\'arborescence:', error);
        }
    }

    renderFolderTree(tree, parentPath = '', isModal = false) {
        const allItems = [];

        // Lien vers la racine
        if (parentPath === '') {
            const isActive = this.currentFolder === '/';
            allItems.push(`
                <button 
                    onclick="mediaLibrary.navigateToFolder('/', ${isModal})"
                    class="w-full text-left px-3 py-2 rounded-lg transition flex items-center gap-2 text-sm ${isActive ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Racine
                </button>
            `);
        }

        // Parcourir l'arborescence
        tree.forEach(folder => {
            const folderPath = parentPath ? `${parentPath}/${folder.name}` : folder.name;
            const isActive = this.currentFolder === folderPath || this.currentFolder === `/${folderPath}`;

            allItems.push(`
                <div class="folder-item" data-folder-path="${folderPath}" ondrop="mediaLibrary.handleFolderDrop(event, '${folderPath}')" ondragover="event.preventDefault(); event.stopPropagation(); event.currentTarget.classList.add('bg-green-100', 'dark:bg-green-900/30');" ondragleave="event.currentTarget.classList.remove('bg-green-100', 'dark:bg-green-900/30');">
                    <button 
                        onclick="mediaLibrary.navigateToFolder('${folderPath}', ${isModal})"
                        class="w-full text-left px-3 py-2 rounded-lg transition flex items-center gap-2 text-sm ${isActive ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        ${this.escapeHtml(folder.name)}
                    </button>
                    ${folder.children && folder.children.length > 0 ? `
                        <div class="ml-4 mt-1">
                            ${this.renderFolderTree(folder.children, folderPath, isModal)}
                        </div>
                    ` : ''}
                </div>
            `);
        });

        return allItems.join('');
    }

    navigateToFolder(folder, isModal = false) {
        this.currentFolder = folder === '/' ? '/' : `/${folder}`;
        this.currentPage = 1;
        this.loadFiles(isModal);
        if (!isModal) {
            this.loadFolderTree();
        }
    }

    updateBreadcrumb(folder, isModal = false) {
        const breadcrumbEl = isModal ? document.getElementById('modal-breadcrumb') : document.getElementById('breadcrumb');
        if (!breadcrumbEl) return;

        const parts = folder === '/' ? [] : folder.split('/').filter(p => p);
        const items = ['<a href="#" onclick="mediaLibrary.navigateToFolder(\'/\', ' + isModal + '); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">Racine</a>'];

        let currentPath = '';
        parts.forEach((part, index) => {
            currentPath += '/' + part;
            const isLast = index === parts.length - 1;
            items.push(`
                <span class="text-slate-400 dark:text-slate-500">/</span>
                ${isLast 
                    ? `<span class="text-slate-900 dark:text-white font-medium">${this.escapeHtml(part)}</span>`
                    : `<a href="#" onclick="mediaLibrary.navigateToFolder('${currentPath.replace(/^\//, '')}', ${isModal}); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">${this.escapeHtml(part)}</a>`
                }
            `);
        });

        breadcrumbEl.innerHTML = items.join('');
    }

    async uploadFiles(files) {
        if (files.length === 0) return;

        const progressEl = document.getElementById('upload-progress');
        const progressBar = document.getElementById('upload-progress-bar');
        const progressPercent = document.getElementById('upload-percent');
        const statusEl = document.getElementById('upload-status');

        if (progressEl) {
            progressEl.classList.remove('hidden');
            progressBar.style.width = '0%';
            if (progressPercent) progressPercent.textContent = '0%';
            if (statusEl) statusEl.textContent = `Préparation de l'upload de ${files.length} fichier${files.length > 1 ? 's' : ''}...`;
        }

        let uploaded = 0;
        let errors = 0;

        // Sauvegarder le dossier courant avant l'upload pour le rechargement
        const uploadFolder = this.currentFolder;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('folder', uploadFolder);

            try {
                if (statusEl) statusEl.textContent = `Upload de ${file.name}... (${i + 1}/${files.length})`;

                const xhr = new XMLHttpRequest();

                // Suivi de la progression
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const fileProgress = (e.loaded / e.total) * 100;
                        const totalProgress = ((uploaded + (fileProgress / 100)) / files.length) * 100;
                        if (progressBar) {
                            progressBar.style.width = totalProgress + '%';
                        }
                        if (progressPercent) {
                            progressPercent.textContent = Math.round(totalProgress) + '%';
                        }
                    }
                });

                // Gérer la réponse
                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                uploaded++;
                                console.log('Upload réussi:', data.file);
                                console.log('Fichier uploadé dans le dossier:', data.file?.folder_path ?? 'racine', 'URL:', data.url);
                                // Vérifier que le dossier du fichier correspond au dossier upload
                                if (data.file?.folder_path && uploadFolder !== '/' && uploadFolder !== '/' + data.file.folder_path) {
                                    console.warn('Incohérence de dossier:', 'Attendu:', uploadFolder, 'Reçu:', data.file.folder_path);
                                }
                            } else {
                                errors++;
                                const errorMsg = data.error || data.message || 'Erreur inconnue';
                                console.error('Erreur upload:', errorMsg);
                                // Afficher l'erreur à l'utilisateur
                                if (statusEl) {
                                    statusEl.textContent = `Erreur: ${errorMsg}`;
                                    statusEl.style.color = '#ef4444';
                                }
                            }
                        } catch (e) {
                            errors++;
                            console.error('Erreur parsing JSON:', e, xhr.responseText);
                            if (statusEl) {
                                statusEl.textContent = 'Erreur: réponse invalide du serveur';
                                statusEl.style.color = '#ef4444';
                            }
                        }
                    } else {
                        errors++;
                        let errorMsg = xhr.statusText;
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            errorMsg = errorData.error || errorData.message || errorMsg;
                        } catch (e) {
                            // Ignorer si ce n'est pas du JSON
                        }
                        console.error('Erreur upload HTTP:', xhr.status, errorMsg, xhr.responseText);
                        // Afficher l'erreur à l'utilisateur
                        if (statusEl) {
                            statusEl.textContent = `Erreur ${xhr.status}: ${errorMsg}`;
                            statusEl.style.color = '#ef4444';
                        }
                    }

                    // Vérifier si tous les fichiers sont terminés
                    if (uploaded + errors === files.length) {
                        if (progressEl) {
                            setTimeout(() => {
                                progressEl.classList.add('hidden');
                            }, 2000);
                        }
                        if (statusEl) {
                            statusEl.textContent = `Upload terminé: ${uploaded} succès, ${errors} erreur${errors > 1 ? 's' : ''}`;
                        }
                        // Attendre un peu que le serveur finalise l'écriture du fichier
                        // puis recharger les fichiers et l'arborescence
                        setTimeout(() => {
                            console.log('Rechargement des fichiers après upload...', 'Dossier upload:', uploadFolder, 'Dossier courant:', this.currentFolder);
                            // S'assurer qu'on est dans le bon dossier (celui où on a uploadé)
                            if (this.currentFolder !== uploadFolder) {
                                console.log('Changement de dossier:', this.currentFolder, '->', uploadFolder);
                                this.currentFolder = uploadFolder;
                                this.updateBreadcrumb(uploadFolder);
                            }
                            // Forcer le rechargement de la page courante
                            this.currentPage = 1;
                            // Réinitialiser les filtres pour voir tous les fichiers uploadés
                            const typeFilter = document.getElementById('media-type-filter');
                            if (typeFilter) typeFilter.value = '';
                            this.currentType = '';
                            // Réinitialiser la recherche pour voir tous les fichiers
                            const searchInput = document.getElementById('media-search');
                            if (searchInput) {
                                this.searchTerm = '';
                                searchInput.value = '';
                            }
                            // Recharger les fichiers
                            console.log('Rechargement forcé après upload dans le dossier:', this.currentFolder);
                            this.loadFiles();
                            this.loadFolderTree();
                        }, 800); // Augmenter le délai pour laisser le temps au serveur de finaliser
                    }
                });

                xhr.addEventListener('error', () => {
                    errors++;
                    console.error('Erreur upload:', file.name);
                    if (uploaded + errors === files.length) {
                        if (progressEl) {
                            setTimeout(() => {
                                progressEl.classList.add('hidden');
                            }, 2000);
                        }
                        this.loadFiles();
                        this.loadFolderTree();
                    }
                });

                xhr.open('POST', '/admin/api/media/upload');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
                xhr.send(formData);
            } catch (error) {
                errors++;
                console.error('Erreur upload:', error);
            }
        }

        // Réinitialiser l'input
        const fileUpload = document.getElementById('file-upload');
        if (fileUpload) fileUpload.value = '';
    }

    setupDragAndDrop() {
        const gridEl = document.getElementById('media-grid');
        const dropZone = document.getElementById('drop-zone');
        
        if (!gridEl) return;

        const preventDefaults = (e) => {
            e.preventDefault();
            e.stopPropagation();
        };

        // Empêcher le comportement par défaut du navigateur
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            gridEl.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Afficher la zone de drop
        ['dragenter', 'dragover'].forEach(eventName => {
            gridEl.addEventListener(eventName, () => {
                if (dropZone) dropZone.classList.remove('hidden');
            }, false);
        });

        // Cacher la zone de drop
        ['dragleave', 'drop'].forEach(eventName => {
            gridEl.addEventListener(eventName, () => {
                if (dropZone) dropZone.classList.add('hidden');
            }, false);
        });

        // Gérer le drop
        gridEl.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            if (files.length > 0) {
                this.uploadFiles(files);
            }
        }, false);
    }

    setupFileDragAndDrop() {
        // Les fichiers sont draggables via l'attribut draggable dans renderFileCard
        // Les dossiers acceptent le drop via les attributs ondrop dans renderFolderTree
        
        // Gérer le drop sur la zone racine
        const folderTree = document.getElementById('folder-tree');
        if (folderTree) {
            folderTree.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.draggedFileId) {
                    this.moveFileToFolder(this.draggedFileId, '/');
                }
            }, false);
        }
    }

    async moveFileToFolder(fileId, folderPath) {
        try {
            const response = await fetch(`/admin/api/media/${fileId}/move`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ folder: folderPath }),
            });

            const data = await response.json();
            if (data.success) {
                this.loadFiles();
                this.loadFolderTree();
            } else {
                alert('Erreur lors du déplacement: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur déplacement:', error);
            alert('Erreur lors du déplacement');
        }
    }

    async showFileDetails(fileId) {
        try {
            const response = await fetch(`/admin/api/media/${fileId}`);
            const data = await response.json();

            if (data.success) {
                const file = data.file;
                const url = `/media/${file.path}`;
                const thumbnailUrl = data.thumbnail_url || null;
                const modalEl = document.getElementById('file-details-modal');
                const contentEl = document.getElementById('file-details-content');

                if (contentEl) {
                    // Déterminer le type d'affichage selon le type de fichier
                    let previewHtml = '';
                    if (file.type === 'image') {
                        previewHtml = `<img src="${url}" alt="${this.escapeHtml(file.name)}" class="max-w-full max-h-full object-contain">`;
                    } else if (file.type === 'video') {
                        // Afficher la miniature si elle existe, sinon une icône
                        if (thumbnailUrl) {
                            previewHtml = `
                                <div class="relative w-full h-full">
                                    <img src="${thumbnailUrl}" alt="Miniature" class="w-full h-full object-cover rounded-lg">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                                        <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            `;
                        } else {
                            previewHtml = `
                                <div class="text-slate-400 relative">
                                    ${this.getFileIcon(file.type, file.mime_type)}
                                    <div class="absolute bottom-2 left-2 right-2 text-xs text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800 px-2 py-1 rounded">
                                        Pas de miniature
                                    </div>
                                </div>
                            `;
                        }
                    } else if (file.type === 'audio') {
                        // Afficher la miniature si elle existe, sinon une icône
                        if (thumbnailUrl) {
                            previewHtml = `<img src="${thumbnailUrl}" alt="Miniature" class="w-full h-full object-cover rounded-lg">`;
                        } else {
                            previewHtml = `
                                <div class="text-slate-400 relative">
                                    ${this.getFileIcon(file.type, file.mime_type)}
                                    <div class="absolute bottom-2 left-2 right-2 text-xs text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800 px-2 py-1 rounded">
                                        Pas de miniature
                                    </div>
                                </div>
                            `;
                        }
                    } else {
                        previewHtml = `<div class="text-slate-400">${this.getFileIcon(file.type, file.mime_type)}</div>`;
                    }

                    // Section miniature pour vidéos et audio
                    let thumbnailSection = '';
                    if (file.type === 'video' || file.type === 'audio') {
                        thumbnailSection = `
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Miniature
                                </label>
                                <div class="space-y-3">
                                    ${thumbnailUrl ? `
                                        <div class="relative inline-block">
                                            <img src="${thumbnailUrl}" alt="Miniature actuelle" class="w-32 h-32 object-cover rounded-lg border border-slate-300 dark:border-slate-600">
                                            <button onclick="mediaLibrary.deleteThumbnail(${file.id})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition" title="Supprimer la miniature">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    ` : ''}
                                    <div>
                                        <label for="thumbnail-upload-${file.id}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            ${thumbnailUrl ? 'Remplacer la miniature' : 'Ajouter une miniature'}
                                        </label>
                                        <input type="file" id="thumbnail-upload-${file.id}" accept="image/*" class="hidden">
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Formats acceptés : JPG, PNG, GIF, WebP (max 5MB). La miniature sera automatiquement redimensionnée.
                                    </p>
                                </div>
                            </div>
                        `;
                    }

                    contentEl.innerHTML = `
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">${this.escapeHtml(file.name)}</h3>
                            <button onclick="document.getElementById('file-details-modal').classList.add('hidden')" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="aspect-video bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden flex items-center justify-center">
                                ${previewHtml}
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-slate-600 dark:text-slate-400">URL</label>
                                    <input type="text" value="${url}" readonly class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm" onclick="this.select()">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-slate-600 dark:text-slate-400">Taille</label>
                                    <input type="text" value="${this.formatFileSize(file.size)}" readonly class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                </div>
                            </div>
                            ${thumbnailSection}
                            <div class="flex gap-3">
                                <button onclick="mediaLibrary.renameFile(${file.id})" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                                    Renommer
                                </button>
                                <button onclick="mediaLibrary.openMoveFilesModal([${file.id}])" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                                    Déplacer
                                </button>
                                <button onclick="mediaLibrary.deleteFile(${file.id})" class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    `;
                }

                if (modalEl) {
                    modalEl.classList.remove('hidden');
                    modalEl.classList.add('flex');
                    // Réattacher les événements pour les nouveaux éléments
                    this.attachThumbnailEvents(file.id);
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails');
        }
    }

    renameFile(fileId) {
        const newName = prompt('Nouveau nom:');
        if (!newName) return;

        fetch(`/admin/api/media/${fileId}/rename`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ name: newName }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadFiles();
                document.getElementById('file-details-modal').classList.add('hidden');
            } else {
                alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        });
    }

    moveFile(fileId) {
        const newFolder = prompt('Nouveau dossier (laissez vide pour la racine):');
        const folder = newFolder || '/';

        fetch(`/admin/api/media/${fileId}/move`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ folder: folder }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadFiles();
                this.loadFolderTree();
                document.getElementById('file-details-modal').classList.add('hidden');
            } else {
                alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        });
    }

    deleteFile(fileId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) return;

        fetch(`/admin/api/media/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadFiles();
                document.getElementById('file-details-modal').classList.add('hidden');
            } else {
                alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        });
    }

    async uploadThumbnail(fileId, file) {
        if (!file) return;

        // Vérifier que c'est une image
        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner une image');
            return;
        }

        // Vérifier la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('L\'image est trop volumineuse (max 5MB)');
            return;
        }

        const formData = new FormData();
        formData.append('thumbnail', file);

        try {
            const response = await fetch(`/admin/api/media/${fileId}/thumbnail`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();
            if (data.success) {
                // Recharger les détails du fichier pour mettre à jour l'affichage
                await this.showFileDetails(fileId);
                // Recharger la liste des fichiers pour mettre à jour les miniatures dans la grille
                this.loadFiles();
            } else {
                alert('Erreur lors de l\'upload de la miniature: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur upload thumbnail:', error);
            alert('Erreur lors de l\'upload de la miniature');
        }
    }

    async deleteThumbnail(fileId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer la miniature ?')) return;

        try {
            const response = await fetch(`/admin/api/media/${fileId}/thumbnail`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();
            if (data.success) {
                // Recharger les détails du fichier pour mettre à jour l'affichage
                await this.showFileDetails(fileId);
                // Recharger la liste des fichiers pour mettre à jour les miniatures dans la grille
                this.loadFiles();
            } else {
                alert('Erreur lors de la suppression de la miniature: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur delete thumbnail:', error);
            alert('Erreur lors de la suppression de la miniature');
        }
    }

    // Fonction pour ouvrir la modale de sélection (réutilisable)
    openSelector(callback, acceptedTypes = null) {
        this.onSelectCallback = callback;
        this.selectedFile = null;
        this.acceptedTypes = acceptedTypes;
        this.currentFolder = '/';
        this.searchTerm = '';
        
        // Réinitialiser le champ de recherche de la modale
        const modalSearch = document.getElementById('modal-search');
        if (modalSearch) {
            modalSearch.value = '';
        }
        
        const modal = document.getElementById('media-selector-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Filtrer par type si spécifié
            if (acceptedTypes && acceptedTypes.length > 0) {
                this.currentType = acceptedTypes[0]; // Pour l'instant, on prend le premier type
            } else {
                this.currentType = '';
            }
            this.loadFiles(true);
            this.loadFolderTree();
        }
    }

    closeSelectorModal() {
        const modal = document.getElementById('media-selector-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        this.selectedFile = null;
        this.onSelectCallback = null;
        this.acceptedTypes = null;
        this.currentType = '';
        // Réinitialiser le champ de recherche
        const modalSearch = document.getElementById('modal-search');
        if (modalSearch) {
            modalSearch.value = '';
            this.searchTerm = '';
        }
    }

    selectFile(fileId) {
        this.selectedFile = { id: fileId };
        // Mettre à jour l'affichage pour montrer la sélection
        document.querySelectorAll('.media-card').forEach(card => {
            card.classList.remove('ring-2', 'ring-green-500');
        });
        const selectedCard = document.querySelector(`[data-file-id="${fileId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('ring-2', 'ring-green-500');
        }
    }

    confirmSelection() {
        if (!this.selectedFile) {
            alert('Veuillez sélectionner un fichier');
            return;
        }

        // Charger les détails du fichier et appeler le callback
        fetch(`/admin/api/media/${this.selectedFile.id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && this.onSelectCallback) {
                    this.onSelectCallback(data.file, data.url);
                    this.closeSelectorModal();
                }
            });
    }

    renderPagination(pagination, isModal = false) {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl || !pagination || pagination.last_page <= 1) {
            if (paginationEl) paginationEl.innerHTML = '';
            return;
        }

        const currentPage = pagination.current_page;
        const lastPage = pagination.last_page;
        const items = [];

        // Bouton Précédent
        items.push(`
            <button 
                onclick="mediaLibrary.goToPage(${currentPage - 1}, ${isModal})"
                ${currentPage === 1 ? 'disabled' : ''}
                class="px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-l-lg hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Précédent
            </button>
        `);

        // Numéros de page
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(lastPage, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            items.push(`
                <button 
                    onclick="mediaLibrary.goToPage(1, ${isModal})"
                    class="px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700"
                >
                    1
                </button>
            `);
            if (startPage > 2) {
                items.push(`<span class="px-3 py-2 text-slate-500">...</span>`);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            items.push(`
                <button 
                    onclick="mediaLibrary.goToPage(${i}, ${isModal})"
                    ${i === currentPage ? 'class="px-3 py-2 text-sm font-medium text-white bg-green-600 border border-green-600"' : 'class="px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700"'}
                >
                    ${i}
                </button>
            `);
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                items.push(`<span class="px-3 py-2 text-slate-500">...</span>`);
            }
            items.push(`
                <button 
                    onclick="mediaLibrary.goToPage(${lastPage}, ${isModal})"
                    class="px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700"
                >
                    ${lastPage}
                </button>
            `);
        }

        // Bouton Suivant
        items.push(`
            <button 
                onclick="mediaLibrary.goToPage(${currentPage + 1}, ${isModal})"
                ${currentPage === lastPage ? 'disabled' : ''}
                class="px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-r-lg hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Suivant
            </button>
        `);

        paginationEl.innerHTML = `
            <div class="flex items-center justify-center gap-1">
                ${items.join('')}
            </div>
            <div class="text-center mt-2 text-sm text-slate-600 dark:text-slate-400">
                Page ${currentPage} sur ${lastPage} (${pagination.total} fichier${pagination.total > 1 ? 's' : ''})
            </div>
        `;
    }

    goToPage(page, isModal = false) {
        if (page < 1) return;
        this.currentPage = page;
        this.loadFiles(isModal);
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    attachThumbnailEvents(fileId) {
        // Attacher l'événement pour l'upload de miniature
        const thumbnailInput = document.getElementById(`thumbnail-upload-${fileId}`);
        if (thumbnailInput) {
            thumbnailInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    this.uploadThumbnail(fileId, e.target.files[0]);
                }
            });
        }
    }

    contextMenuFileId = null;

    showFileContextMenu(fileId, event) {
        event.stopPropagation();
        this.contextMenuFileId = fileId;

        const menu = document.getElementById('file-context-menu');
        if (!menu) return;

        // Fermer tous les menus existants
        this.hideContextMenu();

        // Positionner le menu
        menu.style.left = event.pageX + 'px';
        menu.style.top = event.pageY + 'px';
        menu.classList.remove('hidden');

        // Fermer le menu quand on clique ailleurs
        setTimeout(() => {
            document.addEventListener('click', this.hideContextMenuBound = () => {
                this.hideContextMenu();
                document.removeEventListener('click', this.hideContextMenuBound);
            });
        }, 10);
    }

    hideContextMenu() {
        const menu = document.getElementById('file-context-menu');
        if (menu) menu.classList.add('hidden');
    }

    async contextMenuAction(action) {
        this.hideContextMenu();
        
        if (!this.contextMenuFileId) return;

        const fileId = this.contextMenuFileId;
        this.contextMenuFileId = null;

        switch(action) {
            case 'view':
                await this.showFileDetails(fileId);
                break;
            case 'rename':
                this.renameFile(fileId);
                break;
            case 'move':
                if (this.isMultiSelectMode && this.selectedFiles.size > 0) {
                    this.openMoveFilesModal(Array.from(this.selectedFiles));
                } else {
                    this.openMoveFilesModal([fileId]);
                }
                break;
            case 'copy-url':
                try {
                    const response = await fetch(`/admin/api/media/${fileId}`);
                    const data = await response.json();
                    if (data.success) {
                        const url = `/media/${data.file.path}`;
                        await navigator.clipboard.writeText(window.location.origin + url);
                        // Afficher une notification temporaire
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                        notification.textContent = 'URL copiée dans le presse-papiers';
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 2000);
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la copie de l\'URL');
                }
                break;
            case 'delete':
                if (this.isMultiSelectMode && this.selectedFiles.size > 0) {
                    const ids = Array.from(this.selectedFiles);
                    if (confirm(`Êtes-vous sûr de vouloir supprimer ${ids.length} fichier${ids.length > 1 ? 's' : ''} ?`)) {
                        ids.forEach(id => this.deleteFileWithoutReload(id));
                        this.selectedFiles.clear();
                        this.isMultiSelectMode = false;
                        this.loadFiles();
                        this.loadFolderTree();
                    }
                } else {
                    this.deleteFile(fileId);
                }
                break;
            case 'select-multiple':
                this.toggleMultiSelect();
                this.selectedFiles.add(fileId);
                this.loadFiles();
                break;
        }
    }

    deleteFileWithoutReload(fileId) {
        fetch(`/admin/api/media/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========== GESTION DU GLISSER-DÉPOSER POUR DÉPLACER DES FICHIERS ==========

    handleFolderDrop(event, folderPath) {
        event.preventDefault();
        event.stopPropagation();
        event.currentTarget.classList.remove('bg-green-100', 'dark:bg-green-900/30');
        
        if (this.draggedFileId) {
            this.moveFileToFolder(this.draggedFileId, folderPath === '/' ? '/' : '/' + folderPath);
        }
    }

    // ========== GESTION DE LA SÉLECTION MULTIPLE ==========

    toggleMultiSelect() {
        this.isMultiSelectMode = !this.isMultiSelectMode;
        if (!this.isMultiSelectMode) {
            this.selectedFiles.clear();
            this.updateMultiSelectUI();
        }
        this.loadFiles();
    }

    toggleFileSelection(fileId, event) {
        if (event) event.stopPropagation();
        
        if (this.selectedFiles.has(fileId)) {
            this.selectedFiles.delete(fileId);
        } else {
            this.selectedFiles.add(fileId);
        }
        
        this.updateMultiSelectUI();
        this.loadFiles();
    }

    updateMultiSelectUI() {
        const actionsEl = document.getElementById('multi-select-actions');
        const countEl = document.getElementById('selected-count');
        
        if (this.isMultiSelectMode && this.selectedFiles.size > 0) {
            if (actionsEl) actionsEl.classList.remove('hidden');
            if (countEl) countEl.textContent = `${this.selectedFiles.size} sélectionné${this.selectedFiles.size > 1 ? 's' : ''}`;
        } else {
            if (actionsEl) actionsEl.classList.add('hidden');
        }
    }

    moveSelectedFiles() {
        if (this.selectedFiles.size > 0) {
            this.openMoveFilesModal(Array.from(this.selectedFiles));
        }
    }

    deleteSelectedFiles() {
        if (this.selectedFiles.size === 0) return;
        
        if (confirm(`Êtes-vous sûr de vouloir supprimer ${this.selectedFiles.size} fichier${this.selectedFiles.size > 1 ? 's' : ''} ?`)) {
            const ids = Array.from(this.selectedFiles);
            let successCount = 0;
            let errorCount = 0;
            
            Promise.all(ids.map(id => 
                fetch(`/admin/api/media/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                }).then(r => r.json()).then(data => {
                    if (data.success) successCount++;
                    else errorCount++;
                }).catch(() => errorCount++)
            )).then(() => {
                this.selectedFiles.clear();
                this.isMultiSelectMode = false;
                this.updateMultiSelectUI();
                this.loadFiles();
                this.loadFolderTree();
                
                if (errorCount === 0) {
                    alert(`${successCount} fichier${successCount > 1 ? 's' : ''} supprimé${successCount > 1 ? 's' : ''} avec succès`);
                } else {
                    alert(`Suppression terminée : ${successCount} succès, ${errorCount} erreur${errorCount > 1 ? 's' : ''}`);
                }
            });
        }
    }

    // ========== GESTION DE LA CRÉATION DE DOSSIER ==========
    
    openCreateFolderModal() {
        this.createFolderParentFolder = this.currentFolder;
        this.updateCreateFolderLocation();
        
        const modal = document.getElementById('create-folder-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const input = document.getElementById('new-folder-name');
            if (input) {
                input.value = '';
                input.focus();
            }
        }
    }

    closeCreateFolderModal() {
        const modal = document.getElementById('create-folder-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    updateCreateFolderLocation() {
        const locationEl = document.getElementById('create-folder-path');
        if (locationEl) {
            locationEl.textContent = this.createFolderParentFolder === '/' ? 'Racine' : this.createFolderParentFolder.replace(/^\//, '');
        }
    }

    openFolderSelectorForCreate() {
        this.folderSelectorMode = 'create';
        this.selectedFolderPath = this.createFolderParentFolder;
        
        const modal = document.getElementById('folder-selector-modal');
        const title = document.getElementById('folder-selector-title');
        if (modal && title) {
            title.textContent = 'Sélectionner le dossier parent';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.loadFolderSelectorTree();
            this.updateFolderSelectorBreadcrumb(this.selectedFolderPath);
        }
    }

    closeFolderSelectorModal() {
        const modal = document.getElementById('folder-selector-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        this.folderSelectorMode = null;
    }

    confirmFolderSelection() {
        if (this.folderSelectorMode === 'create') {
            this.createFolderParentFolder = this.selectedFolderPath;
            this.updateCreateFolderLocation();
            this.closeFolderSelectorModal();
        }
    }

    async createFolder() {
        const input = document.getElementById('new-folder-name');
        if (!input) return;

        const folderName = input.value.trim();
        if (!folderName) {
            alert('Veuillez entrer un nom de dossier');
            return;
        }

        // Valider le nom du dossier
        if (!/^[a-zA-Z0-9_\-\s]+$/.test(folderName)) {
            alert('Le nom du dossier ne peut contenir que des lettres, chiffres, espaces, tirets et underscores.');
            return;
        }

        try {
            const response = await fetch('/admin/api/media/folders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    folder_name: folderName,
                    parent_folder: this.createFolderParentFolder,
                }),
            });

            const data = await response.json();
            if (data.success) {
                this.closeCreateFolderModal();
                // Naviguer vers le nouveau dossier
                this.currentFolder = data.folder_path ? '/' + data.folder_path : '/';
                this.loadFolderTree();
                this.loadFiles();
            } else {
                alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur création dossier:', error);
            alert('Erreur lors de la création du dossier');
        }
    }

    // ========== GESTION DU DÉPLACEMENT DE FICHIERS ==========

    openMoveFilesModal(fileIds) {
        this.filesToMove = Array.isArray(fileIds) ? fileIds : [fileIds];
        this.selectedFolderPath = '/';
        
        const modal = document.getElementById('move-files-modal');
        const countEl = document.getElementById('move-files-count');
        if (modal && countEl) {
            countEl.textContent = `${this.filesToMove.length} fichier${this.filesToMove.length > 1 ? 's' : ''} sélectionné${this.filesToMove.length > 1 ? 's' : ''}`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.loadMoveFilesTree();
            this.updateMoveFilesBreadcrumb('/');
            this.updateMoveFilesSelectedFolder();
        }
    }

    closeMoveFilesModal() {
        const modal = document.getElementById('move-files-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        this.filesToMove = [];
    }

    async confirmMoveFiles() {
        if (this.filesToMove.length === 0) return;

        let successCount = 0;
        let errorCount = 0;

        for (const fileId of this.filesToMove) {
            try {
                const response = await fetch(`/admin/api/media/${fileId}/move`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ folder: this.selectedFolderPath }),
                });

                const data = await response.json();
                if (data.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch (error) {
                errorCount++;
                console.error('Erreur déplacement:', error);
            }
        }

        this.closeMoveFilesModal();
        
        // Fermer aussi la modale de détails si elle est ouverte
        const detailsModal = document.getElementById('file-details-modal');
        if (detailsModal) detailsModal.classList.add('hidden');
        
        this.loadFiles();
        this.loadFolderTree();

        if (errorCount === 0) {
            alert(`Tous les fichiers ont été déplacés avec succès (${successCount})`);
        } else {
            alert(`Déplacement terminé : ${successCount} succès, ${errorCount} erreur${errorCount > 1 ? 's' : ''}`);
        }
    }

    // ========== GESTION DE L'ARBORESCENCE DANS LES MODALES ==========

    async loadFolderSelectorTree() {
        try {
            const response = await fetch(`/admin/api/media/list?folder=/`);
            const data = await response.json();

            const treeEl = document.getElementById('folder-selector-tree');
            if (treeEl) {
                treeEl.innerHTML = this.renderFolderSelectorTree(data.folder_tree || [], '');
            }
        } catch (error) {
            console.error('Erreur chargement arborescence:', error);
        }
    }

    async loadMoveFilesTree() {
        try {
            const response = await fetch(`/admin/api/media/list?folder=/`);
            const data = await response.json();

            const treeEl = document.getElementById('move-files-tree');
            if (treeEl) {
                treeEl.innerHTML = this.renderFolderSelectorTree(data.folder_tree || [], '');
            }
        } catch (error) {
            console.error('Erreur chargement arborescence:', error);
        }
    }

    renderFolderSelectorTree(tree, parentPath = '') {
        const allItems = [];
        const currentPath = this.folderSelectorMode === 'move' ? this.selectedFolderPath : this.selectedFolderPath;
        const isActive = currentPath === '/';

        // Lien vers la racine
        if (parentPath === '') {
            allItems.push(`
                <button 
                    onclick="mediaLibrary.selectFolderInSelector('/')"
                    class="w-full text-left px-3 py-2 rounded-lg transition flex items-center gap-2 text-sm ${isActive ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Racine
                </button>
            `);
        }

        // Parcourir l'arborescence
        tree.forEach(folder => {
            const folderPath = parentPath ? `${parentPath}/${folder.name}` : folder.name;
            const fullPath = '/' + folderPath;
            const isActive = currentPath === fullPath || currentPath === folderPath;

            allItems.push(`
                <div class="folder-item">
                    <button 
                        onclick="mediaLibrary.selectFolderInSelector('${fullPath}')"
                        class="w-full text-left px-3 py-2 rounded-lg transition flex items-center gap-2 text-sm ${isActive ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        ${this.escapeHtml(folder.name)}
                    </button>
                    ${folder.children && folder.children.length > 0 ? `
                        <div class="ml-4 mt-1">
                            ${this.renderFolderSelectorTree(folder.children, folderPath)}
                        </div>
                    ` : ''}
                </div>
            `);
        });

        return allItems.join('');
    }

    selectFolderInSelector(folderPath) {
        this.selectedFolderPath = folderPath;
        
        if (this.folderSelectorMode === 'create') {
            this.updateFolderSelectorBreadcrumb(folderPath);
            this.loadFolderSelectorTree(); // Recharger pour mettre à jour la sélection
        } else if (this.folderSelectorMode === 'move' || this.filesToMove.length > 0) {
            this.updateMoveFilesBreadcrumb(folderPath);
            this.updateMoveFilesSelectedFolder();
            this.loadMoveFilesTree(); // Recharger pour mettre à jour la sélection
        }
    }

    updateFolderSelectorBreadcrumb(folder) {
        const breadcrumbEl = document.getElementById('folder-selector-breadcrumb');
        if (!breadcrumbEl) return;

        const parts = folder === '/' ? [] : folder.split('/').filter(p => p);
        const items = ['<a href="#" onclick="mediaLibrary.selectFolderInSelector(\'/\'); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">Racine</a>'];

        let currentPath = '';
        parts.forEach((part, index) => {
            currentPath += '/' + part;
            const isLast = index === parts.length - 1;
            items.push(`
                <span class="text-slate-400 dark:text-slate-500">/</span>
                ${isLast 
                    ? `<span class="text-slate-900 dark:text-white font-medium">${this.escapeHtml(part)}</span>`
                    : `<a href="#" onclick="mediaLibrary.selectFolderInSelector('${currentPath}'); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">${this.escapeHtml(part)}</a>`
                }
            `);
        });

        breadcrumbEl.innerHTML = items.join('');
    }

    updateMoveFilesBreadcrumb(folder) {
        const breadcrumbEl = document.getElementById('move-files-breadcrumb');
        if (!breadcrumbEl) return;

        const parts = folder === '/' ? [] : folder.split('/').filter(p => p);
        const items = ['<a href="#" onclick="mediaLibrary.selectFolderInSelector(\'/\'); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">Racine</a>'];

        let currentPath = '';
        parts.forEach((part, index) => {
            currentPath += '/' + part;
            const isLast = index === parts.length - 1;
            items.push(`
                <span class="text-slate-400 dark:text-slate-500">/</span>
                ${isLast 
                    ? `<span class="text-slate-900 dark:text-white font-medium">${this.escapeHtml(part)}</span>`
                    : `<a href="#" onclick="mediaLibrary.selectFolderInSelector('${currentPath}'); return false;" class="text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">${this.escapeHtml(part)}</a>`
                }
            `);
        });

        breadcrumbEl.innerHTML = items.join('');
    }

    updateMoveFilesSelectedFolder() {
        const folderNameEl = document.getElementById('move-files-folder-name');
        if (folderNameEl) {
            folderNameEl.textContent = this.selectedFolderPath === '/' ? 'Racine' : this.selectedFolderPath.replace(/^\//, '');
        }
    }

    moveFile(fileId) {
        this.openMoveFilesModal([fileId]);
    }

}

// Initialiser la bibliothèque média
let mediaLibrary;
document.addEventListener('DOMContentLoaded', () => {
    mediaLibrary = new MediaLibrary();

    // Exposer globalement pour permettre l'utilisation depuis n'importe où
    window.mediaLibrary = mediaLibrary;
});

// Fonction globale pour ouvrir le sélecteur de fichiers depuis n'importe où
window.openMediaSelector = function(callback, acceptedTypes = null) {
    if (window.mediaLibrary) {
        window.mediaLibrary.openSelector(callback, acceptedTypes);
    }
};
