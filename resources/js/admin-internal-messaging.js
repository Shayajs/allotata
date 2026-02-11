/**
 * Messagerie interne admin - Polling AJAX
 */

class AdminInternalMessaging {
    constructor(conversationId, currentUserId) {
        this.conversationId = conversationId;
        this.currentUserId = currentUserId;
        this.lastMessageId = 0;
        this.pollingInterval = null;
        this.typingPollingInterval = null;
        this.typingIndicatorTimer = null;
        this.uploadedFile = null;
        this.uploadedFileType = null;
        this.isPolling = false;
    }

    init() {
        // Initialiser les éléments DOM
        this.messageInput = document.getElementById('message-input');
        this.btnSend = document.getElementById('btn-send');
        this.messagesContainer = document.getElementById('messages-container');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.btnUploadImage = document.getElementById('btn-upload-image');
        this.inputFile = document.getElementById('input-file');
        this.filePreview = document.getElementById('file-preview');
        
        // Récupérer le dernier message ID
        const lastMessage = this.messagesContainer.querySelector('.message-item:last-child');
        if (lastMessage) {
            this.lastMessageId = parseInt(lastMessage.dataset.messageId) || 0;
        }

        // Événements
        this.messageInput.addEventListener('input', () => this.onTyping());
        this.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        this.btnSend.addEventListener('click', () => this.sendMessage());
        this.btnUploadImage.addEventListener('click', () => this.inputFile.click());
        this.inputFile.addEventListener('change', (e) => this.handleFileSelect(e));
        
        // File preview remove
        const filePreviewRemove = document.getElementById('file-preview-remove');
        if (filePreviewRemove) {
            filePreviewRemove.addEventListener('click', () => this.clearFilePreview());
        }

        // Démarrer le polling
        this.startPolling();

        // Gérer la visibilité de la page (désactiver polling si inactive)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });

        // Auto-resize textarea
        this.messageInput.addEventListener('input', () => {
            this.messageInput.style.height = 'auto';
            this.messageInput.style.height = this.messageInput.scrollHeight + 'px';
        });
    }

    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;

        // Polling des messages toutes les 2-3 secondes
        this.pollingInterval = setInterval(() => {
            this.pollMessages();
        }, 2500);

        // Polling des indicateurs de frappe toutes les 1-2 secondes
        this.typingPollingInterval = setInterval(() => {
            this.pollTyping();
        }, 1500);

