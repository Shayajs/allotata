// Importer Echo et Pusher
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configuration de Laravel Echo avec Reverb
window.Pusher = Pusher;

// Initialiser Echo si nécessaire
function initEchoIfNeeded() {
    if (typeof window.Echo !== 'undefined') {
        return window.Echo;
    }
    
    if (Echo && Pusher) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            return null;
        }
        
        const reverbAppId = window.REVERB_APP_ID || 'reverb-app';
        const reverbKey = window.REVERB_APP_KEY || 'reverb-key';
        const reverbHost = window.REVERB_HOST || window.location.hostname;
        const reverbPort = window.REVERB_PORT || '8080';
        const reverbScheme = window.REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');
        
        try {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: reverbKey,
                wsHost: reverbHost,
                wsPort: reverbPort,
                wssPort: reverbPort,
                forceTLS: reverbScheme === 'https',
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                    },
                },
            });
            return window.Echo;
        } catch (error) {
            console.error('Erreur lors de l\'initialisation d\'Echo:', error);
            return null;
        }
    }
    
    return null;
}

// Configuration Alpine.js pour l'éditeur de notes collaboratives
function notesEditor(noteId) {
    return {
        noteId: noteId,
        noteTitle: '',
        noteContent: '',
        renderedContent: '',
        simplemde: null,
        saveTimeout: null,
        cursorUpdateTimeout: null,
        collaborators: [],
        cursors: {},
        
        init() {
            // Initialiser le contenu depuis le textarea si disponible
            const editorEl = document.getElementById('note-editor');
            if (editorEl && editorEl.value) {
                this.noteContent = editorEl.value;
            }
            
            // Initialiser SimpleMDE
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:init:before-simplemde',message:'about to create SimpleMDE',data:{editorElExists:!!editorEl,editorElId:editorEl?.id},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
            // #endregion
            
            this.simplemde = new SimpleMDE({
                element: editorEl,
                initialValue: this.noteContent || '',
                spellChecker: false,
                placeholder: 'Commencez à écrire votre note...',
                toolbar: [
                    'bold', 'italic', 'strikethrough', '|',
                    'heading-1', 'heading-2', 'heading-3', '|',
                    'code', 'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', 'table', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ],
            });
            
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:init:after-simplemde',message:'SimpleMDE created',data:{simplemdeExists:!!this.simplemde,codemirrorExists:!!(this.simplemde?.codemirror),wrapperExists:!!(this.simplemde?.codemirror?.getWrapperElement?.())},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
            // #endregion
            
            // Appliquer le thème initial après un court délai pour laisser SimpleMDE créer son DOM
            setTimeout(() => {
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:init:delayed-updateTheme',message:'calling updateTheme after delay',data:{},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
                // #endregion
                this.updateTheme();
            }, 100);
            
            // Observer les changements de thème
            const observer = new MutationObserver(() => {
                // Vérifier que SimpleMDE est prêt avant de mettre à jour le thème
                if (this.simplemde && this.simplemde.codemirror) {
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:init:mutation-observer',message:'mutation observer triggered updateTheme',data:{},timestamp:Date.now(),sessionId:'debug-session',runId:'run2',hypothesisId:'E'})}).catch(()=>{});
                    // #endregion
                    this.updateTheme();
                }
            });
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Écouter les changements
            this.simplemde.codemirror.on('change', () => {
                this.noteContent = this.simplemde.value();
                this.debouncedSave();
            });
            
            // Écouter les mouvements du curseur
            this.simplemde.codemirror.on('cursorActivity', () => {
                this.debouncedCursorUpdate();
            });
            
            // Initialiser le rendu Markdown
            this.updatePreview();
            
            // Écouter les événements WebSocket
            this.initWebSocket();
        },
        
        updateTheme() {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:entry',message:'updateTheme called',data:{simplemdeExists:!!this.simplemde,codemirrorExists:!!(this.simplemde?.codemirror)},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
            // #endregion
            
            // Détecter si on est en mode sombre
            const isDark = document.documentElement.classList.contains('dark');
            
            if (this.simplemde && this.simplemde.codemirror) {
                // Mettre à jour le thème CodeMirror
                const wrapper = this.simplemde.codemirror.getWrapperElement();
                
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:wrapper',message:'wrapper element check',data:{wrapperExists:!!wrapper,wrapperType:wrapper?.constructor?.name},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
                // #endregion
                
                if (!wrapper) {
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:early-exit',message:'wrapper is null, exiting early',data:{},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
                    // #endregion
                    return;
                }
                
                // Utiliser directement le wrapper Element qui EST le CodeMirror
                const editor = wrapper.querySelector('.CodeMirror') || wrapper;
                
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:editor',message:'editor element check',data:{editorExists:!!editor,editorType:editor?.constructor?.name,usedFallback:!wrapper.querySelector('.CodeMirror')},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
                // #endregion
                
                if (!editor || !editor.classList) {
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:editor-null',message:'editor is null or has no classList',data:{editor:editor,editorType:typeof editor},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
                    // #endregion
                    return;
                }
                
                if (isDark) {
                    editor.classList.add('cm-s-material');
                    editor.style.backgroundColor = '#1e293b';
                    editor.style.color = '#e2e8f0';
                } else {
                    editor.classList.remove('cm-s-material');
                    editor.style.backgroundColor = '#ffffff';
                    editor.style.color = '#1e293b';
                }
                
                // Rafraîchir l'éditeur pour appliquer les changements
                this.simplemde.codemirror.refresh();
                
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:success',message:'theme updated successfully',data:{isDark:isDark},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
                // #endregion
            } else {
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/8dac8818-4e86-487b-a651-bf0cced01d9a',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'admin-notes.js:updateTheme:no-simplemde',message:'simplemde or codemirror not available',data:{simplemde:!!this.simplemde,codemirror:!!(this.simplemde?.codemirror)},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
                // #endregion
            }
        },
        
        initWebSocket() {
            // Initialiser Echo si nécessaire
            const echo = initEchoIfNeeded();
            if (!echo) {
                console.warn('Echo n\'est pas disponible, les mises à jour en temps réel ne fonctionneront pas');
                return;
            }
            
            // Écouter les événements de broadcasting
            echo.private(`note.${this.noteId}`)
                .listen('.content.updated', (e) => {
                    this.handleContentUpdated(e);
                })
                .listen('.cursor.moved', (e) => {
                    this.handleCursorMoved(e);
                })
                .listen('.user.joined', (e) => {
                    this.handleUserJoined(e);
                })
                .listen('.user.left', (e) => {
                    this.handleUserLeft(e);
                });
        },
        
        debouncedSave() {
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.saveContent();
            }, 2000); // Sauvegarder après 2 secondes d'inactivité
        },
        
        debouncedCursorUpdate() {
            clearTimeout(this.cursorUpdateTimeout);
            this.cursorUpdateTimeout = setTimeout(() => {
                this.updateCursor();
            }, 200); // Mettre à jour le curseur toutes les 200ms max
        },
        
        async saveContent() {
            try {
                const response = await fetch(`/admin/notes/${this.noteId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        contenu_markdown: this.noteContent
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.updatePreview();
                }
            } catch (error) {
                console.error('Erreur lors de la sauvegarde:', error);
            }
        },
        
        async updateTitle() {
            try {
                const response = await fetch(`/admin/notes/${this.noteId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        titre: this.noteTitle
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    console.error('Erreur lors de la mise à jour du titre');
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },
        
        async updateCursor() {
            const cursor = this.simplemde.codemirror.getCursor();
            const pos = this.simplemde.codemirror.indexFromPos(cursor);
            
            try {
                await fetch(`/admin/notes/${this.noteId}/cursor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        position: pos
                    })
                });
            } catch (error) {
                // Ignorer les erreurs de mise à jour du curseur
            }
        },
        
        handleContentUpdated(event) {
            // Si le contenu a été mis à jour par un autre utilisateur
            if (event.user.id !== window.currentUserId) {
                const currentContent = this.simplemde.value();
                // Stratégie simple: dernier write wins
                // Pour une vraie collaboration, il faudrait implémenter OT ou CRDT
                if (event.note.contenu_markdown !== currentContent) {
                    // Demander confirmation avant d'écraser
                    if (confirm('La note a été modifiée par un autre utilisateur. Voulez-vous charger la dernière version ?')) {
                        this.simplemde.value(event.note.contenu_markdown);
                        this.noteContent = event.note.contenu_markdown;
                        this.updatePreview();
                    }
                }
            }
        },
        
        handleCursorMoved(event) {
            // Afficher le curseur d'un autre utilisateur
            if (event.user.id !== window.currentUserId) {
                this.cursors[event.user.id] = {
                    user: event.user,
                    position: event.cursor.position,
                    color: this.getUserColor(event.user.id)
                };
                this.renderCursors();
            }
        },
        
        handleUserJoined(event) {
            // Ajouter l'utilisateur à la liste des collaborateurs
            if (!this.collaborators.find(c => c.id === event.user.id)) {
                this.collaborators.push(event.user);
            }
        },
        
        handleUserLeft(event) {
            // Retirer l'utilisateur de la liste
            this.collaborators = this.collaborators.filter(c => c.id !== event.user.id);
            delete this.cursors[event.user.id];
            this.renderCursors();
        },
        
        getUserColor(userId) {
            // Générer une couleur basée sur l'ID utilisateur
            const colors = [
                '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
                '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
            ];
            return colors[userId % colors.length];
        },
        
        renderCursors() {
            // Supprimer les curseurs existants
            document.querySelectorAll('.collaborator-cursor').forEach(el => el.remove());
            
            // Afficher les curseurs des autres utilisateurs
            Object.values(this.cursors).forEach(cursor => {
                const pos = this.simplemde.codemirror.posFromIndex(cursor.position);
                const coords = this.simplemde.codemirror.charCoords(pos);
                
                const cursorEl = document.createElement('div');
                cursorEl.className = 'collaborator-cursor';
                cursorEl.style.backgroundColor = cursor.color;
                cursorEl.style.left = coords.left + 'px';
                cursorEl.style.top = coords.top + 'px';
                
                const nameEl = document.createElement('div');
                nameEl.className = 'collaborator-name';
                nameEl.style.backgroundColor = cursor.color;
                nameEl.style.color = 'white';
                nameEl.textContent = cursor.user.name;
                cursorEl.appendChild(nameEl);
                
                const editorEl = this.simplemde.codemirror.getWrapperElement();
                editorEl.appendChild(cursorEl);
            });
        },
        
        updatePreview() {
            if (typeof marked !== 'undefined') {
                this.renderedContent = marked.parse(this.noteContent || '');
            } else {
                this.renderedContent = this.noteContent.replace(/\n/g, '<br>');
            }
        }
    };
}

// Exporter les fonctions pour utilisation globale avec Alpine.js
window.notesEditor = notesEditor;
