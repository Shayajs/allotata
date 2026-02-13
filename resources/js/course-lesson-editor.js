/**
 * Allo Tata - Éditeur de Cours
 * Éditeur visuel avec drag & drop, édition inline, et sauvegarde AJAX (draft/publication)
 */

import '../css/course-lesson-editor.css';
import Sortable from 'sortablejs';

class CourseLessonEditor {
    constructor(options = {}) {
        this.lessonId = options.lessonId;
        this.csrfToken = options.csrfToken;
        this.blocks = options.initialBlocks || [];
        this.saveTimeout = null;
        this.saveDebounceMs = 2000;
        this.isSaving = false;
        this.hasUnsavedChanges = false;
        this.sortableInstance = null;
        this.activeBlockId = null;
        this.editingElement = null;
        this.quillInstances = {}; // Stocker les instances Quill
        
        // Éléments DOM
        this.editorContainer = null;
        this.blocksContainer = null;
        this.sidebar = null;
        this.statusIndicator = null;
        
        this.init();
    }

    /**
     * Initialisation de l'éditeur
     */
    init() {
        this.blocksContainer = document.getElementById('blocks-container');
        this.sidebar = document.getElementById('editor-sidebar');
        this.statusIndicator = document.getElementById('save-status');

        if (!this.blocksContainer) {
            console.error('Élément blocks-container non trouvé');
            return;
        }

        this.initSortable();
        this.initEventListeners();
        this.initInlineEditing();
        this.renderBlocks();
        this.updateStatus('saved');
        
        // Avertissement avant de quitter si modifications non sauvegardées
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    /**
     * Initialiser le drag & drop avec SortableJS
     */
    initSortable() {
        if (!this.blocksContainer) return;

        this.sortableInstance = new Sortable(this.blocksContainer, {
            animation: 150,
            handle: '.course-drag-handle',
            ghostClass: 'course-block-ghost',
            chosenClass: 'course-block-chosen',
            dragClass: 'course-block-drag',
            onEnd: (evt) => {
                this.reorderBlocks(evt.oldIndex, evt.newIndex);
            }
        });
    }

    /**
     * Initialiser les événements
     */
    initEventListeners() {
        // Sélection de bloc au clic
        this.blocksContainer.addEventListener('click', (e) => {
            const blockEl = e.target.closest('.course-editable-block');
            if (blockEl && !e.target.closest('.course-block-toolbar')) {
                const blockId = blockEl.dataset.blockId;
                this.selectBlock(blockId);
                // Ne pas empêcher le scroll naturel si l'utilisateur fait un clic + glisser
            }
        });
        
        // Permettre le scroll même sur les blocs (click + drag pour scroller)
        let isScrolling = false;
        let scrollStartY = 0;
        let scrollStartTime = 0;
        
        this.blocksContainer.addEventListener('mousedown', (e) => {
            // Ne pas capturer le scroll si on clique sur un bouton ou un élément interactif
            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('textarea')) {
                return;
            }
            
            scrollStartY = e.clientY;
            scrollStartTime = Date.now();
            isScrolling = false;
        });
        
        this.blocksContainer.addEventListener('mousemove', (e) => {
            if (scrollStartY !== 0) {
                const deltaY = Math.abs(e.clientY - scrollStartY);
                const deltaTime = Date.now() - scrollStartTime;
                
                // Si l'utilisateur bouge la souris de plus de 5px en moins de 150ms, c'est probablement un scroll
                if (deltaY > 5 && deltaTime < 150) {
                    isScrolling = true;
                }
            }
        });
        
        this.blocksContainer.addEventListener('mouseup', (e) => {
            // Si c'était un scroll, ne pas sélectionner le bloc
            if (isScrolling) {
                e.stopPropagation();
            }
            scrollStartY = 0;
            scrollStartTime = 0;
            isScrolling = false;
        });

        // Actions des blocs
        this.blocksContainer.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]');
            if (!action) return;

            const actionType = action.dataset.action;
            const blockEl = action.closest('.course-editable-block');
            if (!blockEl) return;

            const blockId = blockEl.dataset.blockId;

