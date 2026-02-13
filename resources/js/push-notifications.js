/**
 * Push Notifications - Client-side
 * Gestion des souscriptions Web Push (VAPID)
 */

/**
 * Vérifie si le navigateur supporte les push notifications
 */
export function isPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

/**
 * Vérifie si l'utilisateur a déjà une souscription push active
 */
export async function isPushSubscribed() {
    if (!isPushSupported()) return false;

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    return subscription !== null;
}

/**
 * Demande la permission de notifications au navigateur
 * @returns {Promise<string>} 'granted', 'denied', ou 'default'
 */
export async function requestPushPermission() {
    if (!isPushSupported()) {
        return 'unsupported';
    }
    return await Notification.requestPermission();
}

/**
 * Convertit une clé VAPID base64url en Uint8Array pour PushManager
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

/**
 * Souscrit aux notifications push via PushManager
 * @param {string} vapidPublicKey - Clé VAPID publique (base64url)
 * @returns {Promise<PushSubscription|null>}
 */
export async function subscribeToPush(vapidPublicKey) {
    if (!isPushSupported()) return null;

    const permission = await requestPushPermission();
    if (permission !== 'granted') return null;

    const registration = await navigator.serviceWorker.ready;

    // Vérifier s'il existe déjà une souscription
    let subscription = await registration.pushManager.getSubscription();
    if (subscription) {
        return subscription;
    }

    // Créer une nouvelle souscription
    subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    return subscription;
}

/**
 * Envoie la souscription push au serveur (utilisateur authentifié)
 * @param {PushSubscription} subscription
 * @param {string} csrfToken
 * @returns {Promise<boolean>}
 */
export async function sendSubscriptionToServer(subscription, csrfToken) {
    try {
        const response = await fetch('/push-subscription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                    auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                },
                content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
            }),
        });

        return response.ok;
    } catch (e) {
        console.error('Erreur envoi subscription au serveur:', e);
        return false;
    }
}

/**
 * Envoie la souscription push au serveur en tant que guest (wizard inscription)
 * @param {PushSubscription} subscription
 * @param {string} csrfToken
 * @returns {Promise<boolean>}
 */
export async function sendSubscriptionToServerGuest(subscription, csrfToken) {
    try {
        const response = await fetch('/push-subscription/guest', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                    auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                },
                content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
            }),
        });

        return response.ok;
    } catch (e) {
        console.error('Erreur envoi subscription guest au serveur:', e);
        return false;
    }
}

/**
 * Désabonne des notifications push
 * @param {string} csrfToken
 * @returns {Promise<boolean>}
 */
export async function unsubscribeFromPush(csrfToken) {
    if (!isPushSupported()) return false;

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) return true;

    // Supprimer côté serveur
    try {
        await fetch('/push-subscription', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
            }),
        });
    } catch (e) {
        console.error('Erreur suppression subscription serveur:', e);
    }

    // Désabonner côté navigateur
    await subscription.unsubscribe();
    return true;
}

/**
 * Obtient l'état actuel de la permission de notifications
 * @returns {string} 'granted', 'denied', 'default', ou 'unsupported'
 */
export function getPermissionState() {
    if (!isPushSupported()) return 'unsupported';
    return Notification.permission;
}
