/**
 * Éditeur de notes collaboratif - CodeMirror 6
 * Architecture Master/Slave : Synchronisation par frappes de touches
 */

import { EditorView } from '@codemirror/view';
import { EditorState, ChangeSet } from '@codemirror/state';
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
        
        editorView: null,
        collaborators: [],
        remoteCursors: {},
        userColors: {},
        
        saveTimer: null,
        cursorTimer: null,
        isApplyingRemote: false,
        isHandlingRemoteChange: false,
        
        echo: null,
        channel: null,
        hasMasterKey: false, // Clé de sauvegarde Master
        
        init() {
            const titleInput = this.$el.querySelector('input[type="text"]');
            if (titleInput) {
                this.noteTitle = titleInput.value || '';
            }
            
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

            const initialContent = window.noteContent || '';
            const isDark = document.documentElement.classList.contains('dark');

            try {
                const state = EditorState.create({
                    doc: initialContent,
                    extensions: [
                        basicSetup,
                        markdown(),
                        isDark ? oneDark : [],
                        EditorView.updateListener.of((update) => {
                            if (update.docChanged && !this.isApplyingRemote) {
                                // Capturer les changements individuels
                                this.handleLocalChange(update);
                                this.queueCursorUpdate();
                                this.drawCursors();
                            }
                        }),
                        EditorView.theme({
                            '&': { height: '100%' },
                            '.cm-scroller': {
                                fontFamily: "'Fira Code', 'Monaco', 'Menlo', monospace",
                            },
                        }),
                    ],
                });

                this.editorView = new EditorView({
                    state: state,
                    parent: container,
                });

                const scrollEl = this.editorView.scrollDOM;
                if (scrollEl) {
                    scrollEl.addEventListener('scroll', () => this.drawCursors());
                }

                const themeObserver = new MutationObserver(() => {
                    // Gestion thème si besoin
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
            if (!this.echo) {
                console.warn('Pusher non disponible, mode polling activé');
                return;
            }

            try {
                // Utiliser join() pour un Presence Channel
                this.channel = this.echo.join(`note.${this.noteId}`);
                
                // Utilisateurs déjà présents
                this.channel.here((users) => {
                    this.collaborators = users;
                    this.determineMaster(users);
                });

                // Nouvel utilisateur rejoint
                this.channel.joining((user) => {
                    if (!this.collaborators.find(u => u.id === user.id)) {
                        this.collaborators.push(user);
                    }
                    this.determineMaster([...this.collaborators, user]);
                });

                // Utilisateur part
                this.channel.leaving((user) => {
                    this.collaborators = this.collaborators.filter(u => u.id !== user.id);
                    delete this.remoteCursors[user.id];
                    this.determineMaster(this.collaborators);
                    this.drawCursors();
                });

                // Écouter les changements de texte (whisper - événements clients)
                this.channel.listenForWhisper('text-change', (data) => {
                    if (data.userId !== window.currentUserId) {
                        this.handleRemoteTextChange(data);
                    }
                });

                // Écouter les mouvements de curseur
                this.channel.listenForWhisper('cursor-moved', (data) => {
                    if (data.userId !== window.currentUserId) {
                        this.handleRemoteCursor(data);
                    }
                });

                console.log('Collaboration en temps réel activée (Master/Slave)');

            } catch (e) {
                console.error('Erreur WebSocket:', e);
            }
        },

        // Déterminer qui est le Master (premier arrivé)
        determineMaster(users) {
            if (!users || users.length === 0) {
                this.hasMasterKey = false;
                return;
            }

            // Trier par joined_at (timestamp)
            const sorted = users.sort((a, b) => (a.joined_at || 0) - (b.joined_at || 0));
            const master = sorted[0];
            
            this.hasMasterKey = master.id === window.currentUserId;
            
            if (this.hasMasterKey) {
                console.log('💾 Vous êtes le Master (sauvegarde activée)');
            }
        },

        // Gérer les changements locaux (envoyer via whisper)
        handleLocalChange(update) {
            if (!this.channel || this.isHandlingRemoteChange) return;

            // Extraire les changements de la transaction
            update.transactions.forEach(tr => {
                if (tr.changes && !tr.annotation('remote')) {
                    const changes = tr.changes;
                    
                    // Parcourir les changements individuels
                    changes.iterChanges((fromA, toA, fromB, toB, inserted) => {
                        const change = {
                            userId: window.currentUserId,
                            from: fromA,
                            to: toA,
                            insert: inserted.toString(),
                            timestamp: Date.now()
                        };

                        // Envoyer via whisper (événement client-client, pas serveur)
                        try {
                            this.channel.whisper('text-change', change);
                        } catch (e) {
                            console.error('Erreur whisper:', e);
                        }
                    });
                }
            });

            // Si on a la clé Master, programmer la sauvegarde
            if (this.hasMasterKey) {
                const content = update.state.doc.toString();
                this.queueSave(content);
            }
        },

        // Appliquer un changement distant
        handleRemoteTextChange(data) {
            if (!this.editorView) return;

            this.isApplyingRemote = true;

            try {
                const state = this.editorView.state;
                const changes = ChangeSet.of({
                    from: data.from,
                    to: data.to || data.from,
                    insert: data.insert || ''
                });

                const transaction = state.update({
                    changes: changes,
                    annotations: [EditorState.transactionMeta.of({ remote: true })]
                });

                this.editorView.dispatch(transaction);
            } catch (e) {
                console.error('Erreur application changement distant:', e);
            }

            this.isApplyingRemote = false;
            this.drawCursors();
        },

        // Gérer le curseur distant
        handleRemoteCursor(data) {
            if (data.user && data.position !== undefined) {
                this.remoteCursors[data.userId] = {
                    user: data.user,
                    position: data.position,
                    time: Date.now()
                };
                this.drawCursors();
            }
        },

        // Sauvegarde (uniquement si Master)
        queueSave(content) {
            if (!this.hasMasterKey) return;

            clearTimeout(this.saveTimer);
            this.saveStatus = 'saving';
            
            this.saveTimer = setTimeout(() => {
                this.saveContent(content);
            }, 2000);
        },

        async saveContent(content) {
            if (!this.hasMasterKey) {
                this.saveStatus = 'idle';
                return;
            }

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

        queueCursorUpdate() {
            clearTimeout(this.cursorTimer);
            this.cursorTimer = setTimeout(() => this.updateCursor(), 200);
        },

        async updateCursor() {
            if (!this.editorView || !this.channel) return;
            
            try {
                const selection = this.editorView.state.selection.main;
                const pos = selection.head;
                
                // Envoyer via whisper (pas besoin du serveur pour le curseur)
                this.channel.whisper('cursor-moved', {
                    userId: window.currentUserId,
                    user: {
                        id: window.currentUserId,
                        name: document.body.dataset.userName || 'Utilisateur'
                    },
                    position: pos
                });
            } catch (e) {
                // Ignorer
            }
        },

        // Dessiner les curseurs distants
        drawCursors() {
            if (!this.editorView) return;

            const view = this.editorView;
            const scrollEl = view.scrollDOM || view.dom;
            
            scrollEl.querySelectorAll('.collaborator-cursor').forEach(el => el.remove());

            const now = Date.now();
            Object.keys(this.remoteCursors).forEach(userId => {
                if (now - this.remoteCursors[userId].time > 5000) {
                    delete this.remoteCursors[userId];
                }
            });

            Object.values(this.remoteCursors).forEach(cursorData => {
                try {
                    const pos = cursorData.position;
                    const coords = view.coordsAtPos(pos, 1);
                    
                    if (!coords) return;

                    const scrollInfo = view.scrollDOM.getBoundingClientRect();
                    if (coords.top < scrollInfo.top || coords.top > scrollInfo.bottom) {
                        return;
                    }

                    const color = this.getUserColor(cursorData.user.id);
                    const lineHeight = coords.bottom - coords.top || 20;

                    const cursorEl = document.createElement('div');
                    cursorEl.className = 'collaborator-cursor';
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
                    cursorEl.style.setProperty('--cursor-color', color);

                    const nameTag = document.createElement('div');
                    nameTag.className = 'collaborator-name-tag';
                    nameTag.textContent = cursorData.user.name || 'Utilisateur';
                    nameTag.style.cssText = `
                        background-color: ${color};
                    `;

                    cursorEl.appendChild(nameTag);
                    scrollEl.appendChild(cursorEl);

                } catch (e) {
                    // Ignorer
                }
            });
        },

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

        getCollaboratorColor(userId) {
            if (!userId) return {};
            const color = this.getUserColor(userId);
            return {
                backgroundColor: color + '20',
                color: color,
                borderColor: color
            };
        },

        get saveStatusText() {
            if (this.hasMasterKey) {
                if (this.saveStatus === 'saving') return 'Sauvegarde...';
                if (this.saveStatus === 'saved') return 'Sauvegardé';
            } else {
                return 'En lecture seule';
            }
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
