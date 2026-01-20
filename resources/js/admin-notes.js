/**
 * Éditeur de notes collaboratif - CodeMirror 6
 * Architecture V1 : Coloration syntaxique + Curseurs en temps réel
 */

import { EditorView } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Instance Echo globale
let echoInstance = null;

function getEcho() {
    if (echoInstance) return echoInstance;
    
    const key = window.PUSHER_APP_KEY;
    const cluster = window.PUSHER_APP_CLUSTER || 'mt1';
    const csrf = document.querySelector('meta[name="csrf-token"]');
    
    if (!key || !csrf) return null;
    
    try {
        echoInstance = new Echo({
            broadcaster: 'pusher',
            key: key,
            cluster: cluster,
            forceTLS: true,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrf.content,
                },
            },
        });
    } catch (e) {
        console.error('Erreur initialisation Echo:', e);
        return null;
    }
    
    return echoInstance;
}

// Composant Alpine.js
function notesEditor(noteId) {
    return {
        noteId: parseInt(noteId),
        noteTitle: '',
        saveStatus: 'idle',
        
        editor: null,
        editorView: null,
        
        remoteCursors: {},
        userColors: {},
        collaborators: [],
        
        saveTimer: null,
        cursorTimer: null,
        isApplyingRemote: false,
        echo: null,
        channel: null,

        init() {
            // Récupérer le titre
            const titleInput = this.$el.querySelector('input[type="text"]');
            if (titleInput) {
                this.noteTitle = titleInput.value || '';
            }
            
            // Initialiser après le tick
            this.$nextTick(() => {
                this.setupEditor();
                this.setupWebSocket();
            });
        },

        setupEditor() {
            const container = document.getElementById('editor-container');
            if (!container) {
                setTimeout(() => this.setupEditor(), 100);
                return;
            }

            // Contenu initial
            const initialContent = window.noteContent || '';
            
            // Détecter le thème
            const isDark = document.documentElement.classList.contains('dark');

            try {
                // Créer l'état de l'éditeur
                const state = EditorState.create({
                    doc: initialContent,
                    extensions: [
                        basicSetup,
                        markdown(),
                        isDark ? oneDark : [],
                        EditorView.updateListener.of((update) => {
                            if (update.docChanged && !this.isApplyingRemote) {
                                const content = update.state.doc.toString();
                                this.queueSave(content);
                                this.queueCursorUpdate();
                                this.drawCursors();
                            }
                        }),
                        EditorView.theme({
                            '&': {
                                height: '100%',
                            },
                            '.cm-scroller': {
                                fontFamily: "'Fira Code', 'Monaco', 'Menlo', monospace",
                            },
                        }),
                    ],
                });

                // Créer la vue
                this.editorView = new EditorView({
                    state: state,
                    parent: container,
                });

                this.editor = this.editorView.state;

                // Mettre à jour les curseurs lors du scroll
                const scrollEl = this.editorView.scrollDOM;
                if (scrollEl) {
                    scrollEl.addEventListener('scroll', () => {
                        this.drawCursors();
                    });
                }

                // Observer le thème
                const themeObserver = new MutationObserver(() => {
                    // CodeMirror 6 gère mieux les thèmes, mais on peut ajouter une logique ici si besoin
                });
                themeObserver.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });

            } catch (e) {
                console.error('Erreur CodeMirror 6:', e);
            }
        },

        setupWebSocket() {
            this.echo = getEcho();
            if (!this.echo) return;

            try {
                this.channel = this.echo.private(`note.${this.noteId}`);
                
                // Écouter les mouvements de curseur
                this.channel.listen('.cursor.moved', (data) => {
                    if (data.user && data.user.id !== window.currentUserId && data.cursor) {
                        this.handleRemoteCursor(data.user, data.cursor);
                    }
                });
                
                // Écouter les mises à jour de contenu (pour voir les modifications en temps réel)
                this.channel.listen('.content.updated', (data) => {
                    if (data.user && data.user.id !== window.currentUserId && data.note) {
                        this.handleRemoteContent(data.note.contenu_markdown);
                    }
                });
                
                // Utilisateurs qui rejoignent
                this.channel.listen('.user.joined', (data) => {
                    if (data.user && !this.collaborators.find(c => c.id === data.user.id)) {
                        this.collaborators.push(data.user);
                    }
                });
                
                // Utilisateurs qui partent
                this.channel.listen('.user.left', (data) => {
                    if (data.user) {
                        this.collaborators = this.collaborators.filter(c => c.id !== data.user.id);
                        delete this.remoteCursors[data.user.id];
                        this.drawCursors();
                    }
                });
                
            } catch (e) {
                console.error('Erreur WebSocket:', e);
            }
        },

        // Sauvegarde avec délai (pas via Pusher)
        queueSave(content) {
            clearTimeout(this.saveTimer);
            this.saveStatus = 'saving';
            
            this.saveTimer = setTimeout(() => {
                this.saveContent(content);
            }, 2000);
        },

        // Sauvegarde HTTP
        async saveContent(content) {
            if (!content && this.editorView) {
                content = this.editorView.state.doc.toString();
            }

            try {
                const res = await fetch(`/admin/notes/${this.noteId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ contenu_markdown: content })
                });

                const data = await res.json();
                if (data.success) {
                    this.saveStatus = 'saved';
                    setTimeout(() => {
                        if (this.saveStatus === 'saved') {
                            this.saveStatus = 'idle';
                        }
                    }, 2000);
                } else {
                    this.saveStatus = 'idle';
                }
            } catch (e) {
                console.error('Erreur sauvegarde:', e);
                this.saveStatus = 'idle';
            }
        },

        // Mise à jour du titre
        async updateTitle() {
            try {
                await fetch(`/admin/notes/${this.noteId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ titre: this.noteTitle })
                });
            } catch (e) {
                console.error('Erreur titre:', e);
            }
        },

        // Mise à jour du curseur (pour les autres utilisateurs)
        queueCursorUpdate() {
            clearTimeout(this.cursorTimer);
            this.cursorTimer = setTimeout(() => this.updateCursor(), 200);
        },

        async updateCursor() {
            if (!this.editorView) return;
            
            try {
                const selection = this.editorView.state.selection.main;
                const pos = selection.head;
                
                // Envoyer seulement la position, pas le contenu
                await fetch(`/admin/notes/${this.noteId}/cursor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ position: pos })
                });
            } catch (e) {
                // Ignorer silencieusement
            }
        },

        // Gérer le curseur d'un autre utilisateur
        handleRemoteCursor(user, cursor) {
            this.remoteCursors[user.id] = {
                user: user,
                position: cursor.position || 0,
                time: Date.now()
            };
            this.drawCursors();
        },

        // Appliquer le contenu modifié par un autre utilisateur (temps réel)
        handleRemoteContent(content) {
            if (!this.editorView) return;

            this.isApplyingRemote = true;

            // Sauvegarder la position du curseur et du scroll
            const selection = this.editorView.state.selection.main;
            const scrollPos = this.editorView.scrollDOM.scrollTop;

            try {
                // Appliquer le nouveau contenu
                const transaction = this.editorView.state.update({
                    changes: {
                        from: 0,
                        to: this.editorView.state.doc.length,
                        insert: content
                    }
                });

                this.editorView.dispatch(transaction);

                // Restaurer la position du curseur si possible
                try {
                    const newSelection = selection.head > content.length 
                        ? { anchor: content.length, head: content.length }
                        : selection;
                    this.editorView.dispatch({
                        selection: newSelection
                    });
                } catch (e) {
                    // Ignorer si la position n'est plus valide
                }

                // Restaurer la position de scroll
                if (scrollPos !== undefined) {
                    this.editorView.scrollDOM.scrollTop = scrollPos;
                }

            } catch (e) {
                console.error('Erreur application contenu distant:', e);
            }

            this.isApplyingRemote = false;
            
            // Redessiner les curseurs
            this.drawCursors();
        },

        // Dessiner les curseurs distants
        drawCursors() {
            if (!this.editorView) return;

            const view = this.editorView;
            const scrollEl = view.scrollDOM || view.dom;
            
            // Nettoyer les curseurs existants
            scrollEl.querySelectorAll('.collaborator-cursor').forEach(el => el.remove());

            // Nettoyer les curseurs expirés (5 secondes)
            const now = Date.now();
            Object.keys(this.remoteCursors).forEach(userId => {
                if (now - this.remoteCursors[userId].time > 5000) {
                    delete this.remoteCursors[userId];
                }
            });

            // Dessiner chaque curseur
            Object.values(this.remoteCursors).forEach(cursorData => {
                try {
                    const pos = cursorData.position;
                    const coords = view.coordsAtPos(pos, 1); // 'window' pour les coordonnées absolues
                    
                    if (!coords) return;

                    // Vérifier si le curseur est visible
                    const scrollInfo = view.scrollDOM.getBoundingClientRect();
                    if (coords.top < scrollInfo.top || coords.top > scrollInfo.bottom) {
                        return; // Curseur hors de la zone visible
                    }

                    const color = this.getUserColor(cursorData.user.id);
                    const lineHeight = coords.bottom - coords.top || 20;

                    // Créer le curseur
                    const cursorEl = document.createElement('div');
                    cursorEl.className = 'collaborator-cursor';
                    cursorEl.dataset.userId = cursorData.user.id;
                    cursorEl.style.cssText = `
                        position: absolute;
                        width: 2px;
                        height: ${lineHeight}px;
                        background-color: ${color};
                        left: ${coords.left}px;
                        top: ${coords.top}px;
                        z-index: 10;
                        margin-left: -1px;
                        pointer-events: auto;
                    `;

                    // Ajouter la goutte (via ::before en CSS)
                    cursorEl.style.setProperty('--cursor-color', color);

                    // Tag avec le nom (apparaît au hover)
                    const nameTag = document.createElement('div');
                    nameTag.className = 'collaborator-name-tag';
                    nameTag.textContent = cursorData.user.name || 'Utilisateur';
                    nameTag.style.cssText = `
                        background-color: ${color};
                    `;

                    cursorEl.appendChild(nameTag);
                    scrollEl.appendChild(cursorEl);

                } catch (e) {
                    console.debug('Erreur rendu curseur:', e);
                }
            });
        },

        // Couleur pour un utilisateur
        getUserColor(userId) {
            if (!userId) return '#3b82f6';
            if (this.userColors[userId]) return this.userColors[userId];
            
            const colors = [
                '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
                '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
            ];
            
            const color = colors[userId % colors.length];
            this.userColors[userId] = color;
            return color;
        },

        // Style pour avatar collaborateur
        getCollaboratorColor(userId) {
            if (!userId) return {};
            const color = this.getUserColor(userId);
            return {
                backgroundColor: color + '20',
                color: color,
                borderColor: color
            };
        },

        // Getters pour le statut
        get saveStatusText() {
            if (this.saveStatus === 'saving') return 'Sauvegarde...';
            if (this.saveStatus === 'saved') return 'Sauvegardé';
            return '';
        },

        get saveStatusClass() {
            return this.saveStatus;
        }
    };
}

// Export et enregistrement
window.notesEditor = notesEditor;

function registerNotesEditor() {
    if (window.Alpine && typeof window.Alpine.data === 'function') {
        window.Alpine.data('notesEditor', notesEditor);
        return true;
    }
    return false;
}

if (!registerNotesEditor()) {
    document.addEventListener('alpine:init', () => {
        registerNotesEditor();
    });
    setTimeout(() => registerNotesEditor(), 1000);
}
