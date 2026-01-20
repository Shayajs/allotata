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
            }
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
                url: ''
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
                aspectRatio: '16:9'
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
            blockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Mettre à jour le panneau de propriétés
        this.updatePropertiesPanel();
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

        // Re-render le bloc
        this.renderBlock(blockId);
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

        // Re-render le bloc
        this.renderBlock(blockId);
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
                blockEl.outerHTML = newHtml;
                // Réinitialiser SortableJS
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                }
                this.initSortable();
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

        // Attacher les événements
        panel.querySelectorAll('[data-setting]').forEach(input => {
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
            <p class="text-slate-400 text-sm mb-4">Type: <strong>${block.type}</strong></p>
        `;

        // Ajouter les champs selon le type
        switch (block.type) {
            case 'heading':
                html += `
                    <label class="block text-sm text-slate-300 mb-2">Texte</label>
                    <input type="text" data-content="text" value="${block.content?.text || ''}" 
                           class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm mb-3">
                    <label class="block text-sm text-slate-300 mb-2">Niveau (1-6)</label>
                    <input type="number" data-content="level" min="1" max="6" value="${block.content?.level || 2}" 
                           class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm">
                `;
                break;
            case 'text':
                html += `
                    <label class="block text-sm text-slate-300 mb-2">Contenu HTML</label>
                    <textarea data-content="html" rows="6" 
                              class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm font-mono"
                              >${block.content?.html || ''}</textarea>
                `;
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
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    blocks: this.blocks,
                    ...lessonData
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.hasUnsavedChanges = false;
                this.updateStatus('saved');
                // Recharger la page pour afficher le statut publié
                window.location.reload();
            } else {
                this.updateStatus('error');
                alert('Erreur lors de la publication: ' + (data.error || 'Une erreur est survenue'));
            }
        } catch (error) {
            console.error('Erreur lors de la publication:', error);
            this.updateStatus('error');
            alert('Erreur lors de la publication.');
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
}

// Exposer globalement pour utilisation dans les vues
window.CourseLessonEditor = CourseLessonEditor;

export default CourseLessonEditor;
