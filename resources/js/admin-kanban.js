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

// Configuration Alpine.js pour le Kanban
function kanbanData(boardId) {
    return {
        boardId: boardId,
        showCreateCardModal: false,
        showEditCardModal: false,
        editingCardId: null,
        draggedCardId: null,
        cardForm: {
            column_id: null,
            board_id: boardId,
            titre: '',
            description: '',
            type: 'tache',
            priorite: 'normale',
            assignee_id: null,
            couleur: null,
            due_date: null,
        },
        
        init() {
            // Initialiser SortableJS pour le drag & drop
            this.initSortable();
            
            // Écouter les événements WebSocket
            this.initWebSocket();
        },
        
        initSortable() {
            // Pour chaque colonne, créer une instance SortableJS
            document.querySelectorAll('[data-column]').forEach(columnEl => {
                const columnId = columnEl.dataset.column;
                
                new Sortable(columnEl, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: (evt) => {
                        const cardId = parseInt(evt.item.dataset.cardId);
                        const newColumnId = parseInt(evt.to.dataset.column);
                        const oldColumnId = parseInt(evt.from.dataset.column);
                        const newOrder = evt.newIndex;
                        
                        this.moveCard(cardId, newColumnId, newOrder);
                    }
                });
            });
        },
        
        initWebSocket() {
            // Initialiser Echo si nécessaire
            const echo = initEchoIfNeeded();
            if (!echo) {
                console.warn('Echo n\'est pas disponible, les mises à jour en temps réel ne fonctionneront pas');
                return;
            }
            
            // Écouter les événements de broadcasting
            echo.private(`kanban.${this.boardId}`)
                .listen('.card.moved', (e) => {
                    this.handleCardMoved(e);
                })
                .listen('.card.updated', (e) => {
                    this.handleCardUpdated(e);
                });
        },
        
        handleDragStart(event, cardId) {
            this.draggedCardId = cardId;
            event.dataTransfer.effectAllowed = 'move';
        },
        
        handleDragEnd(event) {
            this.draggedCardId = null;
        },
        
        async moveCard(cardId, columnId, order) {
            try {
                const response = await fetch(`/admin/kanban/cards/${cardId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        column_id: columnId,
                        ordre: order
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    console.error('Erreur lors du déplacement de la carte');
                    location.reload(); // Recharger en cas d'erreur
                }
            } catch (error) {
                console.error('Erreur:', error);
                location.reload();
            }
        },
        
        handleCardMoved(event) {
            // Mettre à jour l'interface si une autre personne a déplacé une carte
            if (event.card.id !== this.draggedCardId) {
                location.reload(); // Simplification: recharger la page
            }
        },
        
        handleCardUpdated(event) {
            // Mettre à jour la carte dans l'interface
            const cardEl = document.querySelector(`[data-card-id="${event.card.id}"]`);
            if (cardEl) {
                // Mettre à jour le contenu de la carte
                location.reload(); // Simplification: recharger la page
            }
        },
        
        editCard(cardId) {
            // Charger les données de la carte et ouvrir le modal
            fetch(`/admin/kanban/cards/${cardId}`)
                .then(res => res.json())
                .then(data => {
                    this.cardForm = {
                        ...data.card,
                        column_id: data.card.column_id,
                    };
                    this.editingCardId = cardId;
                    this.showEditCardModal = true;
                })
                .catch(err => {
                    console.error('Erreur:', err);
                });
        },
        
        async saveCard() {
            const url = this.editingCardId 
                ? `/admin/kanban/cards/${this.editingCardId}`
                : '/admin/kanban/cards';
            
            const method = this.editingCardId ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.cardForm)
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },
        
        async syncReservations() {
            try {
                const response = await fetch('/admin/kanban/sync/reservations', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(`${data.created} carte(s) créée(s) depuis les réservations`);
                    location.reload();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },
        
        async syncTickets() {
            try {
                const response = await fetch('/admin/kanban/sync/tickets', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(`${data.created} carte(s) créée(s) depuis les tickets`);
                    location.reload();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
    };
}

// Exporter les fonctions pour utilisation globale avec Alpine.js
window.kanbanData = kanbanData;
