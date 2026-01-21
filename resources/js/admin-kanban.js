// Configuration Alpine.js pour le Kanban (sans WebSocket)
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
        
        openCreateModal() {
            // Réinitialiser le formulaire
            this.cardForm = {
                column_id: null,
                board_id: this.boardId,
                titre: '',
                description: '',
                type: 'tache',
                priorite: 'normale',
                assignee_id: null,
                couleur: null,
                due_date: null,
            };
            this.editingCardId = null;
            this.showCreateCardModal = true;
        },
        
        async saveCard() {
            // Vérifier que la colonne est sélectionnée
            if (!this.cardForm.column_id) {
                alert('Veuillez sélectionner une colonne');
                return;
            }
            
            // Vérifier que le titre est rempli
            if (!this.cardForm.titre || this.cardForm.titre.trim() === '') {
                alert('Veuillez saisir un titre');
                return;
            }
            
            const url = this.editingCardId 
                ? `/admin/kanban/cards/${this.editingCardId}`
                : '/admin/kanban/cards';
            
            const method = this.editingCardId ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.cardForm)
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    // Fermer le modal et recharger
                    this.showCreateCardModal = false;
                    this.showEditCardModal = false;
                    location.reload();
                } else {
                    // Afficher les erreurs
                    let errorMessage = 'Erreur lors de la sauvegarde';
                    if (data.message) {
                        errorMessage = data.message;
                    } else if (data.errors) {
                        const errors = Object.values(data.errors).flat();
                        errorMessage = errors.join('\n');
                    }
                    alert(errorMessage);
                    console.error('Erreur de sauvegarde:', data);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la sauvegarde: ' + error.message);
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

// Attendre qu'Alpine.js soit disponible
function waitForAlpine(callback) {
    if (window.Alpine) {
        callback();
    } else {
        document.addEventListener('alpine:init', callback);
    }
}

// Enregistrer la fonction avec Alpine.js
waitForAlpine(() => {
    window.Alpine.data('kanbanData', kanbanData);
});

// Exporter aussi globalement pour compatibilité
window.kanbanData = kanbanData;
