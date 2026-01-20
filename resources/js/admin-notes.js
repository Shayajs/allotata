/**
 * Éditeur de notes collaboratif - CodeMirror 6
 * Architecture Master/Slave : Synchronisation par frappes de touches
 */

import { EditorView } from '@codemirror/view';
import { EditorState, ChangeSet } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Instance Pusher globale
let pusherInstance = null;

function getPusher() {
    if (pusherInstance) return pusherInstance;
    
    const key = window.PUSHER_APP_KEY;
    const cluster = window.PUSHER_APP_CLUSTER || 'mt1';
    
    if (!key) {
        console.error('PUSHER_APP_KEY non défini');
        return null;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        if (!csrfToken) {
            console.warn('⚠️ CSRF token non trouvé, l\'authentification peut échouer');
        }
        
        pusherInstance = new Pusher(String(key), {
            cluster: String(cluster),
            forceTLS: true,
            encrypted: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            },
        });
        
        // Gestion des erreurs de connexion
        pusherInstance.connection.bind('error', (err) => {
            console.error('❌ Erreur de connexion Pusher:', err);
            if (err.error) {
                console.error('   Code:', err.error.code);
                console.error('   Message:', err.error.data?.message || err.error.message);
            }
        });
        
        pusherInstance.connection.bind('authorization_error', (data) => {
            console.error('❌ Erreur d\'autorisation Pusher:', data);
            console.error('   Status:', data.status);
            console.error('   Message:', data.message);
        });
        
        pusherInstance.connection.bind('connected', () => {
            console.log('✅ Pusher connecté avec succès');
        });
        
        pusherInstance.connection.bind('disconnected', () => {
            console.warn('⚠️ Pusher déconnecté');
        });
        
        console.log('✅ Instance Pusher créée avec succès');
    } catch (e) {
        console.error('❌ Erreur initialisation Pusher:', e);
        return null;
    }
    
    return pusherInstance;
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
        
        pusher: null,
        channel: null,
        presenceChannel: null,
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
            this.pusher = getPusher();
            if (!this.pusher) {
                console.warn('⚠️ Pusher non disponible, mode polling activé');
                return;
            }

            try {
                // S'assurer que noteId est valide
                if (!this.noteId || isNaN(this.noteId) || this.noteId <= 0) {
                    console.error('❌ Erreur: noteId invalide:', this.noteId);
                    return;
                }
                
                // Nom du canal Presence - S'assurer que c'est une string valide
                const noteIdStr = String(this.noteIdString || this.noteId || '');
                if (!noteIdStr || noteIdStr === 'NaN' || noteIdStr === '0') {
                    console.error('❌ Erreur: noteId invalide pour le canal:', noteIdStr);
                    return;
                }
                
                const channelName = `presence-note.${noteIdStr}`;
                console.log('🔌 Connexion au canal:', channelName, typeof channelName);
                
                // Créer le canal Presence - s'assurer que le nom est une string
                try {
                    this.presenceChannel = this.pusher.subscribe(String(channelName));
                    this.channel = this.presenceChannel; // Pour compatibilité avec le reste du code
                } catch (e) {
                    console.error('❌ Erreur lors de la souscription au canal:', e, channelName);
                    return;
                }
                
                // Gestion des erreurs de souscription
                this.presenceChannel.bind('pusher:subscription_error', (status, error) => {
                    console.error('❌ Erreur de souscription au canal:', status, error);
                    console.error('   Type:', error?.type);
                    console.error('   Status:', status);
                    console.error('   Error data:', error?.error);
                    
                    if (status === 403) {
                        console.error('🔐 Erreur 403: Problème d\'authentification');
                        console.error('   Vérifiez que:');
                        console.error('   1. Vous êtes bien authentifié (connecté)');
                        console.error('   2. Vous êtes admin (is_admin = true)');
                        console.error('   3. Vous êtes collaborateur de la note');
                        console.error('   4. Consultez storage/logs/laravel.log pour plus de détails');
                    }
                    
                    this.isChannelSubscribed = false;
                });
                
                // Attendre que le canal soit complètement joint AVANT toute action
                this.presenceChannel.bind('pusher:subscription_succeeded', (members) => {
                    console.log('✅ Canal Presence souscrit avec succès:', channelName);
                    console.log('✅ Canal prêt pour les événements client');
                    this.isChannelSubscribed = true; // Marquer comme souscrit
                    
                    // Vérification supplémentaire de l'état du canal
                    if (this.presenceChannel && this.presenceChannel.subscribed) {
                        console.log('✅ Vérification: canal.subscribed = true');
                    } else {
                        console.warn('⚠️ Attention: canal.subscribed = false malgré subscription_succeeded');
                    }
                    
                    const users = Object.values(members.members || {}).map(member => ({
                        id: member.id || member.user_id,
                        name: member.name || member.user_info?.name || 'Utilisateur',
                        ...member
                    }));
                    
                    console.log('👥 Utilisateurs présents:', users.length, users.map(u => ({ id: u.id, name: u.name })));
                    this.collaborators = users;
                    this.determineMaster(users);
                    
                    // Initialiser les heartbeats des utilisateurs présents
                    const now = Date.now();
                    users.forEach(user => {
                        const userId = Number(user.id);
                        if (userId !== Number(window.currentUserId)) {
                            this.userHeartbeats[userId] = now;
                        }
                    });
                    
                    // Démarrer le heartbeat maintenant que nous sommes connectés
                    this.startHeartbeat();
                    this.startHeartbeatCheck();
                    
                    // Redessiner les curseurs après l'initialisation
                    this.$nextTick(() => {
                        this.drawCursors();
                    });
                });

                // Nouvel utilisateur rejoint
                this.presenceChannel.bind('pusher:member_added', (member) => {
                    const user = {
                        id: member.id || member.user_id,
                        name: member.info?.name || member.name || 'Utilisateur',
                        ...member
                    };
                    
                    console.log('➕ Utilisateur rejoint:', user);
                    if (!this.collaborators.find(u => Number(u.id) === Number(user.id))) {
                        this.collaborators.push(user);
                    }
                    this.determineMaster([...this.collaborators]);
                    
                    // Initialiser le heartbeat du nouvel utilisateur
                    const userId = Number(user.id);
                    if (userId !== Number(window.currentUserId)) {
                        this.userHeartbeats[userId] = Date.now();
                    }
                });

                // Utilisateur part
                this.presenceChannel.bind('pusher:member_removed', (member) => {
                    const userId = Number(member.id || member.user_id);
                    console.log('➖ Utilisateur part:', userId);
                    this.collaborators = this.collaborators.filter(u => Number(u.id) !== userId);
                    delete this.remoteCursors[userId];
                    delete this.userHeartbeats[userId];
                    this.determineMaster(this.collaborators);
                    this.drawCursors();
                });

                // Écouter les changements de texte (client events - événements clients)
                this.presenceChannel.bind('client-text-change', (data) => {
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
                this.presenceChannel.bind('client-cursor-moved', (data) => {
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
                this.presenceChannel.bind('client-isAlive', (data) => {
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
                this.presenceChannel.bind('master.changed', (data) => {
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

                // Ne pas démarrer le heartbeat ici, attendre subscription_succeeded
                // this.startHeartbeat() sera appelé dans subscription_succeeded

                console.log('Collaboration en temps réel activée (Master/Slave) - En attente de souscription...');

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

        // Gérer les changements locaux (envoyer via client event)
        handleLocalChange(update) {
            if (!this.presenceChannel || !this.isChannelSubscribed) {
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
                            console.log('📤 [Client Event] Envoi text-change:', { from: change.from, to: change.to, insertLength: change.insert.length });
                            this.presenceChannel.trigger('client-text-change', change);
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
            if (!this.editorView || !this.presenceChannel || !this.isChannelSubscribed) {
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
                if (!this.isChannelSubscribed || !this.presenceChannel) {
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
                console.log('📤 [Client Event] Envoi cursor-moved:', { userId: cursorData.userId, position: cursorData.position });
                try {
                    this.presenceChannel.trigger('client-cursor-moved', cursorData);
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
                    this.presenceChannel.trigger('client-isAlive', {
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
