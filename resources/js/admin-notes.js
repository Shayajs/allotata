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
    
    // S'assurer que toutes les valeurs sont des strings valides
    let key = window.PUSHER_APP_KEY;
    let cluster = window.PUSHER_APP_CLUSTER || 'mt1';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    // Vérifier que la clé existe et n'est pas vide
    if (!key || key === '' || key === 'null' || key === 'undefined' || typeof key !== 'string') {
        console.error('❌ PUSHER_APP_KEY non défini ou invalide:', key, 'type:', typeof key);
        return null;
    }
    
    // Convertir explicitement en string et vérifier
    const keyStr = String(key).trim();
    const clusterStr = String(cluster).trim();
    const csrfTokenStr = String(csrfToken).trim();
    
    if (!keyStr || keyStr === '' || keyStr.length < 10) {
        console.error('❌ PUSHER_APP_KEY est vide ou trop court après conversion:', keyStr.length, 'caractères');
        return null;
    }
    
    // Vérifier que cluster est valide
    if (!clusterStr || clusterStr === '') {
        console.error('❌ PUSHER_APP_CLUSTER est invalide:', clusterStr);
        return null;
    }
    
    if (!csrfTokenStr) {
        console.warn('⚠️ CSRF token non trouvé, l\'authentification peut échouer');
    }
    
    // Vérifier que Echo et Pusher sont disponibles
    if (typeof Echo === 'undefined') {
        console.error('❌ Laravel Echo n\'est pas chargé');
        return null;
    }
    
    if (typeof Pusher === 'undefined' && typeof window.Pusher === 'undefined') {
        console.error('❌ Pusher n\'est pas chargé');
        return null;
    }
    
    try {
        // Utiliser Pusher directement si disponible via window
        const PusherClient = window.Pusher || Pusher;
        
        echoInstance = new Echo({
            broadcaster: 'pusher',
            client: new PusherClient(keyStr, {
                cluster: clusterStr,
                forceTLS: true,
                encrypted: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': csrfTokenStr,
                        'Accept': 'application/json',
                    },
                },
            }),
        });
        
        console.log('✅ Laravel Echo initialisé avec succès', { key: keyStr.substring(0, 10) + '...', cluster: clusterStr });
    } catch (e) {
        console.error('❌ Erreur initialisation Echo:', e);
        console.error('   Détails:', { 
            keyType: typeof key, 
            clusterType: typeof cluster, 
            keyLength: keyStr?.length,
            cluster: clusterStr,
            echoAvailable: typeof Echo !== 'undefined',
            pusherAvailable: typeof Pusher !== 'undefined' || typeof window.Pusher !== 'undefined'
        });
        return null;
    }
    
    return echoInstance;
}

