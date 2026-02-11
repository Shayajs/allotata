const CACHE_NAME = 'allotata-cache-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    // On ajoutera ici les CSS/JS principaux si on a leurs noms exacts
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Stratégie : Network First (Réseau en priorité, Cache en secours)
// Cela permet d'avoir toujours la version la plus récente du site, mais de fonctionner hors-ligne si besoin.
self.addEventListener('fetch', (event) => {
    // On ignore les requêtes non-GET (POST, PUT, etc.) et les extensions chrome
    if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                // Si la réponse est valide, on la met en cache pour plus tard
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // Si le réseau échoue (hors-ligne), on cherche dans le cache
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Fallback optionnel : si on demande une page HTML et qu'elle n'est pas en cache,
                    // on pourrait renvoyer la page d'accueil ou une page "offline" générique.
                    if (event.request.headers.get('accept').includes('text/html')) {
                        return caches.match('/');
                    }
                });
            })
    );
});
