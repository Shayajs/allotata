/**
 * Allo Tata - Éditeur de Site Web Vitrine
 * Éditeur visuel avec drag & drop, édition inline, et sauvegarde automatique
 */

import Sortable from 'sortablejs';

class SiteWebEditor {
    constructor(options = {}) {
        this.entrepriseSlug = options.slug;
        this.csrfToken = options.csrfToken;
        this.content = options.initialContent || this.getDefaultContent();
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
     * Structure par défaut du contenu
     */
    getDefaultContent() {
        return {
            theme: {
                colors: {
                    primary: '#22c55e',
                    secondary: '#f97316',
                    accent: '#3b82f6',
                    background: '#ffffff',
                    text: '#1e293b'
                },
                fonts: {
                    heading: 'Poppins',
                    body: 'Inter'
                },
                buttons: {
                    style: 'rounded',
                    shadow: true
                }
            },
            blocks: [],
            version: 1,
            lastSaved: null
        };
    }

    /**
     * Initialisation de l'éditeur
     */
    init() {
        this.editorContainer = document.getElementById('site-web-editor');
        this.blocksContainer = document.getElementById('blocks-container');
        this.sidebar = document.getElementById('editor-sidebar');
        this.statusIndicator = document.getElementById('save-status');

        if (!this.editorContainer) {
            console.error('Éditeur non trouvé');
            return;
        }

        this.initSortable();
        this.initEventListeners();
        this.initInlineEditing();
        this.applyTheme();
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
            handle: '.block-drag-handle',
            ghostClass: 'block-ghost',
            chosenClass: 'block-chosen',
            dragClass: 'block-drag',
            onEnd: (evt) => {
                this.reorderBlocks(evt.oldIndex, evt.newIndex);
            }
        });

        // Sortable pour la sidebar (ajouter des blocs)
        const blocksList = document.getElementById('available-blocks');
        if (blocksList) {
            new Sortable(blocksList, {
                group: {
                    name: 'blocks',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150,
                onEnd: (evt) => {
                    if (evt.to === this.blocksContainer) {
                        const blockType = evt.item.dataset.blockType;
                        const newIndex = evt.newIndex;
                        evt.item.remove(); // Retirer le clone
                        this.addBlock(blockType, newIndex);
                    }
                }
            });
        }
    }

    /**
     * Initialiser les événements
     */
    initEventListeners() {
        // Clic sur un bloc pour le sélectionner
        this.editorContainer.addEventListener('click', (e) => {
            const block = e.target.closest('.editor-block');
            if (block) {
                this.selectBlock(block.dataset.blockId);
            }
        });

        // Boutons d'action des blocs
        this.editorContainer.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]');
            if (!action) return;

            const blockEl = action.closest('.editor-block');
            if (!blockEl) return;

            const blockId = blockEl.dataset.blockId;
            const actionType = action.dataset.action;