        // Premier poll immédiat
        this.pollMessages();
        this.pollTyping();
    }

    stopPolling() {
        this.isPolling = false;
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        if (this.typingPollingInterval) {
            clearInterval(this.typingPollingInterval);
            this.typingPollingInterval = null;
        }
    }

    async pollMessages() {
        try {
            const response = await fetch(
                `/admin/api/messagerie-interne/conversations/${this.conversationId}/messages?after_id=${this.lastMessageId}`,
                {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            );

            if (!response.ok) return;

            const data = await response.json();
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(message => {
                    this.addMessage(message);
                    this.lastMessageId = Math.max(this.lastMessageId, message.id);
                });
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Erreur polling messages:', error);
        }
    }

    async pollTyping() {
        try {
            const response = await fetch(
                `/admin/api/messagerie-interne/conversations/${this.conversationId}/typing`,
                {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            );

            if (!response.ok) return;

            const data = await response.json();
            if (data.typing_users && data.typing_users.length > 0) {
                const names = data.typing_users.map(u => u.name).join(', ');
                this.typingIndicator.textContent = `${names} ${data.typing_users.length === 1 ? 'est' : 'sont'} en train d'écrire...`;
            } else {
                this.typingIndicator.textContent = '';
            }
        } catch (error) {
            console.error('Erreur polling typing:', error);
        }
    }

    onTyping() {
        // Debounce : mettre à jour l'indicateur de frappe max 1 fois/seconde
        if (this.typingIndicatorTimer) {
            clearTimeout(this.typingIndicatorTimer);
        }

        this.typingIndicatorTimer = setTimeout(async () => {
            try {
                await fetch(
                    `/admin/api/messagerie-interne/conversations/${this.conversationId}/typing`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                );
            } catch (error) {
                console.error('Erreur mise à jour typing:', error);
            }
        }, 1000);
    }

    async sendMessage() {
        const contenu = this.messageInput.value.trim();
        
        if (!contenu && !this.uploadedFile) {
            return;
        }

        // Vérifier si on répond à un message
        let replyMessageId = null;
        const replyPreview = document.getElementById('reply-preview');
        if (replyPreview && !replyPreview.classList.contains('hidden') && typeof currentReplyMessageId !== 'undefined' && currentReplyMessageId) {
            replyMessageId = currentReplyMessageId;
        }

        const messageData = {
            conversation_id: this.conversationId,
            contenu: contenu || null,
            type: this.uploadedFileType || 'texte',
            fichier: this.uploadedFile || null,
            reply_message_id: replyMessageId || null
        };

        try {
            const response = await fetch('/admin/api/messagerie-interne/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(messageData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Erreur lors de l\'envoi du message');
            }

            const data = await response.json();
            
            // Réinitialiser le formulaire
            this.messageInput.value = '';
            this.messageInput.style.height = 'auto';
            this.clearFilePreview();
            
            // Annuler la réponse si on était en train de répondre
            if (typeof cancelReply === 'function') {
                cancelReply();
            }

            // Ajouter le message immédiatement
            this.addMessage(data.message);
            this.lastMessageId = Math.max(this.lastMessageId, data.message.id);
            this.scrollToBottom();

        } catch (error) {
            console.error('Erreur envoi message:', error);
            alert('Erreur lors de l\'envoi du message: ' + error.message);
        }
    }

    async handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Afficher le preview
        const filePreviewImg = document.getElementById('file-preview-img');
        const filePreviewVideo = document.getElementById('file-preview-video');
        const filePreviewName = document.getElementById('file-preview-name');
        const filePreviewType = document.getElementById('file-preview-type');

        this.filePreview.classList.remove('hidden');

        if (file.type.startsWith('image/')) {
            filePreviewImg.src = URL.createObjectURL(file);
            filePreviewImg.classList.remove('hidden');
            filePreviewVideo.classList.add('hidden');
            this.uploadedFileType = 'image';
        } else if (file.type.startsWith('video/')) {
            filePreviewVideo.src = URL.createObjectURL(file);
            filePreviewVideo.classList.remove('hidden');
            filePreviewImg.classList.add('hidden');
            this.uploadedFileType = 'video';
        }

        filePreviewName.textContent = file.name;
        filePreviewType.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

        // Uploader le fichier
        try {
            const formData = new FormData();
            formData.append('fichier', file);
            formData.append('conversation_id', this.conversationId);

            const response = await fetch('/admin/api/messagerie-interne/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'upload du fichier');
            }

            const data = await response.json();
            this.uploadedFile = data.fichier;
            this.uploadedFileType = data.type;

        } catch (error) {
            console.error('Erreur upload fichier:', error);
            alert('Erreur lors de l\'upload du fichier: ' + error.message);
            this.clearFilePreview();
        }
    }

    clearFilePreview() {
        this.uploadedFile = null;
        this.uploadedFileType = null;
        this.filePreview.classList.add('hidden');
        this.inputFile.value = '';
    }

    addMessage(message) {
        // Vérifier si le message existe déjà
        const existingMessage = this.messagesContainer.querySelector(`[data-message-id="${message.id}"]`);
        if (existingMessage) {
            return;
        }

        // Créer l'élément de message
        const messageEl = this.createMessageElement(message);
        this.messagesContainer.appendChild(messageEl);
    }

    createMessageElement(message) {
        const isMine = message.user_id == this.currentUserId;
        const div = document.createElement('div');
        div.className = `flex ${isMine ? 'justify-end' : 'justify-start'} message-item`;
        div.dataset.messageId = message.id;

        let html = `<div class="flex items-start gap-2 max-w-[70%] ${isMine ? 'flex-row-reverse' : ''}">`;
        
        // Avatar
        if (!isMine) {
            const initial = (message.user?.name || '?')[0].toUpperCase();
            if (message.user?.photo_profil) {
                html += `<div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-r from-green-500 to-orange-500 flex-shrink-0">
                    <img 
                        src="/media/${message.user.photo_profil}" 
                        alt="${this.escapeHtml(message.user?.name || '?')}" 
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <span class="text-white font-bold text-sm hidden">${initial}</span>
                </div>`;
            } else {
                html += `<div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-r from-green-500 to-orange-500 flex-shrink-0">
                    <span class="text-white font-bold text-sm">${initial}</span>
                </div>`;
            }
        }

        html += `<div class="flex flex-col ${isMine ? 'items-end' : 'items-start'}">`;

        // Nom
        if (!isMine && message.user) {
            html += `<p class="text-xs text-slate-500 dark:text-slate-400 mb-1 px-2">${message.user.name}</p>`;
        }

        // Bulle
        html += `<div class="rounded-lg px-4 py-2 message-bubble ${isMine ? 'bg-green-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white'}" data-message-id="${message.id}">`;
        
        // Contenu
        if (message.contenu) {
            html += `<p class="whitespace-pre-wrap break-words message-content">${this.escapeHtml(message.contenu)}</p>`;
        }
        
        // Zone d'édition (pour les messages de l'utilisateur)
        if (isMine && message.contenu) {
            html += `<div class="message-edit-form hidden mt-2">
                <textarea 
                    class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                    rows="3"
                >${this.escapeHtml(message.contenu)}</textarea>
                <div class="flex gap-2 mt-2">
                    <button 
                        onclick="saveMessageEdit(${message.id})"
                        class="px-3 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition"
                    >
                        Enregistrer
                    </button>
                    <button 
                        onclick="cancelMessageEdit(${message.id})"
                        class="px-3 py-1 text-xs bg-slate-500 hover:bg-slate-600 text-white rounded transition"
                    >
                        Annuler
                    </button>
                </div>
            </div>`;
        }

        // Fichier
        if (message.fichier) {
            // Les fichiers sont accessibles via /media/ et non /storage/
            const fileUrl = message.fichier.startsWith('http') || message.fichier.startsWith('/') 
                ? message.fichier 
                : `/media/${message.fichier}`;
            if (message.type === 'image') {
                html += `<img src="${fileUrl}" alt="Image" class="mt-2 max-w-xs max-h-64 rounded-lg cursor-pointer object-cover" onclick="openImageModal('${fileUrl}')">`;
            } else if (message.type === 'video') {
                html += `<video src="${fileUrl}" controls class="mt-2 max-w-xs max-h-64 rounded-lg object-contain"></video>`;
            }
        }
        
        // Réactions
        if (message.reactions && message.reactions.length > 0) {
            // Grouper les réactions par emoji
            const reactionsGrouped = {};
            message.reactions.forEach(reaction => {
                if (!reactionsGrouped[reaction.emoji]) {
                    reactionsGrouped[reaction.emoji] = [];
                }
                reactionsGrouped[reaction.emoji].push(reaction);
            });
            
            html += '<div class="flex flex-wrap gap-1 mt-2 pt-2 border-t ' + 
                   (isMine ? 'border-green-400/50' : 'border-slate-300 dark:border-slate-600') + '">';
            Object.keys(reactionsGrouped).forEach(emoji => {
                const count = reactionsGrouped[emoji].length;
                html += `<button 
                    class="text-xs px-2 py-1 rounded-full bg-white/20 dark:bg-black/20 hover:bg-white/30 dark:hover:bg-black/30 transition"
                    onclick="toggleReaction(${message.id}, '${emoji}')"
                    title="Cliquer pour retirer votre réaction"
                >${emoji} ${count}</button>`;
            });
            html += '</div>';
        }

        html += '</div>';

        // Timestamp
        const time = new Date(message.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        const isModified = message.updated_at && message.updated_at !== message.created_at;
        html += `<div class="flex items-center gap-2 mt-1 px-2">
            <span class="text-xs text-slate-500 dark:text-slate-400">${time}${isModified ? ' <span class="italic">(modifié)</span>' : ''}</span>
        </div>`;

        // Actions (icônes SVG)
        html += '<div class="flex items-center gap-3 mt-1 px-2 message-actions opacity-0 group-hover:opacity-100 transition-opacity">';
        
        if (isMine && message.contenu) {
            html += `<button 
                class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-green-600 dark:hover:text-green-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition"
                onclick="editMessage(${message.id})"
                title="Modifier le message"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </button>`;
        }
        
        const authorName = message.user?.name || 'Utilisateur';
        const messageContent = message.contenu || '';
        
        html += `<button 
            class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition"
            onclick="replyToMessage(${message.id}, '${this.escapeHtml(authorName).replace(/'/g, "\\'")}', '${this.escapeHtml(messageContent).replace(/'/g, "\\'")}')"
            title="Répondre"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
            </svg>
        </button>`;
        
        html += `<button 
            class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition relative"
            onclick="showReactionPicker(event, ${message.id})"
            title="Réagir"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>`;
        
        html += '</div>';
        
        html += '</div></div>';

        div.innerHTML = html;
        div.classList.add('group'); // Ajouter la classe group pour le hover
        
        // Ajouter le gestionnaire de clic droit
        div.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            if (typeof showContextMenu === 'function') {
                showContextMenu(e, div);
            }
        });
        
        return div;
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Ces fonctions sont maintenant définies dans show.blade.php pour avoir accès aux routes Laravel

// Export pour utilisation globale
window.AdminInternalMessaging = AdminInternalMessaging;
