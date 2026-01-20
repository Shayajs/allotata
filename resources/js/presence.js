import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configuration de Laravel Echo avec Reverb
window.Pusher = Pusher;

let echo = null;
let heartbeatInterval = null;
let idleTimeout = null;
let lastActivity = Date.now();
const HEARTBEAT_INTERVAL = 30000; // 30 secondes
const IDLE_THRESHOLD = 120000; // 2 minutes

// Initialiser Echo si l'utilisateur est authentifié
function initializePresence() {
    // Vérifier si l'utilisateur est authentifié (vérifier la présence d'un token CSRF ou autre)
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        return; // Pas d'authentification, ne pas initialiser
    }

    // Configuration depuis les variables d'environnement Laravel
    const reverbAppId = window.REVERB_APP_ID || 'reverb-app';
    const reverbKey = window.REVERB_APP_KEY || 'reverb-key';
    const reverbHost = window.REVERB_HOST || window.location.hostname;
    const reverbPort = window.REVERB_PORT || '8080';
    const reverbScheme = window.REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

    // Supprimer temporairement les logs d'erreur WebSocket de Pusher
    const originalError = console.error;
    const suppressWebSocketErrors = (...args) => {
        // Ignorer uniquement les erreurs WebSocket de Pusher/Echo
        const errorMessage = args.join(' ');
        if (errorMessage.includes('WebSocket') && 
            (errorMessage.includes('pusher') || errorMessage.includes('reverb'))) {
            return; // Ne pas afficher ces erreurs
        }
        originalError.apply(console, args);
    };

    try {
        // Remplacer temporairement console.error
        console.error = suppressWebSocketErrors;

        echo = new Echo({
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

        // Exporter Echo globalement pour utilisation dans d'autres scripts
        window.Echo = echo;

        // Essayer de se connecter, mais ne pas afficher d'erreurs si ça échoue
        setTimeout(() => {
            try {
                // Écouter les changements de présence (si la connexion est établie)
                echo.channel('presence.users')
                    .listen('.user.presence.changed', (e) => {
                        updateUserStatus(e.user_id, e.status, e.last_activity_at);
                    });
            } catch (error) {
                // Ignorer silencieusement si la connexion n'est pas établie
                // La présence fonctionnera quand même via les heartbeats HTTP
            }
            
            // Restaurer console.error après la tentative de connexion
            console.error = originalError;
        }, 2000);

        // Démarrer les heartbeats (fonctionne même sans WebSocket)
        startHeartbeat();

        // Détecter l'activité utilisateur
        setupActivityDetection();
    } catch (error) {
        // Restaurer console.error en cas d'erreur
        console.error = originalError;
        // En cas d'erreur d'initialisation, continuer quand même avec les heartbeats HTTP
        startHeartbeat();
        setupActivityDetection();
    }
}

// Démarrer les heartbeats périodiques
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

// Envoyer un heartbeat au serveur
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
            // Mettre à jour le statut local si nécessaire
            if (data.status) {
                updateUserStatus(window.currentUserId, data.status, data.last_activity_at);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi du heartbeat:', error);
        });
}

// Détecter l'activité utilisateur pour gérer l'état idle
function setupActivityDetection() {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    
    events.forEach(event => {
        document.addEventListener(event, () => {
            lastActivity = Date.now();
            clearTimeout(idleTimeout);
            
            // Si l'utilisateur était idle et qu'il y a de l'activité, le remettre en ligne
            const statusElement = document.querySelector(`[data-user-id="${window.currentUserId}"] .presence-status`);
            if (statusElement && statusElement.dataset.status === 'idle') {
                sendHeartbeat();
            }
        }, true);
    });

    // Vérifier périodiquement si l'utilisateur est devenu idle
    setInterval(() => {
        const timeSinceActivity = Date.now() - lastActivity;
        if (timeSinceActivity >= IDLE_THRESHOLD) {
            const statusElement = document.querySelector(`[data-user-id="${window.currentUserId}"] .presence-status`);
            if (statusElement && statusElement.dataset.status === 'online') {
                // L'utilisateur est devenu idle, mais on laisse le serveur gérer cela
                // Le prochain heartbeat mettra à jour le statut
            }
        }
    }, 10000); // Vérifier toutes les 10 secondes
}

// Mettre à jour le statut d'un utilisateur dans l'UI
function updateUserStatus(userId, status, lastActivityAt) {
    // Mettre à jour tous les éléments avec data-user-id
    const elements = document.querySelectorAll(`[data-user-id="${userId}"]`);
    
    elements.forEach(element => {
        const statusElement = element.querySelector('.presence-status');
        if (statusElement) {
            statusElement.dataset.status = status;
            statusElement.className = `presence-status presence-${status}`;
            
            // Mettre à jour le titre (tooltip)
            const statusLabels = {
                'online': 'En ligne',
                'idle': 'Inactif',
                'offline': 'Hors ligne',
            };
            statusElement.title = statusLabels[status] || status;
        }

        // Mettre à jour les badges de statut
        const badgeElement = element.querySelector('.presence-badge');
        if (badgeElement) {
            badgeElement.dataset.status = status;
            badgeElement.className = `presence-badge presence-badge-${status}`;
        }
    });

    // Déclencher un événement personnalisé pour que d'autres scripts puissent réagir
    window.dispatchEvent(new CustomEvent('presence:updated', {
        detail: { userId, status, lastActivityAt }
    }));
}

// Nettoyer lors de la déconnexion
function cleanupPresence() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
    
    if (idleTimeout) {
        clearTimeout(idleTimeout);
        idleTimeout = null;
    }
    
    if (echo) {
        echo.disconnect();
        echo = null;
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
};
