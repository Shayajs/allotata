/**
 * Éditeur de notes collaboratif - CodeMirror 6
 * Architecture Master/Slave : Synchronisation par frappes de touches
 * Connexion DIRECTE à Pusher (sans Laravel Echo)
 */

import { EditorView } from '@codemirror/view';
import { EditorState, Transaction } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Instance Pusher globale
let pusherInstance = null;

/**
 * Initialise une connexion DIRECTE à Pusher (sans Laravel Echo)
 */
function getPusher() {
    if (pusherInstance) return pusherInstance;
    
    // Récupérer les clés depuis window
    let key = window.PUSHER_APP_KEY;
    let cluster = window.PUSHER_APP_CLUSTER || 'mt1';
    
    // Vérifier que la clé existe et n'est pas vide
    if (!key || key === '' || key === 'null' || key === 'undefined' || typeof key !== 'string') {
        console.error('❌ PUSHER_APP_KEY non défini ou invalide:', key, 'type:', typeof key);
        return null;
    }
    
    // Convertir explicitement en string et vérifier
    const keyStr = String(key).trim();
    const clusterStr = String(cluster).trim();
    
    if (!keyStr || keyStr === '' || keyStr.length < 10) {
        console.error('❌ PUSHER_APP_KEY est vide ou trop court après conversion:', keyStr.length, 'caractères');
        return null;
    }
    
    // Vérifier que cluster est valide
    if (!clusterStr || clusterStr === '') {
        console.error('❌ PUSHER_APP_CLUSTER est invalide:', clusterStr);
        return null;
    }
    
    // Vérifier que Pusher est disponible
    if (typeof Pusher === 'undefined' && typeof window.Pusher === 'undefined') {
        console.error('❌ Pusher n\'est pas chargé');
        return null;
    }
    
    try {
        // Utiliser Pusher directement
        const PusherClient = window.Pusher || Pusher;
        
        pusherInstance = new PusherClient(keyStr, {
            cluster: clusterStr,
            forceTLS: true,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            },
        });
        
        // Écouter les événements de connexion
        pusherInstance.connection.bind('connected', () => {
            console.log('✅ Connexion Pusher établie directement');
        });
        
        pusherInstance.connection.bind('error', (err) => {
            console.error('❌ Erreur connexion Pusher:', err);
        });
        
        pusherInstance.connection.bind('disconnected', () => {
            console.warn('⚠️ Connexion Pusher fermée');
        });
        
        console.log('✅ Pusher initialisé directement avec succès', { key: keyStr.substring(0, 10) + '...', cluster: clusterStr });
    } catch (e) {
        console.error('❌ Erreur initialisation Pusher:', e);
        return null;
    }
    
    return pusherInstance;
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
        
        pusher: null, // Instance Pusher directe (sans Laravel Echo)
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
                            
                            // Si c'est un changement distant, forcer un rafraîchissement de la syntaxe
                            if (update.docChanged && this.isApplyingRemote) {
                                // Forcer CodeMirror à recalculer la syntaxe après un changement distant
                                // Cela préserve la colorisation syntaxique
                                setTimeout(() => {
                                    if (this.editorView) {
                                        this.editorView.requestMeasure();
                                    }
                                }, 0);
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
            // Connexion DIRECTE à Pusher (sans Laravel Echo)
            this.pusher = getPusher();
            if (!this.pusher) {
                console.warn('⚠️ Pusher non disponible, mode polling activé');
                return;
            }

            try {
                const noteIdStr = String(this.noteId || this.noteIdString).trim();
                const channelName = `presence-note.${noteIdStr}`; // Presence channel avec préfixe

                console.log("🚀 Connexion DIRECTE à Pusher - Canal:", channelName);

                // Se connecter au canal Presence directement
                const channel = this.pusher.subscribe(channelName);
                
                // Gérer les erreurs de souscription
                channel.bind('pusher:subscription_error', (status) => {
                    console.error('❌ Erreur souscription canal:', status);
                });

                // Canal souscrit avec succès
                channel.bind('pusher:subscription_succeeded', (members) => {
                    console.log('✅ Canal Presence souscrit avec succès');
                    this.isChannelSubscribed = true;
                    
                    // Convertir les membres en format collaborateur
                    const users = Object.values(members.members || {}).map(member => ({
                        id: Number(member.id || member.user_id),
                        name: member.name || 'Utilisateur',
                        email: member.email || '',
                        joined_at: member.joined_at || Date.now()
                    }));
                    
                    console.log('👥 Utilisateurs présents:', users.length);
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

                // Utilisateur rejoint
                channel.bind('pusher:member_added', (member) => {
                    console.log('➕ Utilisateur rejoint:', member);
                    const user = {
                        id: Number(member.id || member.user_id),
                        name: member.info?.name || member.name || 'Utilisateur',
                        email: member.email || '',
                        joined_at: member.joined_at || Date.now()
                    };
                    
                    if (!this.collaborators.find(u => Number(u.id) === Number(user.id))) {
                        this.collaborators.push(user);
                    }
                    this.determineMaster([...this.collaborators]);
                    
                    const userId = Number(user.id);
                    if (userId !== Number(window.currentUserId)) {
                        this.userHeartbeats[userId] = Date.now();
                    }
                });

                // Utilisateur part
                channel.bind('pusher:member_removed', (member) => {
                    const userId = Number(member.id || member.user_id);
                    console.log('➖ Utilisateur part:', userId);
                    this.collaborators = this.collaborators.filter(u => Number(u.id) !== userId);
                    delete this.remoteCursors[userId];
                    delete this.userHeartbeats[userId];
                    this.determineMaster(this.collaborators);
                    this.drawCursors();
                });

                // Écouter les événements client-client (whisper) - text-change
                channel.bind('client-text-change', (data) => {
                    const senderId = Number(data.userId);
                    const currentId = Number(window.currentUserId);
                    if (senderId !== currentId) {
                        this.handleRemoteTextChange(data);
                    }
                });

                // Écouter les événements client-client (whisper) - cursor-moved
                channel.bind('client-cursor-moved', (data) => {
                    const senderId = Number(data.userId);
                    const currentId = Number(window.currentUserId);
                    if (senderId !== currentId) {
                        this.handleRemoteCursor(data);
                    }
                });

                // Écouter les événements client-client (whisper) - isAlive
                channel.bind('client-isAlive', (data) => {
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
                channel.bind('App\\Events\\MasterChanged', (data) => {
                    console.log('🔄 Master changé:', data);
                    const masterUserId = Number(data.master_user_id);
                    const currentUserId = Number(window.currentUserId);
                    window.noteMasterUserId = masterUserId;
                    
                    const wasMaster = this.hasMasterKey;
                    this.hasMasterKey = masterUserId === currentUserId;
                    
                    if (this.hasMasterKey && !wasMaster) {
                        console.log('💾 Vous êtes maintenant le Master');
                    } else if (!this.hasMasterKey && wasMaster) {
                        console.log(`💾 Vous n'êtes plus le Master. Nouveau Master: ${data.master_user_name || data.master_user_id}`);
                    }
                });

                // Sauvegarder la référence du canal
                this.channel = channel;
                window.activeNoteChannel = channel;

                console.log("✅ Connexion DIRECTE à Pusher établie - Écouteurs attachés");
                console.log('Collaboration en temps réel activée (Master/Slave)');
            } catch (i) {
                console.error("💥 Erreur fatale WebSocket:", i);
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
            let hasLocalChanges = false;
            update.transactions.forEach(tr => {
                if (tr.changes && !tr.annotation('remote')) {
                    hasLocalChanges = true;
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
                            console.log('📤 [Pusher Direct] Envoi text-change:', { from: change.from, to: change.to, insertLength: change.insert.length });
                            // Utiliser trigger() directement avec Pusher (client events)
                            this.channel.trigger('client-text-change', change);
                        } catch (e) {
                            console.error('❌ Erreur client event text-change:', e);
                        }
                    });
                }
            });

            // Si on a la clé Master ET qu'il y a des changements locaux, programmer la sauvegarde
            if (hasLocalChanges) {
                // Vérifier à nouveau hasMasterKey au moment de la sauvegarde
                // (au cas où il aurait changé entre temps)
                const currentUserId = Number(window.currentUserId);
                const masterUserId = window.noteMasterUserId ? Number(window.noteMasterUserId) : null;
                
                // Si on est seul ou si on est le Master, on sauvegarde
                const shouldSave = this.hasMasterKey || 
                                   (this.collaborators.length <= 1) ||
                                   (masterUserId === currentUserId) ||
                                   (!masterUserId && this.collaborators.length > 0 && Number(this.collaborators[0].id) === currentUserId);
                
                if (shouldSave) {
                    // Mettre à jour hasMasterKey si nécessaire
                    if (!this.hasMasterKey && shouldSave) {
                        this.hasMasterKey = true;
                        console.log('💾 [Auto-Master] Vous êtes maintenant le Master (sauvegarde activée)');
                    }
                    
                    const content = update.state.doc.toString();
                    console.log('💾 [Master] Changement détecté, sauvegarde programmée...', { 
                        hasMasterKey: this.hasMasterKey, 
                        contentLength: content.length,
                        shouldSave: shouldSave
                    });
                    this.queueSave(content);
                } else {
                    console.log('⚠️ [Slave] Changement détecté mais pas Master, pas de sauvegarde.', { 
                        hasMasterKey: this.hasMasterKey, 
                        currentUserId: currentUserId,
                        masterUserId: masterUserId,
                        collaboratorsCount: this.collaborators.length
                    });
                }
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
                const docLength = state.doc.length;
                const from = Number(data.from) || 0;
                const to = Number(data.to) || Number(data.from) || 0;
                const insert = String(data.insert || '');
                
                // Vérifier que les positions sont valides
                if (from < 0 || from > docLength || to < 0 || to > docLength || from > to) {
                    console.warn('⚠️ [handleRemoteTextChange] Positions invalides, ignoré:', { from, to, docLength });
                    this.isApplyingRemote = false;
                    this.isHandlingRemoteChange = false;
                    return;
                }
                
                console.log(`🔄 [handleRemoteTextChange] Application: from=${from}, to=${to}, insert="${insert.substring(0, 20)}..."`);
                
                // Dans CodeMirror 6, on peut passer directement un objet {from, to, insert}
                // sans avoir besoin de créer un ChangeSet
                // Utiliser Transaction.remote pour marquer que c'est un changement distant
                this.editorView.dispatch({
                    changes: {
                        from: from,
                        to: to,
                        insert: insert
                    },
                    annotations: [Transaction.remote.of(true)]
                });
                
                // Forcer CodeMirror à se resynchroniser avec le DOM après le changement distant
                // Cela évite les erreurs "Invalid child in posBefore"
                this.editorView.requestMeasure();
                
                console.log('✅ [handleRemoteTextChange] Changement appliqué avec succès');
            } catch (e) {
                console.error('❌ Erreur application changement distant:', e);
                console.error('   Détails:', { from: data.from, to: data.to, insert: data.insert?.substring(0, 50) });
                
                // En cas d'erreur, forcer un rafraîchissement complet de l'éditeur
                try {
                    this.editorView.requestMeasure();
                } catch (refreshError) {
                    console.error('❌ Erreur lors du rafraîchissement:', refreshError);
                }
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
                console.log('📤 [Pusher Direct] Envoi cursor-moved:', { userId: cursorData.userId, position: cursorData.position });
                try {
                    // Utiliser trigger() directement avec Pusher (client events)
                    this.channel.trigger('client-cursor-moved', cursorData);
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
                    
                    // Vérifier que la position est valide (dans les limites du document)
                    const docLength = view.state.doc.length;
                    if (pos < 0 || pos > docLength) {
                        // Position invalide, ignorer silencieusement
                        return;
                    }
                    
                    // Essayer d'obtenir les coordonnées, mais gérer l'erreur si la "tile" n'est pas encore rendue
                    let coords;
                    try {
                        coords = view.coordsAtPos(pos);
                    } catch (e) {
                        // La position n'est pas encore rendue (rendu lazy), ignorer
                        return;
                    }
                    
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
                        /* Zone de hover plus tolérante */
                        padding: 8px 12px;
                        margin: -8px -12px;
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
                    // Utiliser trigger() directement avec Pusher (client events)
                    this.channel.trigger('client-isAlive', {
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