            switch (actionType) {
                case 'delete':
                    this.deleteBlock(blockId);
                    break;
                case 'duplicate':
                    this.duplicateBlock(blockId);
                    break;
                case 'move-up':
                    this.moveBlock(blockId, -1);
                    break;
                case 'move-down':
                    this.moveBlock(blockId, 1);
                    break;
            }
        });

        // Ajouter un bloc depuis la sidebar
        document.querySelectorAll('[data-add-block]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const blockType = e.target.closest('[data-add-block]').dataset.addBlock;
                this.addBlock(blockType);
            });
        });

        // Sauvegarder comme brouillon
        const saveDraftBtn = document.getElementById('save-draft-btn');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', () => this.saveDraft());
        }

        // Publier
        const publishBtn = document.getElementById('publish-btn');
        if (publishBtn) {
            publishBtn.addEventListener('click', () => this.publish());
        }

        // Toggle sidebar
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        if (toggleSidebarBtn) {
            toggleSidebarBtn.addEventListener('click', () => this.toggleSidebar());
        }
    }

    /**
     * Initialiser l'édition inline
     */
    initInlineEditing() {
        if (!this.blocksContainer) return;
        
        this.blocksContainer.addEventListener('dblclick', (e) => {
            const editable = e.target.closest('[data-editable]');
            if (!editable) return;

            this.startInlineEdit(editable);
        });
    }

    /**
     * Démarrer l'édition inline d'un élément
     */
    startInlineEdit(element) {
        if (this.editingElement) {
            this.finishInlineEdit();
        }

        this.editingElement = element;
        element.setAttribute('contenteditable', 'true');
        element.classList.add('editing');
        element.focus();

        // Sélectionner tout le texte
        const range = document.createRange();
        range.selectNodeContents(element);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        // Écouter la fin de l'édition
        element.addEventListener('blur', () => this.finishInlineEdit(), { once: true });
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && element.tagName !== 'TEXTAREA') {
                e.preventDefault();
                this.finishInlineEdit();
            }
            if (e.key === 'Escape') {
                element.textContent = element.dataset.originalContent || element.textContent;
                this.finishInlineEdit();
            }
        });

        // Sauvegarder le contenu original
        element.dataset.originalContent = element.textContent || element.innerHTML;
    }

    /**
     * Terminer l'édition inline
     */
    finishInlineEdit() {
        if (!this.editingElement) return;

        const element = this.editingElement;
        element.setAttribute('contenteditable', 'false');
        element.classList.remove('editing');

        const blockEl = element.closest('.course-editable-block');
        if (blockEl) {
            const blockId = blockEl.dataset.blockId;
            const field = element.dataset.editable;
            const newValue = element.innerHTML || element.textContent;

            this.updateBlockContent(blockId, field, newValue);
        }

        this.editingElement = null;
    }

    /**
     * Générer un UUID
     */
    generateUUID() {
        return 'block-' + Math.random().toString(36).substring(2, 9) + Date.now().toString(36);
    }

    /**
     * Ajouter un nouveau bloc
     */
    addBlock(type, index = null) {
        const newBlock = {
            id: this.generateUUID(),
            type: type,
            content: this.getDefaultBlockContent(type),
            settings: this.getDefaultBlockSettings(type),
        };

        if (index !== null && index >= 0 && index <= this.blocks.length) {
            this.blocks.splice(index, 0, newBlock);
        } else {
            this.blocks.push(newBlock);
        }

        this.renderBlocks();
        this.scheduleAutoSave();
        this.selectBlock(newBlock.id);
        
        // Scroll vers le nouveau bloc
        setTimeout(() => {
            const blockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
            if (blockEl) {
                blockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }

    /**
     * Contenu par défaut selon le type de bloc
     */
    getDefaultBlockContent(type) {
        const defaults = {
            text: {
                html: '<p>Cliquez pour modifier ce texte. Vous pouvez ajouter du contenu riche avec des titres, des listes, et plus encore.</p>'
            },
            heading: {
                text: 'Titre',
                level: 2
            },
            image: {
                src: null,
                alt: 'Image',
                caption: ''
            },
            video: {
                type: 'external',
                url: '',
                file: ''
            },
            iframe: {
                src: ''
            },
            code: {
                code: '// Votre code ici',
                language: 'javascript'
            },
            callout: {
                type: 'info',
                title: 'Information',
                html: '<p>Contenu de l\'encadré...</p>'
            },
            steps: {
                title: '',
                steps: [
                    { title: 'Étape 1', content: '<p>Description de l\'étape 1</p>' },
                    { title: 'Étape 2', content: '<p>Description de l\'étape 2</p>' }
                ]
            },
            checklist: {
                title: '',
                items: [
                    { text: 'Item 1' },
                    { text: 'Item 2' }
                ]
            },
            exercise: {
                title: 'Exercice pratique',
                instructions: '<p>Instructions de l\'exercice...</p>',
                responseArea: false,
                solution: '<p>Solution de l\'exercice...</p>'
            },
            quiz_block: {
                question: 'Question ?',
                type: 'multiple_choice',
                options: ['Option 1', 'Option 2'],
                correctAnswer: 'Option 1',
                explanation: '<p>Explication de la réponse...</p>'
            },
            embed: {
                url: '',
                title: '',
                type: 'pdf'
            },
            gallery: {
                images: [],
                columns: 3
            },
            columns: {
                columns: 2,
                content: [{ html: '<p>Colonne 1</p>' }, { html: '<p>Colonne 2</p>' }]
            },
            divider: {
                style: 'solid'
            }
        };

        return defaults[type] || {};
    }

    /**
     * Paramètres par défaut selon le type de bloc
     */
    getDefaultBlockSettings(type) {
        const defaults = {
            text: {
                alignment: 'left',
                maxWidth: 'prose'
            },
            heading: {
                alignment: 'left',
                color: 'text-slate-900 dark:text-white'
            },
            image: {
                size: 'medium',
                rounded: true,
                shadow: true
            },
            video: {
                aspectRatio: '16:9',
                pinned: false
            },
            iframe: {
                height: 400,
                rounded: true
            },
            code: {
                showLineNumbers: true
            },
            callout: {
                type: 'info'
            },
            steps: {
                layout: 'vertical'
            },
            checklist: {
                layout: 'vertical'
            },
            exercise: {
                showSolution: false
            },
            quiz_block: {
                showExplanation: false
            },
            embed: {
                type: 'pdf'
            },
            gallery: {
                gap: 'medium',
                rounded: true
            },
            columns: {
                columns: 2
            },
            divider: {
                spacing: 'medium',
                style: 'solid'
            }
        };

        return defaults[type] || {};
    }

    /**
     * Supprimer un bloc
     */
    deleteBlock(blockId) {
        if (!confirm('Supprimer ce bloc ?')) return;

        const index = this.blocks.findIndex(b => b.id === blockId);
        if (index !== -1) {
            this.blocks.splice(index, 1);
            this.renderBlocks();
            this.scheduleAutoSave();
            this.activeBlockId = null;
            this.updatePropertiesPanel();
        }
    }

    /**
     * Dupliquer un bloc
     */
    duplicateBlock(blockId) {
        const index = this.blocks.findIndex(b => b.id === blockId);
        if (index === -1) return;

        const originalBlock = this.blocks[index];
        const newBlock = {
            ...JSON.parse(JSON.stringify(originalBlock)),
            id: this.generateUUID()
        };

        this.blocks.splice(index + 1, 0, newBlock);
        this.renderBlocks();
        this.scheduleAutoSave();
        this.selectBlock(newBlock.id);
    }

    /**
     * Déplacer un bloc
     */
    moveBlock(blockId, direction) {
        const index = this.blocks.findIndex(b => b.id === blockId);
        if (index === -1) return;

        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= this.blocks.length) return;

        const [block] = this.blocks.splice(index, 1);
        this.blocks.splice(newIndex, 0, block);
        this.renderBlocks();
        this.scheduleAutoSave();
        this.selectBlock(blockId);
    }

    /**
     * Réordonner les blocs après drag & drop
     */
    reorderBlocks(oldIndex, newIndex) {
        if (oldIndex === newIndex) return;

        const [block] = this.blocks.splice(oldIndex, 1);
        this.blocks.splice(newIndex, 0, block);
        this.scheduleAutoSave();
    }

    /**
     * Sélectionner un bloc
     */
    selectBlock(blockId) {
        // Désélectionner l'ancien bloc
        document.querySelectorAll('.course-editable-block.selected').forEach(el => {
            el.classList.remove('selected');
        });

        this.activeBlockId = blockId;

        // Sélectionner le nouveau bloc
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (blockEl) {
            blockEl.classList.add('selected');
            
            // Scroller vers le bloc sélectionné dans le conteneur scrollable
            if (this.blocksContainer) {
                // Calculer la position du bloc par rapport au conteneur scrollable
                const containerRect = this.blocksContainer.getBoundingClientRect();
                const blockRect = blockEl.getBoundingClientRect();
                
                // Position relative du bloc dans le conteneur
                const relativeTop = blockRect.top - containerRect.top + this.blocksContainer.scrollTop;
                
                // Scroller pour centrer le bloc dans le conteneur visible (avec un peu de marge)
                const scrollPosition = relativeTop - (containerRect.height / 2) + (blockRect.height / 2) - 50;
                
                this.blocksContainer.scrollTo({
                    top: Math.max(0, scrollPosition),
                    behavior: 'smooth'
                });
            } else {
                // Fallback si le conteneur n'est pas trouvé
                blockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Mettre à jour le panneau de propriétés et s'assurer que l'onglet Propriétés est visible
        this.updatePropertiesPanel();
        
        // Basculer automatiquement vers l'onglet Propriétés quand un bloc est sélectionné
        if (window.switchSidebarTab) {
            window.switchSidebarTab('properties');
        }
    }

    /**
     * Mettre à jour le contenu d'un bloc
     */
    updateBlockContent(blockId, field, value) {
        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        // Mettre à jour le contenu
        if (!block.content) block.content = {};
        block.content[field] = value;

        // Re-render le bloc si c'est un champ qui affecte l'affichage
        if (field === 'type' || field === 'url' || field === 'file') {
            this.renderBlock(blockId);
        }
        this.scheduleAutoSave();
    }

    /**
     * Mettre à jour les paramètres d'un bloc
     */
    updateBlockSetting(blockId, setting, value) {
        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        if (!block.settings) block.settings = {};
        block.settings[setting] = value;

        // Re-render le bloc si c'est un setting qui affecte l'affichage
        if (setting === 'pinned' || setting === 'aspectRatio') {
            this.renderBlock(blockId);
        }
        this.scheduleAutoSave();
    }

    /**
     * Rendre tous les blocs
     */
    async renderBlocks() {
        if (!this.blocksContainer) return;

        this.blocksContainer.innerHTML = '';

        for (const block of this.blocks) {
            await this.renderBlockElement(block);
        }

        // Réinitialiser SortableJS
        if (this.sortableInstance) {
            this.sortableInstance.destroy();
        }
        this.initSortable();
    }

    /**
     * Rendre un bloc spécifique
     */
    async renderBlock(blockId) {
        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (blockEl) {
            const newHtml = await this.fetchBlockHTML(block);
            if (newHtml) {
                const wasSelected = blockEl.classList.contains('selected');
                blockEl.outerHTML = newHtml;
                
                // Réinitialiser SortableJS
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                }
                this.initSortable();
                
                // Re-sélectionner si nécessaire
                if (wasSelected) {
                    const newBlockEl = document.querySelector(`[data-block-id="${blockId}"]`);
                    if (newBlockEl) {
                        this.selectBlock(blockId);
                    }
                }
                
                // Réinitialiser Quill si c'est un bloc texte
                if (block.type === 'text' && this.activeBlockId === blockId) {
                    setTimeout(() => {
                        this.updatePropertiesPanel();
                    }, 100);
                }
            }
        }
    }

    /**
     * Rendre un élément de bloc
     */
    async renderBlockElement(block) {
        const html = await this.fetchBlockHTML(block);
        if (html) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const blockEl = temp.firstElementChild;
            this.blocksContainer.appendChild(blockEl);
        }
    }

    /**
     * Récupérer le HTML d'un bloc via AJAX
     */
    async fetchBlockHTML(block) {
        try {
            const response = await fetch(`/admin/courses/lessons/${this.lessonId}/render-block`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ block })
            });

            const data = await response.json();
            if (data.success) {
                // Envelopper dans la structure d'édition
                const isSelected = this.activeBlockId === block.id;
                return this.wrapBlockHTML(data.html, block, isSelected);
            }
            return null;
        } catch (error) {
            console.error('Erreur lors du rendu du bloc:', error);
            return null;
        }
    }

    /**
     * Envelopper le HTML du bloc dans la structure d'édition
     */
    wrapBlockHTML(html, block, isSelected) {
        return `
            <div class="course-editable-block ${isSelected ? 'selected' : ''}" 
                 data-block-id="${block.id}" 
                 data-block-type="${block.type}">
                <div class="course-drag-handle" title="Glisser pour déplacer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </div>
                <div class="course-block-toolbar">
                    <button type="button" data-action="move-up" title="Déplacer vers le haut">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="move-down" title="Déplacer vers le bas">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="duplicate" title="Dupliquer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="delete" title="Supprimer" class="text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="course-block-content">
                    ${html}
                </div>
            </div>
        `;
    }

    /**
     * Mettre à jour le panneau de propriétés
     */
    updatePropertiesPanel() {
        const panel = document.getElementById('block-properties');
        if (!panel) return;

        const block = this.blocks.find(b => b.id === this.activeBlockId);
        if (!block) {
            panel.innerHTML = '<p class="text-slate-400 text-center py-8 text-sm">Sélectionnez un bloc pour voir ses propriétés</p>';
            return;
        }

        // Générer le formulaire de propriétés selon le type de bloc
        panel.innerHTML = this.generatePropertiesForm(block);

        // Attacher les événements pour les settings normales
        panel.querySelectorAll('[data-setting]:not([data-setting="videoType"])').forEach(input => {
            input.addEventListener('change', (e) => {
                const setting = e.target.dataset.setting;
                let value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
                if (e.target.type === 'number') value = parseFloat(value);
                this.updateBlockSetting(this.activeBlockId, setting, value);
            });
        });

        panel.querySelectorAll('[data-content]').forEach(input => {
            input.addEventListener('input', (e) => {
                const field = e.target.dataset.content;
                this.updateBlockContent(this.activeBlockId, field, e.target.value);
            });
        });
    }

    /**
     * Générer le formulaire de propriétés selon le type de bloc
     */
    generatePropertiesForm(block) {
        // Cette méthode sera complétée avec les formulaires spécifiques à chaque type de bloc
        // Pour l'instant, retourner un formulaire générique
        let html = `<div class="course-block-settings">
            <h4 class="text-white font-semibold mb-4">Propriétés du bloc</h4>
            <p class="text-slate-400 text-sm mb-4">Type: <strong class="text-white">${block.type}</strong></p>
        `;

        // Ajouter les champs selon le type
        switch (block.type) {
            case 'heading':
                html += `
                    <label class="block text-sm text-slate-300 mb-2">Texte</label>
                    <input type="text" data-content="text" value="${this.escapeHtml(block.content?.text || '')}" 
                           class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm mb-3">
                    <label class="block text-sm text-slate-300 mb-2">Niveau (1-6)</label>
                    <input type="number" data-content="level" min="1" max="6" value="${block.content?.level || 2}" 
                           class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm">
                `;
                break;
            case 'text':
                const textContent = (block.content?.html || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                html += `
                    <label class="block text-sm text-slate-300 mb-2">Contenu HTML</label>
                    <div id="quill-editor-${block.id}" class="bg-slate-700 rounded" style="min-height: 200px;"></div>
                    <input type="hidden" data-content="html" id="quill-content-${block.id}" value="${textContent}">
                    <p class="mt-2 text-xs text-slate-400">Utilisez les outils ci-dessus pour formater votre texte. Le bouton <span class="allotata-text">AlloTata</span> applique un style spécial.</p>
                `;
                // Initialiser Quill pour ce bloc après l'insertion
                setTimeout(() => {
                    this.initQuillEditor(block.id, block.content?.html || '');
                }, 200);
                break;
            case 'video':
                const videoUrl = block.content?.url || '';
                const videoType = block.content?.type || 'external';
                const videoFile = block.content?.file || '';
                const isPinned = block.settings?.pinned || false;
                html += `
                    <label class="block text-sm text-slate-300 mb-2">Type de vidéo</label>
                    <select id="video-type-${block.id}" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm mb-3">
                        <option value="external" ${videoType === 'external' ? 'selected' : ''}>Service externe (YouTube, Vimeo...)</option>
                        <option value="upload" ${videoType === 'upload' ? 'selected' : ''}>Vidéo personnelle</option>
                    </select>
                    <div id="video-external-${block.id}" class="${videoType === 'external' ? '' : 'hidden'}">
                        <label class="block text-sm text-slate-300 mb-2">URL de la vidéo</label>
                        <input type="url" data-content="url" value="${this.escapeHtml(videoUrl)}" 
                               placeholder="https://youtube.com/watch?v=..." 
                               class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm mb-3">
                        <p class="text-xs text-slate-400 mb-3">YouTube, Vimeo, ou autre service</p>
                    </div>
                    <div id="video-upload-${block.id}" class="${videoType === 'upload' ? '' : 'hidden'}">
                        <label class="block text-sm text-slate-300 mb-2">Vidéo</label>
                        ${videoFile ? `
                            <div class="mb-2 p-2 bg-slate-800 rounded text-xs text-slate-400 flex items-center justify-between">
                                <span>Vidéo actuelle: ${this.escapeHtml(videoFile)}</span>
                            </div>
                        ` : '<p class="text-xs text-slate-400 mb-3">Aucune vidéo sélectionnée</p>'}
                        <div class="flex gap-2">
                            <input type="hidden" id="video-file-path-${block.id}" data-content="file" value="${this.escapeHtml(videoFile || '')}">
                            <button type="button" onclick="window.courseEditor.openMediaSelectorForBlock('${block.id}')" 
                                    class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                ${videoFile ? 'Changer la vidéo' : 'Sélectionner depuis la médiathèque'}
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Sélectionnez une vidéo depuis la médiathèque ou uploadez-en une nouvelle</p>
                    </div>
                    <label class="block text-sm text-slate-300 mb-2 mt-3">Format</label>
                    <select data-setting="aspectRatio" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm mb-3">
                        <option value="16:9" ${block.settings?.aspectRatio === '16:9' ? 'selected' : ''}>16:9 (Widescreen)</option>
                        <option value="4:3" ${block.settings?.aspectRatio === '4:3' ? 'selected' : ''}>4:3 (Standard)</option>
                        <option value="1:1" ${block.settings?.aspectRatio === '1:1' ? 'selected' : ''}>1:1 (Carré)</option>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer mt-3">
                        <input type="checkbox" data-setting="pinned" ${isPinned ? 'checked' : ''} 
                               class="w-4 h-4 rounded border-slate-600 text-green-600 focus:ring-green-500 bg-slate-700">
                        <span class="text-sm text-slate-300">Épingler la vidéo (reste visible en scrollant - PC uniquement)</span>
                    </label>
                `;
                // Gérer le changement de type vidéo
                setTimeout(() => {
                    const typeSelect = document.getElementById(`video-type-${block.id}`);
                    if (typeSelect) {
                        typeSelect.addEventListener('change', (e) => {
                            const type = e.target.value;
                            const externalDiv = document.getElementById(`video-external-${block.id}`);
                            const uploadDiv = document.getElementById(`video-upload-${block.id}`);
                            if (type === 'external') {
                                externalDiv?.classList.remove('hidden');
                                uploadDiv?.classList.add('hidden');
                            } else {
                                externalDiv?.classList.add('hidden');
                                uploadDiv?.classList.remove('hidden');
                            }
                            // Mettre à jour le bloc
                            this.updateBlockContent(this.activeBlockId, 'type', type);
                        });
                    }
                }, 100);
                break;
            // Ajouter d'autres types...
        }

        html += '</div>';
        return html;
    }

    /**
     * Planifier la sauvegarde automatique
     */
    scheduleAutoSave() {
        this.hasUnsavedChanges = true;
        this.updateStatus('saving');

        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveDraft(true); // Auto-save
        }, this.saveDebounceMs);
    }

    /**
     * Sauvegarder comme brouillon (AJAX)
     */
    async saveDraft(isAutoSave = false) {
        if (this.isSaving) return;

        this.isSaving = true;
        this.updateStatus('saving');

        try {
            // Récupérer les paramètres de la leçon
            const lessonData = this.getLessonSettings();

            const response = await fetch(`/admin/courses/lessons/${this.lessonId}/save-draft`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    blocks: this.blocks,
                    ...lessonData,
                    is_auto_save: isAutoSave
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.hasUnsavedChanges = false;
                this.updateStatus('saved');
            } else {
                this.updateStatus('error');
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            this.updateStatus('error');
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Publier la leçon (AJAX)
     */
    async publish() {
        if (!confirm('Publier cette leçon ? Elle sera visible pour tous les utilisateurs.')) {
            return;
        }

        if (this.isSaving) return;

        this.isSaving = true;
        this.updateStatus('saving');

        try {
            // Récupérer les paramètres de la leçon
            const lessonData = this.getLessonSettings();

            const response = await fetch(`/admin/courses/lessons/${this.lessonId}/publish`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    blocks: this.blocks,
                    ...lessonData
                })
            });

            // Vérifier si la réponse est du JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                // Si ce n'est pas du JSON, c'est probablement une page d'erreur HTML
                const text = await response.text();
                console.error('Réponse non-JSON reçue:', text.substring(0, 500));
                
                this.updateStatus('error');
                alert('Erreur lors de la publication: Le serveur a renvoyé une réponse invalide. Veuillez vérifier les logs du serveur.');
                this.isSaving = false;
                return;
            }
            
            if (!response.ok) {
                // Erreur HTTP (4xx, 5xx)
                this.updateStatus('error');
                const errorMsg = data.error || data.message || `Erreur HTTP ${response.status}`;
                alert('Erreur lors de la publication: ' + errorMsg);
                
                if (data.errors && typeof data.errors === 'object') {
                    console.error('Erreurs de validation:', data.errors);
                }
                
                this.isSaving = false;
                return;
            }
            
            if (data.success) {
                this.hasUnsavedChanges = false;
                this.updateStatus('saved');
                // Recharger la page pour afficher le statut publié
                window.location.reload();
            } else {
                this.updateStatus('error');
                alert('Erreur lors de la publication: ' + (data.error || data.message || 'Une erreur est survenue'));
            }
        } catch (error) {
            console.error('Erreur lors de la publication:', error);
            this.updateStatus('error');
            
            if (error instanceof SyntaxError) {
                alert('Erreur lors de la publication: Le serveur a renvoyé une réponse invalide. Veuillez vérifier les logs du serveur.');
            } else {
                alert('Erreur lors de la publication: ' + (error.message || 'Une erreur est survenue'));
            }
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Récupérer les paramètres de la leçon depuis le formulaire
     */
    getLessonSettings() {
        return {
            titre: document.getElementById('lesson-title')?.value || '',
            description: document.getElementById('lesson-description')?.value || '',
            type: document.getElementById('lesson-type')?.value || 'course',
            page_key: document.getElementById('lesson-page-key')?.value || '',
            ordre: parseInt(document.getElementById('lesson-ordre')?.value || 0),
            points_quiz: parseInt(document.getElementById('lesson-points')?.value || 0),
            est_actif: document.getElementById('lesson-actif')?.checked || false,
        };
    }

    /**
     * Mettre à jour le statut de sauvegarde
     */
    updateStatus(status) {
        if (!this.statusIndicator) return;

        this.statusIndicator.className = `course-save-status ${status}`;
        
        switch (status) {
            case 'saved':
                this.statusIndicator.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Sauvegardé</span>
                `;
                break;
            case 'saving':
                this.statusIndicator.innerHTML = `
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Enregistrement...</span>
                `;
                break;
            case 'error':
                this.statusIndicator.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Erreur</span>
                `;
                break;
        }
    }

    /**
     * Toggle sidebar
     */
    toggleSidebar() {
        if (this.sidebar) {
            this.sidebar.classList.toggle('hidden');
            
            // Ajuster la marge du preview
            const preview = document.getElementById('course-preview');
            if (preview) {
                preview.classList.toggle('full-width');
            }
        }
    }

    /**
     * Échapper le HTML pour éviter les injections
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialiser l'éditeur Quill pour un bloc texte
     */
    initQuillEditor(blockId, initialContent = '') {
        const editorEl = document.getElementById(`quill-editor-${blockId}`);
        if (!editorEl) {
            console.warn(`Élément Quill éditeur non trouvé pour bloc ${blockId}`);
            return;
        }
        
        if (window.Quill === undefined) {
            console.warn('Quill.js n\'est pas chargé');
            return;
        }

        // Vérifier qu'on n'a pas déjà initialisé Quill pour ce bloc
        if (this.quillInstances && this.quillInstances[blockId]) {
            console.warn(`Quill déjà initialisé pour bloc ${blockId}`);
            return;
        }

        // Enregistrer le format personnalisé AlloTata AVANT de créer l'instance Quill
        // Note: Le format sera utilisé via dangerouslyPasteHTML, Quill ne le gère pas nativement
        // mais on le préserve lors du chargement/sauvegarde

        // Configuration de la toolbar
        const toolbarOptions = [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            [{ 'align': [] }],
            ['clean']
        ];

        // Créer l'instance Quill
        const quill = new Quill(editorEl, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions,
                clipboard: {
                    matchVisual: false // Important : désactiver le matching visuel pour préserver les styles inline
                }
            },
            placeholder: 'Commencez à écrire...',
            formats: ['bold', 'italic', 'underline', 'link', 'header', 'list', 'align', 'color', 'background']
        });
        
        // Personnaliser le module clipboard pour préserver les spans allotata-text
        const Clipboard = quill.getModule('clipboard');
        
        // Ajouter un matcher personnalisé pour préserver les spans avec classe allotata-text
        Clipboard.addMatcher(Node.ELEMENT_NODE, (node, delta) => {
            if (node.tagName === 'SPAN' && node.classList && node.classList.contains('allotata-text')) {
                // Préserver le HTML tel quel en utilisant dangerouslyPasteHTML
                // On retourne le delta mais on va aussi préserver le HTML dans l'éditeur
                return delta;
            }
            return delta;
        });

        // Ajouter le style CSS pour le bouton AlloTata
        if (!document.getElementById('quill-allotata-style')) {
            const style = document.createElement('style');
            style.id = 'quill-allotata-style';
            style.textContent = `
                .ql-allotata {
                    font-weight: 900 !important;
                    width: auto !important;
                    padding: 0 8px !important;
                }
                .ql-allotata span.allotata-text {
                    background: linear-gradient(135deg, #22c55e 0%, #f97316 100%) !important;
                    -webkit-background-clip: text !important;
                    -webkit-text-fill-color: transparent !important;
                    background-clip: text !important;
                }
                .ql-toolbar .ql-allotata.ql-active span.allotata-text {
                    opacity: 0.7;
                }
            `;
            document.head.appendChild(style);
        }

        // Ajouter le bouton AlloTata à la toolbar après initialisation
        setTimeout(() => {
            this.addAlloTataButton(quill, blockId);
        }, 100);

        // Charger le contenu initial en préservant les classes AlloTata
        if (initialContent) {
            // Préserver et restaurer le format AlloTata avant de charger dans Quill
            const processedContent = this.preserveAlloTataFormat(initialContent);
            
            // Utiliser dangerouslyPasteHTML pour préserver le HTML personnalisé
            // Attention: dangerouslyPasteHTML avec index 0 remplace tout le contenu
            quill.clipboard.dangerouslyPasteHTML(processedContent);
            
            // Restaurer le format immédiatement après chargement
            setTimeout(() => {
                this.restoreAlloTataInEditor(quill);
            }, 100);
        }

        // Utiliser un MutationObserver pour surveiller et restaurer le format AlloTata
        const observer = new MutationObserver((mutations) => {
            let needsRestore = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' || mutation.type === 'childList') {
                    const target = mutation.target;
                    if (target.classList && target.classList.contains('allotata-text')) {
                        // Vérifier si les styles sont complets
                        const style = target.getAttribute('style') || '';
                        if (!this.hasAllAlloTataStyles(style)) {
                            needsRestore = true;
                        }
                    }
                }
            });
            
            if (needsRestore) {
                this.restoreAlloTataInEditor(quill);
            }
        });
        
        // Observer les changements dans l'éditeur
        observer.observe(quill.root, {
            attributes: true,
            attributeFilter: ['class', 'style'],
            childList: true,
            subtree: true
        });
        
        // Écouter les changements de texte et restaurer le format AlloTata
        quill.on('text-change', () => {
            const html = quill.root.innerHTML;
            // Restaurer les classes allotata-text pour les éléments qui ont le format
            const processedHtml = this.restoreAlloTataFormat(html);
            
            const hiddenInput = document.getElementById(`quill-content-${blockId}`);
            if (hiddenInput) {
                // Toujours sauvegarder le HTML restauré
                hiddenInput.value = processedHtml;
                // Déclencher l'événement input pour mettre à jour le bloc
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                // Déclencher la sauvegarde automatique
                this.scheduleAutoSave();
            }
        });
        
        // Restaurer le format périodiquement pour contrer les suppressions par Quill
        let restoreInterval = setInterval(() => {
            const restored = this.restoreAlloTataInEditor(quill);
            // Si on a restauré quelque chose, mettre à jour l'input caché
            if (restored) {
                const html = quill.root.innerHTML;
                const processedHtml = this.restoreAlloTataFormat(html);
                const hiddenInput = document.getElementById(`quill-content-${blockId}`);
                if (hiddenInput) {
                    hiddenInput.value = processedHtml;
                }
            }
        }, 500);
        
        // Nettoyer l'intervalle quand le bloc est détruit
        // (stocker l'ID de l'intervalle dans l'instance Quill pour pouvoir le nettoyer plus tard)
        quill._allotataInterval = restoreInterval;

        // Stocker la référence Quill
        if (!this.quillInstances) this.quillInstances = {};
        this.quillInstances[blockId] = quill;
    }

    /**
     * Enregistrer le format personnalisé AlloTata dans Quill
     * Note: Quill peut avoir du mal avec les styles inline complexes, 
     * donc on utilise dangerouslyPasteHTML et on préserve le HTML lors de la sauvegarde
     */
    registerAlloTataFormat() {
        // Cette méthode est conservée pour référence mais n'est plus utilisée
        // car Quill préserve mieux le HTML via dangerouslyPasteHTML
        if (!window.Quill || window.QuillAlloTataRegistered) {
            return;
        }
        window.QuillAlloTataRegistered = true;
    }

    /**
     * Vérifier si un style contient tous les attributs AlloTata nécessaires
     */
    hasAllAlloTataStyles(style) {
        if (!style) return false;
        const required = ['font-weight', 'background', '-webkit-background-clip', '-webkit-text-fill-color', 'background-clip', 'display'];
        return required.every(prop => style.includes(prop));
    }

    /**
     * Restaurer le format AlloTata directement dans l'éditeur Quill
     */
    restoreAlloTataInEditor(quill) {
        const allotataElements = quill.root.querySelectorAll('.allotata-text');
        let hasChanges = false;
        
        allotataElements.forEach(element => {
            let style = element.getAttribute('style') || '';
            
            if (!this.hasAllAlloTataStyles(style)) {
                hasChanges = true;
                // Ajouter les styles manquants
                const fullStyle = 'font-weight: 900; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;';
                
                // Si pas de style, utiliser le style complet
                if (!style) {
                    element.setAttribute('style', fullStyle);
                } else {
                    // Ajouter les propriétés manquantes
                    let updatedStyle = style;
                    if (!style.includes('font-weight')) updatedStyle += '; font-weight: 900';
                    if (!style.includes('background:')) updatedStyle += '; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%)';
                    if (!style.includes('-webkit-background-clip')) updatedStyle += '; -webkit-background-clip: text';
                    if (!style.includes('-webkit-text-fill-color')) updatedStyle += '; -webkit-text-fill-color: transparent';
                    if (!style.includes('background-clip')) updatedStyle += '; background-clip: text';
                    if (!style.includes('display:')) updatedStyle += '; display: inline-block';
                    
                    element.setAttribute('style', updatedStyle);
                }
            }
        });
        
        return hasChanges;
    }

    /**
     * Préserver le format AlloTata lors du chargement initial
     * Cette fonction s'assure que les spans avec classe allotata-text gardent leurs styles
     */
    preserveAlloTataFormat(html) {
        if (!html) return html;
        
        // Styles complets nécessaires
        const fullStyle = 'font-weight: 900; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;';
        
        // Remplacer tous les spans avec classe allotata-text pour s'assurer qu'ils ont les bons attributs
        return html.replace(
            /<span\s+([^>]*)class=["']([^"']*allotata-text[^"']*)["']([^>]*)>(.*?)<\/span>/gis,
            (match, attrs1, classes, attrs2, content) => {
                const allAttrs = (attrs1 + ' ' + attrs2).trim();
                let finalStyle = fullStyle;
                
                // Extraire les styles existants
                const styleMatch = allAttrs.match(/style=["']([^"']*)["']/i);
                if (styleMatch) {
                    let existingStyles = styleMatch[1];
                    
                    // Vérifier chaque propriété nécessaire
                    const requiredProps = {
                        'font-weight': 'font-weight: 900',
                        'background': 'background: linear-gradient(135deg, #22c55e 0%, #f97316 100%)',
                        '-webkit-background-clip': '-webkit-background-clip: text',
                        '-webkit-text-fill-color': '-webkit-text-fill-color: transparent',
                        'background-clip': 'background-clip: text',
                        'display': 'display: inline-block'
                    };
                    
                    let needsUpdate = false;
                    Object.keys(requiredProps).forEach(prop => {
                        if (!existingStyles.includes(prop)) {
                            existingStyles += '; ' + requiredProps[prop];
                            needsUpdate = true;
                        }
                    });
                    
                    if (needsUpdate) {
                        finalStyle = existingStyles;
                    } else {
                        finalStyle = existingStyles;
                    }
                    
                    // Nettoyer les attributs et reconstruire
                    const cleanAttrs = allAttrs.replace(/style=["'][^"']*["']/i, '').trim();
                    return `<span class="${classes}" style="${finalStyle}"${cleanAttrs ? ' ' + cleanAttrs : ''}>${content}</span>`;
                } else {
                    // Pas de style, ajouter tous les styles nécessaires
                    const cleanAttrs = allAttrs.trim();
                    return `<span class="${classes}" style="${fullStyle}"${cleanAttrs ? ' ' + cleanAttrs : ''}>${content}</span>`;
                }
            }
        );
    }

    /**
     * Restaurer le format AlloTata dans le HTML sauvegardé
     * S'assure que tous les spans avec classe allotata-text ont les styles complets
     */
    restoreAlloTataFormat(html) {
        if (!html) return html;
        
        // Styles complets nécessaires pour le format AlloTata
        const fullAlloTataStyle = 'font-weight: 900; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;';
        
        // Remplacer tous les spans avec classe allotata-text pour s'assurer qu'ils ont tous les styles
        return html.replace(
            /<span([^>]*)class=["']([^"']*allotata-text[^"']*)["']([^>]*)>(.*?)<\/span>/gi,
            (match, attrs1, classes, attrs2, content) => {
                const allAttrs = (attrs1 + ' ' + attrs2).trim();
                let finalStyle = fullAlloTataStyle;
                
                // Extraire les styles existants s'il y en a
                const styleMatch = allAttrs.match(/style=["']([^"']*)["']/i);
                if (styleMatch) {
                    let existingStyles = styleMatch[1];
                    
                    // Vérifier chaque propriété nécessaire
                    const requiredProps = {
                        'font-weight': 'font-weight: 900',
                        'background': 'background: linear-gradient(135deg, #22c55e 0%, #f97316 100%)',
                        '-webkit-background-clip': '-webkit-background-clip: text',
                        '-webkit-text-fill-color': '-webkit-text-fill-color: transparent',
                        'background-clip': 'background-clip: text',
                        'display': 'display: inline-block'
                    };
                    
                    let needsUpdate = false;
                    Object.keys(requiredProps).forEach(prop => {
                        if (!existingStyles.includes(prop)) {
                            existingStyles += '; ' + requiredProps[prop];
                            needsUpdate = true;
                        }
                    });
                    
                    if (needsUpdate) {
                        finalStyle = existingStyles;
                    } else {
                        finalStyle = existingStyles;
                    }
                    
                    // Retirer l'ancien attribut style
                    const cleanAttrs = allAttrs.replace(/style=["'][^"']*["']/i, '').trim();
                    return `<span class="${classes}" style="${finalStyle}"${cleanAttrs ? ' ' + cleanAttrs : ''}>${content}</span>`;
                } else {
                    // Pas de style, ajouter tous les styles nécessaires
                    const cleanAttrs = allAttrs.trim();
                    return `<span class="${classes}" style="${fullAlloTataStyle}"${cleanAttrs ? ' ' + cleanAttrs : ''}>${content}</span>`;
                }
            }
        );
    }

    /**
     * Ajouter le bouton AlloTata à la toolbar Quill (méthode de secours si le format personnalisé ne fonctionne pas)
     */
    addAlloTataButton(quill, blockId) {
        const toolbar = quill.getModule('toolbar');
        if (!toolbar) return;
        
        const container = toolbar.container;

        // Vérifier si le bouton existe déjà
        if (container.querySelector('.ql-allotata')) {
            return;
        }

        // Créer le bouton AlloTata
        const allotataButton = document.createElement('button');
        allotataButton.className = 'ql-allotata';
        allotataButton.type = 'button';
        allotataButton.innerHTML = '<span class="allotata-text">AlloTata</span>';
        allotataButton.title = 'Style AlloTata (Gras + Dégradé vert-orange)';
        allotataButton.setAttribute('aria-label', 'AlloTata');

        // Ajouter le gestionnaire de clic
        allotataButton.addEventListener('click', () => {
            const range = quill.getSelection(true);
            if (!range) {
                // Si aucun texte n'est sélectionné, on fait rien
                return;
            }
            
            if (range.length > 0) {
                // Texte sélectionné : appliquer le style AlloTata
                const text = quill.getText(range.index, range.length);
                quill.deleteText(range.index, range.length);
                
                // Créer le HTML avec tous les attributs nécessaires
                const html = `<span class="allotata-text" style="font-weight: 900; background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;">${this.escapeHtml(text)}</span>`;
                
                // Insérer le HTML formaté
                quill.clipboard.dangerouslyPasteHTML(range.index, html);
                
                // Restaurer le format immédiatement après insertion
                setTimeout(() => {
                    this.restoreAlloTataInEditor(quill);
                }, 10);
                
                // Remettre le curseur après le texte inséré
                quill.setSelection(range.index + text.length);
            } else {
                // Pas de texte sélectionné : activer le mode pour le prochain texte tapé
                // On peut mettre un indicateur ou simplement ne rien faire
                // L'utilisateur devra sélectionner du texte puis cliquer sur le bouton
                const format = quill.getFormat(range);
                if (format.allotata) {
                    // Désactiver si déjà activé
                    quill.format('allotata', false);
                    allotataButton.classList.remove('ql-active');
                } else {
                    // Activer pour le prochain texte
                    allotataButton.classList.add('ql-active');
                }
            }
        });

        // Ajouter le bouton à la toolbar
        const formatGroups = container.querySelectorAll('.ql-formats');
        if (formatGroups.length > 0) {
            const allotataWrapper = document.createElement('span');
            allotataWrapper.className = 'ql-formats';
            allotataWrapper.appendChild(allotataButton);
            formatGroups[0].parentNode.insertBefore(allotataWrapper, formatGroups[0].nextSibling);
        } else {
            container.appendChild(allotataButton);
        }
    }

    /**
     * Ouvrir le sélecteur de médiathèque pour un bloc vidéo
     */
    openMediaSelectorForBlock(blockId) {
        // Vérifier que openMediaSelector est disponible
        if (typeof window.openMediaSelector !== 'function') {
            alert('La médiathèque n\'est pas chargée. Veuillez recharger la page.');
            console.error('openMediaSelector is not available');
            return;
        }

        // Sauvegarder le blockId pour le callback
        this.currentVideoBlockId = blockId;

        // Ouvrir la médiathèque en filtrant pour les vidéos uniquement
        window.openMediaSelector((file, url) => {
            this.handleVideoFileSelect(blockId, file, url);
        }, ['video']); // Filtrer pour les vidéos uniquement
    }

    /**
     * Gérer la sélection d'un fichier vidéo depuis la médiathèque
     */
    handleVideoFileSelect(blockId, file, url) {
        // Le fichier de la médiathèque contient le chemin relatif dans file.path
        // Format attendu: "media/videos/fichier.mp4" (sans storage/)
        let filePath = '';
        
        if (file && file.path) {
            // Utiliser directement le chemin du fichier de la médiathèque
            // file.path est déjà au format "media/videos/fichier.mp4"
            filePath = file.path;
        } else if (url && url.startsWith('/media/')) {
            // Extraire le chemin après /media/ si file.path n'est pas disponible
            filePath = url.substring('/media/'.length);
        } else {
            console.error('Impossible de déterminer le chemin du fichier:', { file, url });
            alert('Erreur: Impossible de déterminer le chemin du fichier');
            return;
        }

        // Mettre à jour le bloc avec le chemin de la vidéo
        this.updateBlockContent(blockId, 'file', filePath);
        this.updateBlockContent(blockId, 'type', 'upload');

        // Mettre à jour l'affichage
        this.updatePropertiesPanel();
        this.renderBlock(blockId);
        this.scheduleAutoSave();

        console.log('Vidéo sélectionnée depuis la médiathèque:', { blockId, filePath, fileName: file?.name, url });
    }

    /**
     * Upload une vidéo pour un bloc (méthode alternative pour upload direct)
     */
    async uploadVideoForBlock(blockId) {
        const fileInput = document.getElementById(`video-file-input-${blockId}`);
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            alert('Veuillez sélectionner une vidéo');
            return;
        }

        const file = fileInput.files[0];
        
        // Vérifier la taille (100MB max)
        if (file.size > 100 * 1024 * 1024) {
            alert('La vidéo est trop volumineuse (max 100MB)');
            return;
        }
        
        const formData = new FormData();
        formData.append('video', file);
        formData.append('block_id', blockId);

        // Afficher un indicateur de chargement
        const uploadBtn = fileInput.nextElementSibling;
        const originalText = uploadBtn?.textContent || 'Uploader la vidéo';
        if (uploadBtn) {
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Upload en cours...';
        }

        try {
            const response = await fetch(`/admin/courses/lessons/${this.lessonId}/upload-video`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                // Mettre à jour le bloc avec le chemin de la vidéo
                this.updateBlockContent(blockId, 'file', data.path);
                this.updateBlockContent(blockId, 'type', 'upload');
                
                // Mettre à jour l'affichage
                this.updatePropertiesPanel();
                this.renderBlock(blockId);
                this.scheduleAutoSave();
                
                if (uploadBtn) {
                    uploadBtn.textContent = '✓ Uploadé';
                    setTimeout(() => {
                        uploadBtn.textContent = originalText;
                        uploadBtn.disabled = false;
                    }, 2000);
                }
            } else {
                alert('Erreur lors de l\'upload: ' + (data.error || 'Erreur inconnue'));
                if (uploadBtn) {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = originalText;
                }
            }
        } catch (error) {
            console.error('Erreur lors de l\'upload de la vidéo:', error);
            alert('Erreur lors de l\'upload de la vidéo');
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.textContent = originalText;
            }
        }
    }
}

// Exposer globalement pour utilisation dans les vues
window.CourseLessonEditor = CourseLessonEditor;

export default CourseLessonEditor;
