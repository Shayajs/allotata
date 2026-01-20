// Système de présence simple basé sur MySQL et requêtes HTTP périodiques
// Pas de WebSockets, juste des vérifications toutes les 10 secondes

let presenceCheckInterval = null;
let heartbeatInterval = null;
const PRESENCE_CHECK_INTERVAL = 10000; // 10 secondes
const HEARTBEAT_INTERVAL = 30000; // 30 secondes pour les heartbeats

// Initialiser le système de présence
function initializePresence() {
    // Vérifier si l'utilisateur est authentifié
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken || !window.currentUserId) {
        return; // Pas d'authentification, ne pas initialiser
    }

    // Démarrer les heartbeats (mise à jour de notre propre statut)
    startHeartbeat();

    // Démarrer la vérification périodique des statuts
    startPresenceCheck();
}

// Démarrer les heartbeats pour mettre à jour notre propre statut
function startHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }

    heartbeatInterval = setInterval(() => {
        sendHeartbeat();
    }, HEARTBEAT_INTERVAL);

    // Envoyer un heartbeat immédiatement
    sendHeartbeat();
}

// Envoyer un heartbeat au serveur pour mettre à jour notre statut
function sendHeartbeat() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        return;
    }

    fetch('/api/presence/heartbeat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.content,
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
        .then(response => response.json())
        .then(data => {
            // Mettre à jour notre propre statut si nécessaire
            if (data.status && window.currentUserId) {
                updateUserStatus(window.currentUserId, data.status, data.last_activity_at);
            }
        })
        .catch(error => {
            // Ignorer silencieusement les erreurs
        });
}

// Démarrer la vérification périodique des statuts des autres utilisateurs
function startPresenceCheck() {
    if (presenceCheckInterval) {
        clearInterval(presenceCheckInterval);
    }

    // Vérifier immédiatement
    checkPresences();

    // Puis toutes les 10 secondes
    presenceCheckInterval = setInterval(() => {
        checkPresences();
    }, PRESENCE_CHECK_INTERVAL);
}

// Vérifier les statuts de tous les utilisateurs visibles
function checkPresences() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        return;
    }

    fetch('/api/presence/users', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.content,
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    })
        .then(response => response.json())
        .then(users => {
            // Mettre à jour les statuts de tous les utilisateurs
            users.forEach(user => {
                updateUserStatus(user.id, user.status, user.last_activity_at);
            });
        })
        .catch(error => {
            // Ignorer silencieusement les erreurs
        });
}

// Mettre à jour le statut d'un utilisateur dans l'UI
function updateUserStatus(userId, status, lastActivityAt) {
    // Mettre à jour tous les éléments avec data-user-id
    const elements = document.querySelectorAll(`[data-user-id="${userId}"]`);
    
    elements.forEach(element => {
        // Mettre à jour les badges de statut
        const badgeElement = element.querySelector('.presence-badge');
        if (badgeElement) {
            badgeElement.dataset.status = status;
            badgeElement.className = `presence-badge presence-badge-${status}`;
            
            // Mettre à jour le titre (tooltip)
            const statusLabels = {
                'online': 'En ligne',
                'idle': 'Inactif',
                'offline': 'Hors ligne',
            };
            badgeElement.title = statusLabels[status] || status;
        }

        // Mettre à jour les éléments avec classe presence-status
        const statusElement = element.querySelector('.presence-status');
        if (statusElement) {
            statusElement.dataset.status = status;
            statusElement.className = `presence-status presence-${status}`;
        }
    });

    // Déclencher un événement personnalisé pour que d'autres scripts puissent réagir
    window.dispatchEvent(new CustomEvent('presence:updated', {
        detail: { userId, status, lastActivityAt }
    }));
}

// Nettoyer lors de la déconnexion
function cleanupPresence() {
    if (presenceCheckInterval) {
        clearInterval(presenceCheckInterval);
        presenceCheckInterval = null;
    }
    
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePresence);
} else {
    initializePresence();
}

// Nettoyer lors de la déconnexion ou du changement de page
window.addEventListener('beforeunload', cleanupPresence);

// Exporter pour utilisation globale
window.PresenceManager = {
    initialize: initializePresence,
    updateStatus: updateUserStatus,
    cleanup: cleanupPresence,
    checkPresences: checkPresences,
};
