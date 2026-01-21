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

        const messageData = {
            conversation_id: this.conversationId,
            contenu: contenu || null,
            type: this.uploadedFileType || 'texte',
            fichier: this.uploadedFile || null
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
            html += `<div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">${initial}</div>`;
        }

        html += `<div class="flex flex-col ${isMine ? 'items-end' : 'items-start'}">`;

        // Nom
        if (!isMine && message.user) {
            html += `<p class="text-xs text-slate-500 dark:text-slate-400 mb-1 px-2">${message.user.name}</p>`;
        }

        // Bulle
        html += `<div class="rounded-lg px-4 py-2 ${isMine ? 'bg-green-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white'}">`;
        
        // Contenu
        if (message.contenu) {
            html += `<p class="whitespace-pre-wrap break-words">${this.escapeHtml(message.contenu)}</p>`;
        }

        // Fichier
        if (message.fichier) {
            // Le fichier est déjà stocké dans public, donc l'URL est /storage/...
            const fileUrl = message.fichier.startsWith('http') ? message.fichier : `/storage/${message.fichier}`;
            if (message.type === 'image') {
                html += `<img src="${fileUrl}" alt="Image" class="mt-2 max-w-full h-auto rounded-lg cursor-pointer" onclick="openImageModal('${fileUrl}')">`;
            } else if (message.type === 'video') {
                html += `<video src="${fileUrl}" controls class="mt-2 max-w-full h-auto rounded-lg"></video>`;
            }
        }

        html += '</div>';

        // Timestamp
        const time = new Date(message.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        html += `<span class="text-xs text-slate-500 dark:text-slate-400 mt-1 px-2">${time}</span>`;
        
        html += '</div></div>';

        div.innerHTML = html;
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

// Fonctions globales pour les réactions (à implémenter si nécessaire)
function showReactionPicker(messageId) {
    // TODO: Afficher le picker de réactions
}

function addReaction(messageId, emoji) {
    // TODO: Ajouter une réaction
}

function toggleReaction(messageId, emoji) {
    // TODO: Toggle une réaction
}

// Export pour utilisation globale
window.AdminInternalMessaging = AdminInternalMessaging;