// Composant Alpine.js
function notesEditor(noteId) {
    // Forcer la conversion en string primitive immédiatement (évite les Proxy Alpine)
    const noteIdStr = String(noteId || '').trim();
    const noteIdNum = parseInt(noteIdStr, 10);
    
    if (isNaN(noteIdNum) || noteIdNum <= 0) {
        console.error('Erreur: noteId invalide:', noteId, 'converti en:', noteIdStr);
    }
    
    return {
        noteId: noteIdNum,
        noteIdString: noteIdStr, // Version string primitive (pas de Proxy)
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
        isChannelSubscribed: false, // Flag pour vérifier si le canal est souscrit
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
                console.warn('⚠️ Laravel Echo non disponible, mode polling activé');
                return;
            }

            try {
                // On extrait la valeur la plus simple possible
                const rawId = this.noteId || this.noteIdString;
                const cleanId = String(rawId).replace(/[^0-9]/g, ''); // On ne garde que les chiffres

                if (!cleanId) {
                    console.error("❌ ID de note manquant");
                    return;
                }

                const channelName = "note." + cleanId;
                
                // On appelle Echo en s'assurant que l'objet est bien là
                if (this.echo && typeof this.echo.join === 'function') {
                    this.channel = this.echo.join(channelName);
                    console.log("🚀 Succès : Connexion au canal", channelName);
                } else {
                    console.error('❌ Erreur: echo.join n\'est pas une fonction', { echo: this.echo, join: typeof this.echo?.join });
                    return;
                }

                // Écouter les changements de texte (whisper - événements clients)
                this.channel.listenForWhisper('text-change', (data) => {
                    const senderId = Number(data.userId);
                    const currentId = Number(window.currentUserId);
                    console.log('📥 [Client Event] text-change reçu:', { senderId, currentId, from: data.from, to: data.to, insertLength: data.insert?.length });
                    
                    if (senderId !== currentId) {
                        console.log('✅ [Client Event] Application du changement de texte distant...');
                        this.handleRemoteTextChange(data);
                    } else {
                        console.log('⏭️ [Client Event] Changement ignoré (notre propre changement)');
                    }
                });

                // Écouter les mouvements de curseur
                this.channel.listenForWhisper('cursor-moved', (data) => {
                    const senderId = Number(data.userId);
                    const currentId = Number(window.currentUserId);
                    console.log('📥 [Client Event] cursor-moved reçu:', { senderId, currentId, position: data.position });
                    
                    if (senderId !== currentId) {
                        console.log('✅ [Client Event] Application du mouvement de curseur distant...');
                        this.handleRemoteCursor(data);
                    } else {
                        console.log('⏭️ [Client Event] Mouvement de curseur ignoré (notre propre mouvement)');
                    }
                });

                // Écouter les heartbeats des autres utilisateurs
                this.channel.listenForWhisper('isAlive', (data) => {
                    const senderId = Number(data.userId);
                    const currentId = Number(window.currentUserId);
                    
                    if (senderId !== currentId) {
                        this.userHeartbeats[senderId] = Date.now();
                        
                        // Si ce n'est pas nous qui sommes Master et que le Master actuel n'envoie plus de heartbeat
                        if (data.isMaster && !this.hasMasterKey) {
                            this.checkAndTransferMasterIfNeeded();
                        }
                    }
                });

                // Écouter les changements de Master (événement serveur)
                // Le nom de l'événement est défini par broadcastAs() = 'master.changed'
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

                // Le heartbeat sera démarré dans .here() callback

                console.log('Collaboration en temps réel activée (Master/Slave) - En attente de souscription...');

            } catch (e) {
                console.error("💥 Crash lors de la création du canal :", e.message);
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

        // Gérer les changements locaux (envoyer via client event)
        handleLocalChange(update) {
            if (!this.channel || !this.isChannelSubscribed) {
                console.warn('⚠️ handleLocalChange: canal non disponible ou non souscrit');
                return;
            }
            
            if (this.isHandlingRemoteChange) {
                console.log('⏭️ handleLocalChange: changement distant en cours, ignoré');
                return;
            }

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

                        // Envoyer via client event (événement client-client, pas serveur)
                        // Vérifier que le canal est souscrit avant d'envoyer
                        if (!this.isChannelSubscribed) {
                            console.warn('⚠️ Canal non encore souscrit, skip text-change');
                            return;
                        }
                        
                        try {
                            console.log('📤 [Whisper] Envoi text-change:', { from: change.from, to: change.to, insertLength: change.insert.length });
                            this.channel.whisper('text-change', change);
                        } catch (e) {
                            console.error('❌ Erreur client event text-change:', e);
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
            if (!this.editorView) {
                console.warn('⚠️ handleRemoteTextChange: editorView n\'est pas disponible');
                return;
            }

            if (this.isApplyingRemote) {
                console.warn('⚠️ handleRemoteTextChange: déjà en train d\'appliquer un changement distant');
                return;
            }

            this.isApplyingRemote = true;
            this.isHandlingRemoteChange = true;

            try {
                const state = this.editorView.state;
                const from = Number(data.from) || 0;
                const to = Number(data.to) || Number(data.from) || 0;
                const insert = String(data.insert || '');
                
                console.log(`🔄 [handleRemoteTextChange] Application: from=${from}, to=${to}, insert="${insert.substring(0, 20)}..."`);
                
                const changes = ChangeSet.of({
                    from: from,
                    to: to,
                    insert: insert
                });

                const transaction = state.update({
                    changes: changes,
                    annotations: [EditorState.transactionMeta.of({ remote: true })]
                });

                this.editorView.dispatch(transaction);
                console.log('✅ [handleRemoteTextChange] Changement appliqué avec succès');
            } catch (e) {
                console.error('❌ Erreur application changement distant:', e);
            }

            this.isApplyingRemote = false;
            this.isHandlingRemoteChange = false;
            
            // Redessiner les curseurs après le changement
            this.$nextTick(() => {
                this.drawCursors();
            });
        },

        // Gérer le curseur distant
        handleRemoteCursor(data) {
            const senderId = Number(data.userId);
            const currentId = Number(window.currentUserId);
            const position = Number(data.position);
            
            if (senderId !== currentId && position !== undefined && !isNaN(position)) {
                // Trouver les données utilisateur depuis les collaborateurs ou utiliser celles fournies
                const collaborator = this.collaborators.find(u => Number(u.id) === senderId);
                const userData = data.user || collaborator || {
                    id: senderId,
                    name: `Utilisateur ${senderId}`
                };
                
                this.remoteCursors[senderId] = {
                    user: userData,
                    userId: senderId,
                    position: position,
                    time: Date.now()
                };
                
                console.log(`✅ [handleRemoteCursor] Curseur mis à jour pour utilisateur ${senderId} à la position ${position}`);
                
                // Redessiner immédiatement
                this.$nextTick(() => {
                    this.drawCursors();
                });
            } else {
                console.log(`⏭️ [handleRemoteCursor] Ignoré: senderId=${senderId}, currentId=${currentId}, position=${position}`);
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
            if (!this.editorView || !this.channel || !this.isChannelSubscribed) {
                console.warn('⚠️ updateCursor: canal non disponible ou non souscrit');
                return;
            }
            
            try {
                const selection = this.editorView.state.selection.main;
                const pos = selection.head;
                
                // Trouver notre info utilisateur depuis les collaborateurs
                const currentUser = this.collaborators.find(u => u.id === window.currentUserId);
                const userName = currentUser?.name || 'Utilisateur';
                
                // Vérification déjà faite au début de la fonction, mais double-check pour sécurité
                if (!this.isChannelSubscribed || !this.channel) {
                    console.warn('⚠️ Canal non disponible ou non souscrit, skip cursor-moved');
                    return;
                }
                
                const cursorData = {
                    userId: Number(window.currentUserId), // S'assurer que c'est un nombre
                    user: {
                        id: Number(window.currentUserId),
                        name: String(userName || 'Utilisateur') // S'assurer que c'est une string
                    },
                    position: Number(pos) // S'assurer que c'est un nombre
                };
                console.log('📤 [Whisper] Envoi cursor-moved:', { userId: cursorData.userId, position: cursorData.position });
                try {
                    this.channel.whisper('cursor-moved', cursorData);
                } catch (e) {
                    console.error('❌ Erreur client event cursor-moved:', e);
                }
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

            // Vérifier que le canal est prêt avant de démarrer
            if (!this.isChannelSubscribed) {
                console.warn('⚠️ startHeartbeat: canal non encore souscrit, attente...');
                // Réessayer dans 1 seconde
                setTimeout(() => this.startHeartbeat(), 1000);
                return;
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
                // Vérifier que le canal est souscrit avant d'envoyer
                if (!this.isChannelSubscribed) {
                    console.warn('⚠️ Canal non encore souscrit, skip heartbeat');
                    return;
                }
                
                // Envoyer via client event aux autres clients (pour détection rapide)
                // S'assurer que toutes les valeurs sont des types primitifs valides
                try {
                    this.channel.whisper('isAlive', {
                        userId: Number(window.currentUserId), // S'assurer que c'est un nombre
                        isMaster: Boolean(this.hasMasterKey), // S'assurer que c'est un booléen
                        timestamp: Number(Date.now()), // S'assurer que c'est un nombre
                    });
                } catch (e) {
                    console.error('❌ Erreur client event isAlive:', e);
                }

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

            // Vérifier que le canal est prêt avant de démarrer
            if (!this.isChannelSubscribed) {
                console.warn('⚠️ startHeartbeatCheck: canal non encore souscrit, attente...');
                // Réessayer dans 1 seconde
                setTimeout(() => this.startHeartbeatCheck(), 1000);
                return;
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