            switch (actionType) {
                case 'edit':
                    this.openBlockEditor(blockId);
                    break;
                case 'duplicate':
                    this.duplicateBlock(blockId);
                    break;
                case 'delete':
                    this.deleteBlock(blockId);
                    break;
                case 'move-up':
                    this.moveBlock(blockId, -1);
                    break;
                case 'move-down':
                    this.moveBlock(blockId, 1);
                    break;
            }
        });

        // Panneau de thème
        document.querySelectorAll('[data-theme-color]').forEach(input => {
            input.addEventListener('input', (e) => {
                const colorKey = e.target.dataset.themeColor;
                this.updateThemeColor(colorKey, e.target.value);
            });
        });

        document.querySelectorAll('[data-theme-font]').forEach(select => {
            select.addEventListener('change', (e) => {
                const fontKey = e.target.dataset.themeFont;
                this.updateThemeFont(fontKey, e.target.value);
            });
        });

        // Boutons d'ajout de bloc
        document.querySelectorAll('[data-add-block]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const blockType = e.target.closest('[data-add-block]').dataset.addBlock;
                this.addBlock(blockType);
            });
        });

        // Bouton de sauvegarde manuelle
        const saveBtn = document.getElementById('manual-save-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveContent(false));
        }

        // Boutons de thème prédéfini
        document.querySelectorAll('[data-preset-theme]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const themeName = e.target.closest('[data-preset-theme]').dataset.presetTheme;
                this.applyPresetTheme(themeName);
            });
        });
    }

    /**
     * Initialiser l'édition inline (contenteditable)
     */
    initInlineEditing() {
        this.editorContainer.addEventListener('dblclick', (e) => {
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
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.finishInlineEdit();
            }
            if (e.key === 'Escape') {
                element.textContent = element.dataset.originalContent;
                this.finishInlineEdit();
            }
        });

        // Sauvegarder le contenu original
        element.dataset.originalContent = element.textContent;
    }

    /**
     * Terminer l'édition inline
     */
    finishInlineEdit() {
        if (!this.editingElement) return;

        const element = this.editingElement;
        element.setAttribute('contenteditable', 'false');
        element.classList.remove('editing');

        const blockEl = element.closest('.editor-block');
        if (blockEl) {
            const blockId = blockEl.dataset.blockId;
            const field = element.dataset.editable;
            const newValue = element.innerHTML;

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
            animation: 'fadeIn'
        };

        if (index !== null && index >= 0 && index <= this.content.blocks.length) {
            this.content.blocks.splice(index, 0, newBlock);
        } else {
            this.content.blocks.push(newBlock);
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
            hero: {
                title: 'Bienvenue sur notre site',
                subtitle: 'Découvrez nos services et notre expertise',
                buttonText: 'En savoir plus',
                buttonLink: '#contact',
                backgroundImage: null,
                overlay: true
            },
            text: {
                html: '<p>Cliquez pour modifier ce texte. Vous pouvez ajouter du contenu riche avec des titres, des listes, et plus encore.</p>'
            },
            image: {
                src: null,
                alt: 'Image',
                caption: ''
            },
            gallery: {
                images: [],
                columns: 3
            },
            contact: {
                title: 'Contactez-nous',
                showEmail: true,
                showPhone: true,
                showAddress: true,
                showMap: false
            },
            video: {
                url: '',
                type: 'youtube',
                autoplay: false
            },
            services: {
                title: 'Nos Services',
                items: []
            },
            testimonials: {
                title: 'Ce que disent nos clients',
                items: []
            },
            cta: {
                title: 'Prêt à commencer ?',
                subtitle: 'Contactez-nous dès aujourd\'hui',
                buttonText: 'Nous contacter',
                buttonLink: '#contact'
            },
            divider: {
                style: 'line',
                icon: null
            },
            iframe: {
                src: '',
                height: 400
            },
            faq: {
                title: 'Questions fréquentes',
                items: []
            },
            team: {
                title: 'Notre équipe',
                members: []
            },
            stats: {
                items: [
                    { value: '100+', label: 'Clients satisfaits' },
                    { value: '5', label: 'Années d\'expérience' },
                    { value: '1000+', label: 'Projets réalisés' }
                ]
            },
            features: {
                title: 'Pourquoi nous choisir ?',
                items: []
            }
        };

        return defaults[type] || {};
    }

    /**
     * Paramètres par défaut selon le type de bloc
     */
    getDefaultBlockSettings(type) {
        const defaults = {
            hero: {
                height: 'large',
                alignment: 'center',
                overlayOpacity: 60
            },
            text: {
                alignment: 'left',
                maxWidth: 'prose'
            },
            image: {
                size: 'medium',
                rounded: true,
                shadow: true,
                lightbox: true
            },
            gallery: {
                gap: 'medium',
                rounded: true
            },
            contact: {
                layout: 'horizontal'
            },
            video: {
                aspectRatio: '16:9'
            },
            services: {
                layout: 'grid',
                columns: 3
            },
            testimonials: {
                layout: 'carousel',
                autoplay: true
            },
            cta: {
                style: 'gradient',
                alignment: 'center'
            },
            divider: {
                spacing: 'medium'
            },
            iframe: {
                fullWidth: true
            },
            faq: {
                style: 'accordion'
            },
            team: {
                layout: 'grid',
                columns: 3
            },
            stats: {
                animated: true,
                layout: 'horizontal'
            },
            features: {
                layout: 'grid',
                columns: 3
            }
        };

        return defaults[type] || {};
    }

    /**
     * Supprimer un bloc
     */
    deleteBlock(blockId) {
        if (!confirm('Supprimer ce bloc ?')) return;

        const index = this.content.blocks.findIndex(b => b.id === blockId);
        if (index !== -1) {
            this.content.blocks.splice(index, 1);
            this.renderBlocks();
            this.scheduleAutoSave();
        }
    }

    /**
     * Dupliquer un bloc
     */
    duplicateBlock(blockId) {
        const index = this.content.blocks.findIndex(b => b.id === blockId);
        if (index === -1) return;

        const originalBlock = this.content.blocks[index];
        const newBlock = {
            ...JSON.parse(JSON.stringify(originalBlock)),
            id: this.generateUUID()
        };

        this.content.blocks.splice(index + 1, 0, newBlock);
        this.renderBlocks();
        this.scheduleAutoSave();
        this.selectBlock(newBlock.id);
    }

    /**
     * Déplacer un bloc
     */
    moveBlock(blockId, direction) {
        const index = this.content.blocks.findIndex(b => b.id === blockId);
        if (index === -1) return;

        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= this.content.blocks.length) return;

        const [block] = this.content.blocks.splice(index, 1);
        this.content.blocks.splice(newIndex, 0, block);
        this.renderBlocks();
        this.scheduleAutoSave();
    }

    /**
     * Réordonner les blocs après drag & drop
     */
    reorderBlocks(oldIndex, newIndex) {
        if (oldIndex === newIndex) return;

        const [block] = this.content.blocks.splice(oldIndex, 1);
        this.content.blocks.splice(newIndex, 0, block);
        this.scheduleAutoSave();
    }

    /**
     * Sélectionner un bloc
     */
    selectBlock(blockId) {
        // Désélectionner l'ancien bloc
        document.querySelectorAll('.editor-block.selected').forEach(el => {
            el.classList.remove('selected');
        });

        this.activeBlockId = blockId;

        // Sélectionner le nouveau bloc
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (blockEl) {
            blockEl.classList.add('selected');
        }

        // Mettre à jour le panneau de propriétés
        this.updatePropertiesPanel(blockId);
    }

    /**
     * Mettre à jour le panneau de propriétés
     */
    updatePropertiesPanel(blockId) {
        const panel = document.getElementById('block-properties');
        if (!panel) return;

        const block = this.content.blocks.find(b => b.id === blockId);
        if (!block) {
            panel.innerHTML = '<p class="text-slate-500 text-center py-8">Sélectionnez un bloc pour voir ses propriétés</p>';
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
                this.updateBlockSetting(blockId, setting, value);
            });
        });

        panel.querySelectorAll('[data-content]').forEach(input => {
            input.addEventListener('input', (e) => {
                const field = e.target.dataset.content;
                this.updateBlockContent(blockId, field, e.target.value);
            });
        });
    }

    /**
     * Générer le formulaire de propriétés
     */
    generatePropertiesForm(block) {
        const typeLabels = {
            hero: 'En-tête Hero',
            text: 'Texte',
            image: 'Image',
            gallery: 'Galerie',
            contact: 'Contact',
            video: 'Vidéo',
            services: 'Services',
            testimonials: 'Témoignages',
            cta: 'Appel à l\'action',
            divider: 'Séparateur',
            iframe: 'Iframe',
            faq: 'FAQ',
            team: 'Équipe',
            stats: 'Statistiques',
            features: 'Fonctionnalités'
        };

        let html = `
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="font-semibold text-slate-900 dark:text-white">${typeLabels[block.type] || block.type}</h3>
            </div>
            <div class="p-4 space-y-4">
        `;

        // Générer les champs selon le type de bloc
        html += this.generateBlockFields(block);

        html += '</div>';
        return html;
    }

    /**
     * Générer les champs d'édition pour un bloc
     */
    generateBlockFields(block) {
        let html = '';
        
        // Animation
        html += `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Animation</label>
                <select data-setting="animation" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="none" ${block.animation === 'none' ? 'selected' : ''}>Aucune</option>
                    <option value="fadeIn" ${block.animation === 'fadeIn' ? 'selected' : ''}>Fondu</option>
                    <option value="slideUp" ${block.animation === 'slideUp' ? 'selected' : ''}>Glissement haut</option>
                    <option value="slideLeft" ${block.animation === 'slideLeft' ? 'selected' : ''}>Glissement gauche</option>
                    <option value="zoomIn" ${block.animation === 'zoomIn' ? 'selected' : ''}>Zoom</option>
                </select>
            </div>
        `;

        // Champs spécifiques selon le type
        switch (block.type) {
            case 'hero':
                html += this.generateHeroFields(block);
                break;
            case 'text':
                html += this.generateTextFields(block);
                break;
            case 'image':
                html += this.generateImageFields(block);
                break;
            case 'gallery':
                html += this.generateGalleryFields(block);
                break;
            case 'contact':
                html += this.generateContactFields(block);
                break;
            case 'cta':
                html += this.generateCtaFields(block);
                break;
            case 'divider':
                html += this.generateDividerFields(block);
                break;
            case 'video':
                html += this.generateVideoFields(block);
                break;
        }

        return html;
    }

    generateHeroFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre</label>
                <input type="text" data-content="title" value="${this.escapeHtml(block.content.title || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sous-titre</label>
                <input type="text" data-content="subtitle" value="${this.escapeHtml(block.content.subtitle || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texte du bouton</label>
                <input type="text" data-content="buttonText" value="${this.escapeHtml(block.content.buttonText || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lien du bouton</label>
                <input type="text" data-content="buttonLink" value="${this.escapeHtml(block.content.buttonLink || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hauteur</label>
                <select data-setting="height" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="small" ${block.settings.height === 'small' ? 'selected' : ''}>Petite</option>
                    <option value="medium" ${block.settings.height === 'medium' ? 'selected' : ''}>Moyenne</option>
                    <option value="large" ${block.settings.height === 'large' ? 'selected' : ''}>Grande</option>
                    <option value="full" ${block.settings.height === 'full' ? 'selected' : ''}>Plein écran</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Alignement</label>
                <select data-setting="alignment" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="left" ${block.settings.alignment === 'left' ? 'selected' : ''}>Gauche</option>
                    <option value="center" ${block.settings.alignment === 'center' ? 'selected' : ''}>Centre</option>
                    <option value="right" ${block.settings.alignment === 'right' ? 'selected' : ''}>Droite</option>
                </select>
            </div>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-content="overlay" ${block.content.overlay ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Overlay sombre</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Image de fond</label>
                <button type="button" onclick="window.siteWebEditor.uploadBlockImage('${block.id}', 'backgroundImage')" class="w-full px-3 py-2 border border-dashed border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-400 hover:border-green-500 hover:text-green-500 text-sm transition">
                    Choisir une image
                </button>
            </div>
        `;
    }

    generateTextFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Alignement</label>
                <select data-setting="alignment" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="left" ${block.settings.alignment === 'left' ? 'selected' : ''}>Gauche</option>
                    <option value="center" ${block.settings.alignment === 'center' ? 'selected' : ''}>Centre</option>
                    <option value="right" ${block.settings.alignment === 'right' ? 'selected' : ''}>Droite</option>
                </select>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Double-cliquez sur le texte pour le modifier directement.</p>
        `;
    }

    generateImageFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Image</label>
                <button type="button" onclick="window.siteWebEditor.uploadBlockImage('${block.id}', 'src')" class="w-full px-3 py-2 border border-dashed border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-400 hover:border-green-500 hover:text-green-500 text-sm transition">
                    ${block.content.src ? 'Changer l\'image' : 'Ajouter une image'}
                </button>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texte alternatif</label>
                <input type="text" data-content="alt" value="${this.escapeHtml(block.content.alt || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Légende</label>
                <input type="text" data-content="caption" value="${this.escapeHtml(block.content.caption || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Taille</label>
                <select data-setting="size" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="small" ${block.settings.size === 'small' ? 'selected' : ''}>Petite</option>
                    <option value="medium" ${block.settings.size === 'medium' ? 'selected' : ''}>Moyenne</option>
                    <option value="large" ${block.settings.size === 'large' ? 'selected' : ''}>Grande</option>
                    <option value="full" ${block.settings.size === 'full' ? 'selected' : ''}>Pleine largeur</option>
                </select>
            </div>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-setting="rounded" ${block.settings.rounded ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Arrondi</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-setting="shadow" ${block.settings.shadow ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Ombre</span>
                </label>
            </div>
        `;
    }

    generateGalleryFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonnes</label>
                <select data-content="columns" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="2" ${block.content.columns === 2 ? 'selected' : ''}>2 colonnes</option>
                    <option value="3" ${block.content.columns === 3 ? 'selected' : ''}>3 colonnes</option>
                    <option value="4" ${block.content.columns === 4 ? 'selected' : ''}>4 colonnes</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Espacement</label>
                <select data-setting="gap" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="small" ${block.settings.gap === 'small' ? 'selected' : ''}>Petit</option>
                    <option value="medium" ${block.settings.gap === 'medium' ? 'selected' : ''}>Moyen</option>
                    <option value="large" ${block.settings.gap === 'large' ? 'selected' : ''}>Grand</option>
                </select>
            </div>
            <div>
                <button type="button" onclick="window.siteWebEditor.openGalleryManager('${block.id}')" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                    Gérer les images (${block.content.images?.length || 0})
                </button>
            </div>
        `;
    }

    generateContactFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre</label>
                <input type="text" data-content="title" value="${this.escapeHtml(block.content.title || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-content="showEmail" ${block.content.showEmail ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Afficher l'email</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-content="showPhone" ${block.content.showPhone ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Afficher le téléphone</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" data-content="showAddress" ${block.content.showAddress ? 'checked' : ''} class="rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Afficher l'adresse</span>
                </label>
            </div>
        `;
    }

    generateCtaFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre</label>
                <input type="text" data-content="title" value="${this.escapeHtml(block.content.title || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sous-titre</label>
                <input type="text" data-content="subtitle" value="${this.escapeHtml(block.content.subtitle || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texte du bouton</label>
                <input type="text" data-content="buttonText" value="${this.escapeHtml(block.content.buttonText || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lien</label>
                <input type="text" data-content="buttonLink" value="${this.escapeHtml(block.content.buttonLink || '')}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Style</label>
                <select data-setting="style" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="simple" ${block.settings.style === 'simple' ? 'selected' : ''}>Simple</option>
                    <option value="gradient" ${block.settings.style === 'gradient' ? 'selected' : ''}>Dégradé</option>
                    <option value="outlined" ${block.settings.style === 'outlined' ? 'selected' : ''}>Contour</option>
                </select>
            </div>
        `;
    }

    generateDividerFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Style</label>
                <select data-content="style" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="line" ${block.content.style === 'line' ? 'selected' : ''}>Ligne</option>
                    <option value="dashed" ${block.content.style === 'dashed' ? 'selected' : ''}>Pointillés</option>
                    <option value="dots" ${block.content.style === 'dots' ? 'selected' : ''}>Points</option>
                    <option value="space" ${block.content.style === 'space' ? 'selected' : ''}>Espace</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Espacement</label>
                <select data-setting="spacing" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="small" ${block.settings.spacing === 'small' ? 'selected' : ''}>Petit</option>
                    <option value="medium" ${block.settings.spacing === 'medium' ? 'selected' : ''}>Moyen</option>
                    <option value="large" ${block.settings.spacing === 'large' ? 'selected' : ''}>Grand</option>
                </select>
            </div>
        `;
    }

    generateVideoFields(block) {
        return `
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL de la vidéo</label>
                <input type="url" data-content="url" value="${this.escapeHtml(block.content.url || '')}" placeholder="https://youtube.com/watch?v=..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <p class="text-xs text-slate-500 mt-1">YouTube, Vimeo, ou lien direct</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Format</label>
                <select data-setting="aspectRatio" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="16:9" ${block.settings.aspectRatio === '16:9' ? 'selected' : ''}>16:9 (Widescreen)</option>
                    <option value="4:3" ${block.settings.aspectRatio === '4:3' ? 'selected' : ''}>4:3 (Standard)</option>
                    <option value="1:1" ${block.settings.aspectRatio === '1:1' ? 'selected' : ''}>1:1 (Carré)</option>
                </select>
            </div>
        `;
    }

    /**
     * Mettre à jour le contenu d'un bloc
     */
    updateBlockContent(blockId, field, value) {
        const block = this.content.blocks.find(b => b.id === blockId);
        if (!block) return;

        // Gérer les champs imbriqués (ex: "images.0.src")
        const parts = field.split('.');
        let target = block.content;
        for (let i = 0; i < parts.length - 1; i++) {
            if (!target[parts[i]]) target[parts[i]] = {};
            target = target[parts[i]];
        }
        target[parts[parts.length - 1]] = value;

        this.renderBlock(blockId);
        this.scheduleAutoSave();
    }

    /**
     * Mettre à jour un paramètre d'un bloc
     */
    updateBlockSetting(blockId, setting, value) {
        const block = this.content.blocks.find(b => b.id === blockId);
        if (!block) return;

        if (setting === 'animation') {
            block.animation = value;
        } else {
            block.settings[setting] = value;
        }

        this.renderBlock(blockId);
        this.scheduleAutoSave();
    }

    /**
     * Mettre à jour une couleur du thème
     */
    updateThemeColor(colorKey, value) {
        this.content.theme.colors[colorKey] = value;
        this.applyTheme();
        this.scheduleAutoSave();
    }

    /**
     * Mettre à jour une police du thème
     */
    updateThemeFont(fontKey, value) {
        this.content.theme.fonts[fontKey] = value;
        this.applyTheme();
        this.scheduleAutoSave();
    }

    /**
     * Appliquer un thème prédéfini
     */
    applyPresetTheme(themeName) {
        const presets = {
            moderne: {
                colors: { primary: '#22c55e', secondary: '#f97316', accent: '#3b82f6', background: '#ffffff', text: '#1e293b' },
                fonts: { heading: 'Poppins', body: 'Inter' },
                buttons: { style: 'rounded', shadow: true }
            },
            classique: {
                colors: { primary: '#1e40af', secondary: '#b45309', accent: '#6366f1', background: '#fafaf9', text: '#292524' },
                fonts: { heading: 'Playfair Display', body: 'Lora' },
                buttons: { style: 'square', shadow: false }
            },
            bold: {
                colors: { primary: '#dc2626', secondary: '#facc15', accent: '#000000', background: '#ffffff', text: '#000000' },
                fonts: { heading: 'Oswald', body: 'Roboto' },
                buttons: { style: 'square', shadow: true }
            },
            nature: {
                colors: { primary: '#15803d', secondary: '#a16207', accent: '#0d9488', background: '#f0fdf4', text: '#14532d' },
                fonts: { heading: 'Merriweather', body: 'Source Sans Pro' },
                buttons: { style: 'rounded', shadow: false }
            },
            tech: {
                colors: { primary: '#7c3aed', secondary: '#06b6d4', accent: '#f43f5e', background: '#0f172a', text: '#e2e8f0' },
                fonts: { heading: 'Space Grotesk', body: 'IBM Plex Sans' },
                buttons: { style: 'pill', shadow: true }
            },
            minimaliste: {
                colors: { primary: '#171717', secondary: '#737373', accent: '#171717', background: '#ffffff', text: '#171717' },
                fonts: { heading: 'DM Sans', body: 'DM Sans' },
                buttons: { style: 'square', shadow: false }
            }
        };

        if (presets[themeName]) {
            this.content.theme = JSON.parse(JSON.stringify(presets[themeName]));
            this.applyTheme();
            this.updateThemeInputs();
            this.scheduleAutoSave();
        }
    }

    /**
     * Appliquer le thème aux variables CSS
     */
    applyTheme() {
        const theme = this.content.theme;
        const root = document.documentElement;

        // Couleurs
        Object.entries(theme.colors).forEach(([key, value]) => {
            root.style.setProperty(`--site-${key}`, value);
        });

        // Polices
        root.style.setProperty('--site-font-heading', theme.fonts.heading);
        root.style.setProperty('--site-font-body', theme.fonts.body);

        // Style des boutons
        root.style.setProperty('--site-button-radius', 
            theme.buttons.style === 'rounded' ? '0.5rem' : 
            theme.buttons.style === 'pill' ? '9999px' : '0');
        root.style.setProperty('--site-button-shadow', 
            theme.buttons.shadow ? '0 4px 6px -1px rgba(0, 0, 0, 0.1)' : 'none');
    }

    /**
     * Mettre à jour les inputs du thème
     */
    updateThemeInputs() {
        const theme = this.content.theme;

        // Couleurs
        document.querySelectorAll('[data-theme-color]').forEach(input => {
            const key = input.dataset.themeColor;
            if (theme.colors[key]) {
                input.value = theme.colors[key];
            }
        });

        // Polices
        document.querySelectorAll('[data-theme-font]').forEach(select => {
            const key = select.dataset.themeFont;
            if (theme.fonts[key]) {
                select.value = theme.fonts[key];
            }
        });
    }

    /**
     * Rendre tous les blocs
     */
    renderBlocks() {
        if (!this.blocksContainer) return;

        this.blocksContainer.innerHTML = this.content.blocks.map(block => 
            this.renderBlockHtml(block)
        ).join('');

        // Réinitialiser le sortable
        if (this.sortableInstance) {
            this.sortableInstance.destroy();
        }
        this.initSortable();
    }

    /**
     * Rendre un seul bloc
     */
    renderBlock(blockId) {
        const block = this.content.blocks.find(b => b.id === blockId);
        if (!block) return;

        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (blockEl) {
            blockEl.outerHTML = this.renderBlockHtml(block);
        }
    }

    /**
     * Générer le HTML d'un bloc
     */
    renderBlockHtml(block) {
        const isSelected = block.id === this.activeBlockId;
        
        return `
            <div class="editor-block ${isSelected ? 'selected' : ''}" data-block-id="${block.id}" data-block-type="${block.type}">
                <div class="block-toolbar">
                    <button type="button" class="block-drag-handle" title="Déplacer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="move-up" title="Monter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="move-down" title="Descendre">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="duplicate" title="Dupliquer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                    <button type="button" data-action="delete" title="Supprimer" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="block-content">
                    ${this.renderBlockContent(block)}
                </div>
            </div>
        `;
    }

    /**
     * Rendre le contenu d'un bloc selon son type
     */
    renderBlockContent(block) {
        switch (block.type) {
            case 'hero':
                return this.renderHeroBlock(block);
            case 'text':
                return this.renderTextBlock(block);
            case 'image':
                return this.renderImageBlock(block);
            case 'gallery':
                return this.renderGalleryBlock(block);
            case 'contact':
                return this.renderContactBlock(block);
            case 'video':
                return this.renderVideoBlock(block);
            case 'services':
                return this.renderServicesBlock(block);
            case 'testimonials':
                return this.renderTestimonialsBlock(block);
            case 'cta':
                return this.renderCtaBlock(block);
            case 'divider':
                return this.renderDividerBlock(block);
            case 'iframe':
                return this.renderIframeBlock(block);
            case 'faq':
                return this.renderFaqBlock(block);
            case 'team':
                return this.renderTeamBlock(block);
            case 'stats':
                return this.renderStatsBlock(block);
            case 'features':
                return this.renderFeaturesBlock(block);
            default:
                return `<div class="p-4 text-center text-slate-500">Bloc inconnu: ${block.type}</div>`;
        }
    }

    renderHeroBlock(block) {
        const heightClass = {
            small: 'min-h-[300px]',
            medium: 'min-h-[400px]',
            large: 'min-h-[500px]',
            full: 'min-h-screen'
        }[block.settings.height] || 'min-h-[400px]';

        const alignClass = {
            left: 'text-left items-start',
            center: 'text-center items-center',
            right: 'text-right items-end'
        }[block.settings.alignment] || 'text-center items-center';

        const bgStyle = block.content.backgroundImage 
            ? `background-image: url('${block.content.backgroundImage}'); background-size: cover; background-position: center;`
            : 'background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));';

        return `
            <div class="${heightClass} relative flex flex-col justify-center ${alignClass} p-8 md:p-16 rounded-lg overflow-hidden" style="${bgStyle}">
                ${block.content.overlay ? '<div class="absolute inset-0 bg-black/50"></div>' : ''}
                <div class="relative z-10 max-w-3xl">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4" data-editable="title" style="font-family: var(--site-font-heading);">
                        ${this.escapeHtml(block.content.title)}
                    </h1>
                    <p class="text-xl md:text-2xl text-white/90 mb-8" data-editable="subtitle" style="font-family: var(--site-font-body);">
                        ${this.escapeHtml(block.content.subtitle)}
                    </p>
                    ${block.content.buttonText ? `
                        <a href="${this.escapeHtml(block.content.buttonLink)}" class="inline-block px-8 py-4 text-lg font-semibold text-white transition" style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                            ${this.escapeHtml(block.content.buttonText)}
                        </a>
                    ` : ''}
                </div>
            </div>
        `;
    }

    renderTextBlock(block) {
        const alignClass = {
            left: 'text-left',
            center: 'text-center',
            right: 'text-right'
        }[block.settings.alignment] || 'text-left';

        return `
            <div class="py-8 px-4 ${alignClass}">
                <div class="prose prose-lg dark:prose-invert max-w-none" data-editable="html" style="font-family: var(--site-font-body);">
                    ${block.content.html}
                </div>
            </div>
        `;
    }

    renderImageBlock(block) {
        const sizeClass = {
            small: 'max-w-sm',
            medium: 'max-w-2xl',
            large: 'max-w-4xl',
            full: 'max-w-full'
        }[block.settings.size] || 'max-w-2xl';

        if (!block.content.src) {
            return `
                <div class="py-8 flex justify-center">
                    <div class="${sizeClass} w-full aspect-video bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center cursor-pointer hover:bg-slate-300 dark:hover:bg-slate-600 transition" onclick="window.siteWebEditor.uploadBlockImage('${block.id}', 'src')">
                        <div class="text-center text-slate-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>Cliquez pour ajouter une image</p>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="py-8 flex justify-center">
                <figure class="${sizeClass} w-full">
                    <img 
                        src="${this.escapeHtml(block.content.src)}" 
                        alt="${this.escapeHtml(block.content.alt)}"
                        class="w-full h-auto ${block.settings.rounded ? 'rounded-lg' : ''} ${block.settings.shadow ? 'shadow-lg' : ''}"
                    >
                    ${block.content.caption ? `
                        <figcaption class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400" data-editable="caption">
                            ${this.escapeHtml(block.content.caption)}
                        </figcaption>
                    ` : ''}
                </figure>
            </div>
        `;
    }

    renderGalleryBlock(block) {
        const columns = block.content.columns || 3;
        const gap = { small: 'gap-2', medium: 'gap-4', large: 'gap-6' }[block.settings.gap] || 'gap-4';

        if (!block.content.images || block.content.images.length === 0) {
            return `
                <div class="py-8">
                    <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-8 text-center cursor-pointer hover:border-green-500 transition" onclick="window.siteWebEditor.openGalleryManager('${block.id}')">
                        <svg class="w-12 h-12 mx-auto mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-slate-500 dark:text-slate-400">Cliquez pour ajouter des images</p>
                    </div>
                </div>
            `;
        }

        return `
            <div class="py-8">
                <div class="grid grid-cols-${columns} ${gap}">
                    ${block.content.images.map((img, i) => `
                        <div class="aspect-square overflow-hidden ${block.settings.rounded ? 'rounded-lg' : ''}">
                            <img src="${this.escapeHtml(img.src)}" alt="${this.escapeHtml(img.alt || '')}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    renderContactBlock(block) {
        return `
            <div class="py-12 px-4">
                <div class="max-w-2xl mx-auto text-center">
                    <h2 class="text-3xl font-bold mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                        ${this.escapeHtml(block.content.title)}
                    </h2>
                    <div class="space-y-4">
                        ${block.content.showEmail ? `
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-lg" style="color: var(--site-text);">[Email de l'entreprise]</span>
                            </div>
                        ` : ''}
                        ${block.content.showPhone ? `
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="text-lg" style="color: var(--site-text);">[Téléphone de l'entreprise]</span>
                            </div>
                        ` : ''}
                        ${block.content.showAddress ? `
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-5 h-5" style="color: var(--site-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-lg" style="color: var(--site-text);">[Adresse de l'entreprise]</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    renderVideoBlock(block) {
        if (!block.content.url) {
            return `
                <div class="py-8">
                    <div class="aspect-video bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                        <div class="text-center text-slate-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>Ajoutez une URL de vidéo dans les propriétés</p>
                        </div>
                    </div>
                </div>
            `;
        }

        const embedUrl = this.getVideoEmbedUrl(block.content.url);
        
        return `
            <div class="py-8">
                <div class="aspect-video rounded-lg overflow-hidden">
                    <iframe src="${embedUrl}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        `;
    }

    renderServicesBlock(block) {
        return `
            <div class="py-12 px-4">
                <h2 class="text-3xl font-bold text-center mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <p class="text-center text-slate-500 dark:text-slate-400">Les services seront automatiquement chargés depuis votre entreprise.</p>
            </div>
        `;
    }

    renderTestimonialsBlock(block) {
        return `
            <div class="py-12 px-4" style="background: var(--site-background);">
                <h2 class="text-3xl font-bold text-center mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <p class="text-center text-slate-500 dark:text-slate-400">Les avis seront automatiquement chargés depuis votre entreprise.</p>
            </div>
        `;
    }

    renderCtaBlock(block) {
        const bgStyle = block.settings.style === 'gradient' 
            ? 'background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));'
            : block.settings.style === 'outlined'
            ? 'background: transparent; border: 2px solid var(--site-primary);'
            : 'background: var(--site-primary);';

        return `
            <div class="py-16 px-8 rounded-lg text-center" style="${bgStyle}">
                <h2 class="text-3xl font-bold mb-4 ${block.settings.style === 'outlined' ? '' : 'text-white'}" data-editable="title" style="font-family: var(--site-font-heading); ${block.settings.style === 'outlined' ? 'color: var(--site-text);' : ''}">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <p class="text-xl mb-8 ${block.settings.style === 'outlined' ? '' : 'text-white/90'}" data-editable="subtitle" style="${block.settings.style === 'outlined' ? 'color: var(--site-text);' : ''}">
                    ${this.escapeHtml(block.content.subtitle)}
                </p>
                <a href="${this.escapeHtml(block.content.buttonLink)}" class="inline-block px-8 py-4 text-lg font-semibold transition" style="background: ${block.settings.style === 'outlined' ? 'var(--site-primary)' : 'white'}; color: ${block.settings.style === 'outlined' ? 'white' : 'var(--site-primary)'}; border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                    ${this.escapeHtml(block.content.buttonText)}
                </a>
            </div>
        `;
    }

    renderDividerBlock(block) {
        const spacingClass = { small: 'py-4', medium: 'py-8', large: 'py-12' }[block.settings.spacing] || 'py-8';
        
        let dividerHtml = '';
        switch (block.content.style) {
            case 'line':
                dividerHtml = '<hr class="border-slate-300 dark:border-slate-600">';
                break;
            case 'dashed':
                dividerHtml = '<hr class="border-slate-300 dark:border-slate-600 border-dashed">';
                break;
            case 'dots':
                dividerHtml = '<div class="flex justify-center gap-2"><span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span><span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span><span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span></div>';
                break;
            case 'space':
                dividerHtml = '';
                break;
        }

        return `<div class="${spacingClass}">${dividerHtml}</div>`;
    }

    renderIframeBlock(block) {
        if (!block.content.src) {
            return `
                <div class="py-8">
                    <div class="bg-slate-200 dark:bg-slate-700 rounded-lg p-8 text-center" style="height: ${block.content.height || 400}px;">
                        <p class="text-slate-500 dark:text-slate-400">Ajoutez une URL dans les propriétés</p>
                    </div>
                </div>
            `;
        }

        return `
            <div class="py-8">
                <iframe src="${this.escapeHtml(block.content.src)}" class="w-full rounded-lg" style="height: ${block.content.height || 400}px;" frameborder="0"></iframe>
            </div>
        `;
    }

    renderFaqBlock(block) {
        return `
            <div class="py-12 px-4">
                <h2 class="text-3xl font-bold text-center mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <div class="max-w-2xl mx-auto">
                    <p class="text-center text-slate-500 dark:text-slate-400">Ajoutez des questions/réponses dans les propriétés du bloc.</p>
                </div>
            </div>
        `;
    }

    renderTeamBlock(block) {
        return `
            <div class="py-12 px-4">
                <h2 class="text-3xl font-bold text-center mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <p class="text-center text-slate-500 dark:text-slate-400">Ajoutez des membres d'équipe dans les propriétés du bloc.</p>
            </div>
        `;
    }

    renderStatsBlock(block) {
        return `
            <div class="py-12 px-4">
                <div class="flex flex-wrap justify-center gap-8 md:gap-16">
                    ${block.content.items.map(stat => `
                        <div class="text-center">
                            <div class="text-4xl md:text-5xl font-bold mb-2" style="color: var(--site-primary); font-family: var(--site-font-heading);">
                                ${this.escapeHtml(stat.value)}
                            </div>
                            <div class="text-slate-600 dark:text-slate-400" style="font-family: var(--site-font-body);">
                                ${this.escapeHtml(stat.label)}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    renderFeaturesBlock(block) {
        return `
            <div class="py-12 px-4">
                <h2 class="text-3xl font-bold text-center mb-8" data-editable="title" style="font-family: var(--site-font-heading); color: var(--site-text);">
                    ${this.escapeHtml(block.content.title)}
                </h2>
                <p class="text-center text-slate-500 dark:text-slate-400">Ajoutez des fonctionnalités dans les propriétés du bloc.</p>
            </div>
        `;
    }

    /**
     * Obtenir l'URL d'embed pour une vidéo
     */
    getVideoEmbedUrl(url) {
        // YouTube
        const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?/]+)/);
        if (youtubeMatch) {
            return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
        }

        // Vimeo
        const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) {
            return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
        }

        return url;
    }

    /**
     * Échapper le HTML
     */
    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Planifier une sauvegarde automatique
     */
    scheduleAutoSave() {
        this.hasUnsavedChanges = true;
        this.updateStatus('unsaved');

        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveContent(true);
        }, this.saveDebounceMs);
    }

    /**
     * Sauvegarder le contenu
     */
    async saveContent(isAutoSave = true) {
        if (this.isSaving) return;

        this.isSaving = true;
        this.updateStatus('saving');

        try {
            const response = await fetch(`/w/${this.entrepriseSlug}/content`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    content: this.content,
                    is_auto_save: isAutoSave
                })
            });

            if (!response.ok) {
                throw new Error('Erreur de sauvegarde');
            }

            const data = await response.json();
            this.content.lastSaved = new Date().toISOString();
            this.hasUnsavedChanges = false;
            this.updateStatus('saved');

        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            this.updateStatus('error');
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Mettre à jour l'indicateur de statut
     */
    updateStatus(status) {
        if (!this.statusIndicator) return;

        const states = {
            saved: {
                text: 'Sauvegardé',
                class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
            },
            saving: {
                text: 'Enregistrement...',
                class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                icon: '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>'
            },
            unsaved: {
                text: 'Modifications non sauvegardées',
                class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            },
            error: {
                text: 'Erreur - Cliquez pour réessayer',
                class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 cursor-pointer',
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            }
        };

        const state = states[status] || states.saved;
        this.statusIndicator.className = `flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition ${state.class}`;
        this.statusIndicator.innerHTML = `${state.icon} <span>${state.text}</span>`;

        if (status === 'error') {
            this.statusIndicator.onclick = () => this.saveContent(false);
        } else {
            this.statusIndicator.onclick = null;
        }
    }

    /**
     * Ouvrir l'éditeur d'un bloc
     */
    openBlockEditor(blockId) {
        this.selectBlock(blockId);
        // Scroll vers le panneau de propriétés
        const panel = document.getElementById('block-properties');
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth' });
        }
    }

    /**
     * Upload d'image pour un bloc
     */
    uploadBlockImage(blockId, field) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('block_id', blockId);
            formData.append('field', field);

            try {
                const response = await fetch(`/w/${this.entrepriseSlug}/upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Upload failed');

                const data = await response.json();
                this.updateBlockContent(blockId, field, data.url);

            } catch (error) {
                console.error('Erreur upload:', error);
                alert('Erreur lors de l\'upload de l\'image');
            }
        };
        input.click();
    }

    /**
     * Ouvrir le gestionnaire de galerie
     */
    openGalleryManager(blockId) {
        // TODO: Implémenter un modal de gestion de galerie
        alert('Gestionnaire de galerie à venir');
    }
}

// Exporter pour utilisation globale
window.SiteWebEditor = SiteWebEditor;
