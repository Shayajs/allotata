const CACHE_NAME = 'allotata-cache-v3';
const ASSETS_CACHE = 'allotata-assets-v1';
const PRECACHE = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

// Installation : pre-cache des ressources critiques
self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE))
    );
});

// Activation : nettoyage des anciens caches + prise de controle immediate
self.addEventListener('activate', (event) => {
    const VALID_CACHES = [CACHE_NAME, ASSETS_CACHE];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (!VALID_CACHES.includes(name)) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// ═══════════════════════════════════════════════════════════
//  Pre-cache a la demande (pages utilisateur)
// ═══════════════════════════════════════════════════════════

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PRECACHE_URLS') {
        const urls = event.data.urls || [];
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) => {
                return Promise.allSettled(
                    urls.map((url) =>
                        cache.match(url).then((existing) => {
                            if (existing) return;
                            return fetch(url, { credentials: 'same-origin' })
                                .then((resp) => {
                                    if (resp && resp.status === 200) {
                                        return cache.put(url, resp);
                                    }
                                })
                                .catch(() => {});
                        })
                    )
                );
            })
        );
    }
});

// ═══════════════════════════════════════════════════════════
//  Push Notifications
// ═══════════════════════════════════════════════════════════

// Réception d'une notification push
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = {
            title: 'Allo Tata',
            body: event.data.text(),
        };
    }

    const title = data.title || 'Allo Tata';
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192x192.png',
        badge: data.badge || '/icons/icon-192x192.png',
        data: {
            url: data.url || '/',
            category: data.category || 'general',
        },
        vibrate: [200, 100, 200],
        tag: data.category || 'general', // Remplace les notifs de même catégorie
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Clic sur une notification push
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Si une fenêtre/onglet de l'app est déjà ouvert, on le focus et on navigue
            for (const client of clientList) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.focus();
                    client.navigate(url);
                    return;
                }
            }
            // Sinon on ouvre un nouvel onglet
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Re-souscription automatique si le navigateur change l'endpoint (Firefox)
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        self.registration.pushManager.subscribe(event.oldSubscription.options)
            .then((newSubscription) => {
                return fetch('/push-subscription', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        endpoint: newSubscription.endpoint,
                        keys: {
                            p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(newSubscription.getKey('p256dh')))),
                            auth: btoa(String.fromCharCode.apply(null, new Uint8Array(newSubscription.getKey('auth')))),
                        },
                        content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                    }),
                });
            })
    );
});

// ═══════════════════════════════════════════════════════════
//  Cache (PWA) — Strategies par type de requete
// ═══════════════════════════════════════════════════════════

function isAsset(url) {
    return /\.(js|css|woff2?|ttf|eot|png|jpg|jpeg|gif|svg|webp|ico|avif)(\?.*)?$/i.test(url)
        || url.includes('/build/');
}

function isNavigationRequest(request) {
    return request.mode === 'navigate'
        || (request.method === 'GET' && request.headers.get('accept') && request.headers.get('accept').includes('text/html'));
}

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
        return;
    }

    const url = new URL(event.request.url);

    // Ignorer les requetes temps-reel et les APIs qui ne doivent pas etre cachees
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/broadcasting/')) {
        return;
    }

    // Endpoints JSON emploi du temps et reservations → Network-First avec cache
    if (url.pathname.match(/\/(emploi-du-temps\/events|agenda\/reservations|reservations)$/) && !isNavigationRequest(event.request)) {
        event.respondWith(
            fetch(event.request).then((response) => {
                if (response && response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            }).catch(() => caches.match(event.request).then((cached) => cached || new Response('{"events":[],"offline":true}', { headers: { 'Content-Type': 'application/json' } })))
        );
        return;
    }

    // Assets statiques (JS, CSS, images, fonts) → Cache-First + mise a jour en arriere-plan
    if (isAsset(event.request.url)) {
        event.respondWith(
            caches.open(ASSETS_CACHE).then((cache) => {
                return cache.match(event.request).then((cached) => {
                    const networkFetch = fetch(event.request).then((response) => {
                        if (response && response.status === 200) {
                            cache.put(event.request, response.clone());
                        }
                        return response;
                    }).catch(() => cached);

                    return cached || networkFetch;
                });
            })
        );
        return;
    }

    // Pages HTML → Stale-While-Revalidate (cache instantane + mise a jour reseau)
    if (isNavigationRequest(event.request)) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.match(event.request).then((cached) => {
                    const networkFetch = fetch(event.request).then((response) => {
                        if (response && response.status === 200) {
                            cache.put(event.request, response.clone());
                        }
                        return response;
                    }).catch(() => {
                        if (cached) return cached;
                        return caches.match('/offline.html');
                    });

                    // Si on a une version cachee, la servir immediatement
                    // Le reseau met a jour le cache pour la prochaine visite
                    if (cached) {
                        // Lancer la mise a jour en arriere-plan (sans attendre)
                        event.waitUntil(networkFetch.catch(() => {}));
                        return cached;
                    }

                    return networkFetch;
                });
            })
        );
        return;
    }

    // Autres GET (JSON, etc.) → Network-First avec fallback cache
    event.respondWith(
        fetch(event.request).then((response) => {
            if (response && response.status === 200) {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
            }
            return response;
        }).catch(() => caches.match(event.request))
    );
});
