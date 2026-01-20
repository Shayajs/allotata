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
    // Convertir noteId en nombre, mais le garder comme string pour les canaux
    const noteIdNum = parseInt(noteId, 10);
    if (isNaN(noteIdNum) || noteIdNum <= 0) {
        console.error('Erreur: noteId invalide:', noteId);
    }
    
    return {
        noteId: noteIdNum,
        noteIdString: String(noteIdNum), // Version string pour les canaux
        noteTitle: '',
        saveStatus: 'idle',
        
        editorView: null,
        collaborators: [],
        remoteCursors: {},
        userColors: {},
        
        saveTimer: null,
        cursorTimer: null,
        heartbeatTimer: null,
        heartbeatCheckTimer: null,
        isApplyingRemote: false,
        isHandlingRemoteChange: false,
        
        echo: null,
        channel: null,
        hasMasterKey: false, // Clé de sauvegarde Master
        userHeartbeats: {}, // Track des derniers heartbeats de chaque utilisateur { userId: timestamp }
        
        init() {
            const titleInput = this.$el.querySelector('input[type="text"]');
            if (titleInput) {
                this.noteTitle = titleInput.value || '';
            }
            
            // Initialiser hasMasterKey depuis la base de données si disponible
            if (window.noteMasterUserId && window.noteMasterUserId !== null) {
                const masterUserId = Number(window.noteMasterUserId);
                const currentUserId = Number(window.currentUserId);
                this.hasMasterKey = masterUserId === currentUserId;
                console.log(`🔍 [init] Master en DB: ${masterUserId}, nous: ${currentUserId}, hasMasterKey: ${this.hasMasterKey}`);
            } else {
                console.log('🔍 [init] Pas de Master défini en DB, sera déterminé lors du join du canal');
            }
            
            // Initialiser notre propre heartbeat
            this.userHeartbeats[window.currentUserId] = Date.now();
            
            this.$nextTick(() => {
                this.setupEditor();
                this.setupWebSocket();
            });
        },

        // Nettoyer les timers lors de la destruction
        destroy() {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
            if (this.heartbeatCheckTimer) {
                clearInterval(this.heartbeatCheckTimer);
                this.heartbeatCheckTimer = null;
            }
            if (this.saveTimer) {
                clearTimeout(this.saveTimer);
            }
            if (this.cursorTimer) {
                clearTimeout(this.cursorTimer);
            }
        },

        // Nettoyer les timers lors de la destruction
        destroy() {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
            if (this.heartbeatCheckTimer) {
                clearInterval(this.heartbeatCheckTimer);
                this.heartbeatCheckTimer = null;
            }
            if (this.saveTimer) {
                clearTimeout(this.saveTimer);
            }
            if (this.cursorTimer) {
                clearTimeout(this.cursorTimer);
            }
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
                            // Détecter les changements de document
                            if (update.docChanged && !this.isApplyingRemote) {
                                this.handleLocalChange(update);
                            }
                            
                            // Détecter les changements de sélection (mouvement du curseur)
                            if (update.selectionSet || update.docChanged) {
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
                    // S'assurer que le conteneur est positionné en relative pour les curseurs absolus
                    if (window.getComputedStyle(scrollEl).position === 'static') {
                        scrollEl.style.position = 'relative';
                    }
                    scrollEl.addEventListener('scroll', () => this.drawCursors());
                    
                    // Redessiner les curseurs périodiquement pour s'assurer qu'ils sont à jour
                    setInterval(() => {
                        if (Object.keys(this.remoteCursors).length > 0) {
                            this.drawCursors();
                        }
                    }, 1000); // Toutes les secondes
                    
                    // Dessiner les curseurs après un court délai pour s'assurer que le DOM est prêt
                    setTimeout(() => this.drawCursors(), 100);
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
                // S'assurer que noteId est une chaîne pour le canal
                if (!this.noteId || isNaN(this.noteId) || this.noteId <= 0) {
                    console.error('Erreur: noteId invalide:', this.noteId);
                    return;
                }
                const channelName = `note.${this.noteIdString || String(this.noteId)}`;
                this.channel = this.echo.join(channelName);
                
                // Utilisateurs déjà présents
                this.channel.here((users) => {
                    this.collaborators = users;
                    this.determineMaster(users);
                    
                    // Initialiser les heartbeats des utilisateurs présents
                    const now = Date.now();
                    users.forEach(user => {
                        if (user.id !== window.currentUserId) {
                            this.userHeartbeats[user.id] = now;
                        }
                    });
                    
                    // Redessiner les curseurs après l'initialisation
                    this.$nextTick(() => {
                        this.drawCursors();
                    });
                });

                // Nouvel utilisateur rejoint
                this.channel.joining((user) => {
                    if (!this.collaborators.find(u => u.id === user.id)) {
                        this.collaborators.push(user);
                    }
                    this.determineMaster([...this.collaborators, user]);
                    
                    // Initialiser le heartbeat du nouvel utilisateur
                    if (user.id !== window.currentUserId) {
                        this.userHeartbeats[user.id] = Date.now();
                    }
                });

                // Utilisateur part
                this.channel.leaving((user) => {
                    this.collaborators = this.collaborators.filter(u => u.id !== user.id);
                    delete this.remoteCursors[user.id];
                    delete this.userHeartbeats[user.id];
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

                // Écouter les heartbeats des autres utilisateurs (whisper)
                this.channel.listenForWhisper('isAlive', (data) => {
                    if (data.userId !== window.currentUserId) {
                        this.userHeartbeats[data.userId] = Date.now();
                        
                        // S'assurer que l'utilisateur est dans la liste des collaborateurs
                        if (!this.collaborators.find(u => u.id === data.userId)) {
                            // Ajouter l'utilisateur si on le connaît via Presence Channel
                            // (normalement il devrait déjà être là, mais on peut avoir un cas limite)
                            console.log('Utilisateur actif détecté via heartbeat:', data.userId);
                        }
                        
                        // Si ce n'est pas nous qui sommes Master et que le Master actuel n'envoie plus de heartbeat
                        if (data.isMaster && !this.hasMasterKey) {
                            this.checkAndTransferMasterIfNeeded();
                        }
                    }
                });

                // Écouter les changements de Master (événement serveur)
                this.echo.listen('.master.changed', (data) => {
                    console.log('🔄 Master changé:', data);
                    const masterUserId = Number(data.master_user_id);
                    const currentUserId = Number(window.currentUserId);
                    window.noteMasterUserId = masterUserId; // Mettre à jour la référence globale
                    
                    const wasMaster = this.hasMasterKey;
                    this.hasMasterKey = masterUserId === currentUserId;
                    
                    console.log(`🔍 [master.changed] Master: ${masterUserId}, nous: ${currentUserId}, hasMasterKey: ${this.hasMasterKey}`);
                    
                    if (this.hasMasterKey && !wasMaster) {
                        console.log('💾 Vous êtes maintenant le Master');
                    } else if (!this.hasMasterKey && wasMaster) {
                        console.log(`💾 Vous n'êtes plus le Master. Nouveau Master: ${data.master_user_name || data.master_user_id}`);
                    } else {
                        console.log(`💾 Le Master est maintenant: ${data.master_user_name || data.master_user_id}`);
                    }
                });

                // Démarrer le système de heartbeat
                this.startHeartbeat();
                this.startHeartbeatCheck();

                console.log('Collaboration en temps réel activée (Master/Slave)');

            } catch (e) {
                console.error('Erreur WebSocket:', e);
            }
        },

        // Déterminer qui est le Master (priorité: master_user_id en base, sinon premier arrivé)
        determineMaster(users) {
            if (!users || users.length === 0) {
                this.hasMasterKey = false;
                console.log('⚠️ Aucun utilisateur présent, hasMasterKey = false');
                return;
            }

            const currentUserId = Number(window.currentUserId);
            
            // Si un master_user_id est défini en base de données, l'utiliser en priorité
            if (window.noteMasterUserId && window.noteMasterUserId !== null) {
                const masterUserId = Number(window.noteMasterUserId);
                const masterInUsers = users.find(u => Number(u.id) === masterUserId);
                if (masterInUsers) {
                    const wasMaster = this.hasMasterKey;
                    this.hasMasterKey = masterUserId === currentUserId;
                    console.log(`🔍 [determineMaster] Master en DB: ${masterUserId}, nous: ${currentUserId}, hasMasterKey: ${this.hasMasterKey}`);
                    if (this.hasMasterKey && !wasMaster) {
                        console.log('💾 Vous êtes le Master (défini en base de données)');
                    }
                    return;
                }
            }

            // Sinon, utiliser le premier arrivé
            const sorted = users.sort((a, b) => (a.joined_at || 0) - (b.joined_at || 0));
            const master = sorted[0];
            
            const wasMaster = this.hasMasterKey;
            this.hasMasterKey = Number(master.id) === currentUserId;
            
            console.log(`🔍 [determineMaster] Premier arrivé: ${master.id}, nous: ${currentUserId}, hasMasterKey: ${this.hasMasterKey}`);
            
            if (this.hasMasterKey && !wasMaster) {
                console.log('💾 Vous êtes le Master (premier arrivé)');
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
                            userId: Number(window.currentUserId), // S'assurer que c'est un nombre
                            from: Number(fromA), // S'assurer que c'est un nombre
                            to: Number(toA), // S'assurer que c'est un nombre
                            insert: String(inserted.toString() || ''), // S'assurer que c'est une string
                            timestamp: Number(Date.now()) // S'assurer que c'est un nombre
                        };

                        // Envoyer via whisper (événement client-client, pas serveur)
                        try {
                            this.channel.whisper('text-change', change);
                        } catch (e) {
                            console.error('Erreur whisper text-change:', e);
                        }
                    });
                }
            });

            // Si on a la clé Master, programmer la sauvegarde
            if (this.hasMasterKey) {
                const content = update.state.doc.toString();
                console.log('💾 [Master] Changement détecté, sauvegarde programmée...');
                this.queueSave(content);
            } else {
                console.log('⚠️ [Slave] Changement détecté mais pas Master, pas de sauvegarde. hasMasterKey:', this.hasMasterKey);
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
            if (data.userId !== window.currentUserId && data.position !== undefined) {
                // Trouver les données utilisateur depuis les collaborateurs ou utiliser celles fournies
                const collaborator = this.collaborators.find(u => u.id === data.userId);
                const userData = data.user || collaborator || {
                    id: data.userId,
                    name: `Utilisateur ${data.userId}`
                };
                
                this.remoteCursors[data.userId] = {
                    user: userData,
                    userId: data.userId,
                    position: data.position,
                    time: Date.now()
                };
                
                // Redessiner immédiatement
                this.$nextTick(() => {
                    this.drawCursors();
                });
            }
        },

        // Sauvegarde (uniquement si Master)
        queueSave(content) {
            if (!this.hasMasterKey) {
                console.warn('⚠️ queueSave appelé mais hasMasterKey est false');
                return;
            }

            clearTimeout(this.saveTimer);
            this.saveStatus = 'saving';
            console.log('💾 [Master] Sauvegarde programmée dans 2 secondes...');
            
            this.saveTimer = setTimeout(() => {
                this.saveContent(content);
            }, 2000);
        },

        async saveContent(content) {
            if (!this.hasMasterKey) {
                console.warn('⚠️ saveContent appelé mais hasMasterKey est false');
                this.saveStatus = 'idle';
                return;
            }

            if (!content && this.editorView) {
                content = this.editorView.state.doc.toString();
            }

            if (!content) {
                console.warn('⚠️ saveContent: pas de contenu à sauvegarder');
                this.saveStatus = 'idle';
                return;
            }

            console.log('💾 [Master] Sauvegarde en cours...', { noteId: this.noteId, contentLength: content.length });

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
                    console.log('✅ [Master] Sauvegarde réussie');
                    this.saveStatus = 'saved';
                    setTimeout(() => {
                        if (this.saveStatus === 'saved') {
                            this.saveStatus = 'idle';
                        }
                    }, 2000);
                } else {
                    console.error('❌ [Master] Sauvegarde échouée:', data);
                    this.saveStatus = 'idle';
                }
            } catch (e) {
                console.error('❌ [Master] Erreur sauvegarde:', e);
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
                
                // Trouver notre info utilisateur depuis les collaborateurs
                const currentUser = this.collaborators.find(u => u.id === window.currentUserId);
                const userName = currentUser?.name || 'Utilisateur';
                
                // Envoyer via whisper (pas besoin du serveur pour le curseur)
                this.channel.whisper('cursor-moved', {
                    userId: Number(window.currentUserId), // S'assurer que c'est un nombre
                    user: {
                        id: Number(window.currentUserId),
                        name: String(userName || 'Utilisateur') // S'assurer que c'est une string
                    },
                    position: Number(pos) // S'assurer que c'est un nombre
                });
            } catch (e) {
                console.error('Erreur updateCursor:', e);
            }
        },

        // Dessiner les curseurs distants
        drawCursors() {
            if (!this.editorView) return;

            const view = this.editorView;
            const scrollEl = view.scrollDOM || view.dom;
            
            if (!scrollEl) return;
            
            // Retirer tous les curseurs existants
            scrollEl.querySelectorAll('.collaborator-cursor').forEach(el => el.remove());

            const now = Date.now();
            const CURSOR_TIMEOUT = 5000; // 5 secondes
            
            // Nettoyer les curseurs expirés
            Object.keys(this.remoteCursors).forEach(userId => {
                if (now - this.remoteCursors[userId].time > CURSOR_TIMEOUT) {
                    delete this.remoteCursors[userId];
                }
            });

            // Dessiner chaque curseur actif
            Object.values(this.remoteCursors).forEach(cursorData => {
                try {
                    const pos = cursorData.position;
                    if (pos === undefined || pos === null) return;
                    
                    const coords = view.coordsAtPos(pos);
                    
                    if (!coords) return;

                    // Vérifier que le curseur est visible dans le viewport
                    const scrollInfo = scrollEl.getBoundingClientRect();
                    const cursorTop = coords.top;
                    const cursorBottom = coords.bottom;
                    
                    // Ne pas afficher si en dehors du viewport
                    if (cursorTop < scrollInfo.top || cursorBottom > scrollInfo.bottom) {
                        return;
                    }

                    const color = this.getUserColor(cursorData.user?.id || cursorData.userId);
                    const lineHeight = cursorBottom - cursorTop || 20;

                    // Calculer la position relative au scrollEl
                    const relativeLeft = coords.left - scrollInfo.left + scrollEl.scrollLeft;
                    const relativeTop = cursorTop - scrollInfo.top + scrollEl.scrollTop;

                    const cursorEl = document.createElement('div');
                    cursorEl.className = 'collaborator-cursor';
                    cursorEl.setAttribute('data-user-id', cursorData.user?.id || cursorData.userId);
                    cursorEl.style.cssText = `
                        position: absolute;
                        width: 2px;
                        height: ${lineHeight}px;
                        background-color: ${color};
                        left: ${relativeLeft}px;
                        top: ${relativeTop}px;
                        z-index: 1000;
                        margin-left: -1px;
                        pointer-events: auto;
                        transition: opacity 0.2s;
                    `;
                    cursorEl.style.setProperty('--cursor-color', color);

                    const nameTag = document.createElement('div');
                    nameTag.className = 'collaborator-name-tag';
                    nameTag.textContent = cursorData.user?.name || 'Utilisateur';
                    nameTag.style.cssText = `
                        background-color: ${color};
                        color: white;
                    `;

                    cursorEl.appendChild(nameTag);
                    scrollEl.appendChild(cursorEl);

                } catch (e) {
                    console.error('Erreur lors du dessin du curseur:', e);
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
        },

        // Système de heartbeat : envoyer toutes les secondes
        startHeartbeat() {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
            }

            // Envoyer immédiatement le premier heartbeat
            this.sendHeartbeat();

            // Envoyer toutes les secondes
            this.heartbeatTimer = setInterval(() => {
                this.sendHeartbeat();
            }, 1000);
        },

        // Envoyer un signal heartbeat au serveur et aux autres clients
        async sendHeartbeat() {
            if (!this.channel) return;

            try {
                // Envoyer via whisper aux autres clients (pour détection rapide)
                // S'assurer que toutes les valeurs sont des types primitifs valides
                this.channel.whisper('isAlive', {
                    userId: Number(window.currentUserId), // S'assurer que c'est un nombre
                    isMaster: Boolean(this.hasMasterKey), // S'assurer que c'est un booléen
                    timestamp: Number(Date.now()), // S'assurer que c'est un nombre
                });

                // Envoyer au serveur pour mettre à jour en base de données
                const response = await fetch(`/admin/notes/${this.noteId}/heartbeat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        is_master: this.hasMasterKey,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    // Mettre à jour notre statut si le serveur indique qu'on est Master
                    if (data.current_master_id === window.currentUserId && !this.hasMasterKey) {
                        this.hasMasterKey = true;
                        console.log('💾 Vous êtes maintenant le Master (confirmé par serveur)');
                    }
                }
            } catch (e) {
                console.error('Erreur envoi heartbeat:', e);
            }
        },

        // Vérifier périodiquement qui est mort et transférer le Master si nécessaire
        startHeartbeatCheck() {
            if (this.heartbeatCheckTimer) {
                clearInterval(this.heartbeatCheckTimer);
            }

            // Vérifier toutes les 3 secondes
            this.heartbeatCheckTimer = setInterval(() => {
                this.checkAndTransferMasterIfNeeded();
            }, 3000);
        },

        // Vérifier et transférer le Master si le Master actuel est mort
        async checkAndTransferMasterIfNeeded() {
            if (!this.collaborators || this.collaborators.length === 0) return;

            const now = Date.now();
            const HEARTBEAT_TIMEOUT = 5000; // 5 secondes sans heartbeat = mort

            // NETTOYER LES COLLABORATEURS INACTIFS
            const activeCollaborators = [];
            const removedUserIds = [];
            
            this.collaborators.forEach(user => {
                if (user.id === window.currentUserId) {
                    // Toujours garder nous-mêmes
                    activeCollaborators.push(user);
                } else {
                    const lastHeartbeat = this.userHeartbeats[user.id] || 0;
                    const isAlive = (now - lastHeartbeat) < HEARTBEAT_TIMEOUT;
                    
                    if (isAlive) {
                        activeCollaborators.push(user);
                    } else {
                        // Retirer l'utilisateur inactif
                        removedUserIds.push(user.id);
                        console.log(`🚫 Utilisateur ${user.name || user.id} retiré (inactif depuis ${Math.round((now - lastHeartbeat) / 1000)}s)`);
                    }
                }
            });
            
            // Mettre à jour la liste des collaborateurs si des changements
            if (removedUserIds.length > 0 || activeCollaborators.length !== this.collaborators.length) {
                this.collaborators = activeCollaborators;
                
                // Nettoyer les curseurs et heartbeats des utilisateurs retirés
                removedUserIds.forEach(userId => {
                    delete this.remoteCursors[userId];
                    delete this.userHeartbeats[userId];
                });
                
                // Redessiner les curseurs après nettoyage
                this.drawCursors();
            }

            // Trier les utilisateurs par joined_at pour déterminer le prochain Master
            const sorted = [...this.collaborators].sort((a, b) => (a.joined_at || 0) - (b.joined_at || 0));
            
            // Trouver le premier utilisateur vivant (qui a envoyé un heartbeat récemment)
            let newMaster = null;
            for (const user of sorted) {
                const lastHeartbeat = this.userHeartbeats[user.id] || 0;
                const isAlive = (now - lastHeartbeat) < HEARTBEAT_TIMEOUT;
                
                if (isAlive) {
                    newMaster = user;
                    break;
                }
            }

            // Si nous sommes le Master actuel mais qu'on ne devrait plus l'être, vérifier avec le serveur
            if (this.hasMasterKey && newMaster && newMaster.id !== window.currentUserId) {
                // Le prochain Master devrait être quelqu'un d'autre, mais on garde notre statut
                // jusqu'à ce que le serveur confirme ou qu'on ne reçoive plus notre propre statut
            }

            // Si on n'est pas Master mais qu'on devrait l'être (Master actuel est mort)
            if (!this.hasMasterKey && newMaster && newMaster.id === window.currentUserId) {
                try {
                    // Demander au serveur de nous nommer Master
                    const response = await fetch(`/admin/notes/${this.noteId}/master`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            master_user_id: window.currentUserId,
                        }),
                    });

                    if (response.ok) {
                        console.log('💾 Transfert du Master demandé au serveur');
                    }
                } catch (e) {
                    console.error('Erreur lors du transfert du Master:', e);
                }
            }

            // Nettoyer les heartbeats expirés (pour éviter une fuite mémoire)
            for (const userId in this.userHeartbeats) {
                const lastHeartbeat = this.userHeartbeats[userId];
                if ((now - lastHeartbeat) > HEARTBEAT_TIMEOUT * 2) {
                    delete this.userHeartbeats[userId];
                }
            }
        },
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
