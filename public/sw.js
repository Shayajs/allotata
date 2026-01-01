const CACHE_NAME = 'allo-tata-v1';
const STATIC_CACHE_NAME = 'allo-tata-static-v1';
const DYNAMIC_CACHE_NAME = 'allo-tata-dynamic-v1';

// Assets statiques à mettre en cache immédiatement
const STATIC_ASSETS = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/favicon.ico',
];

// Installer le service worker et mettre en cache les assets statiques
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activer le service worker et nettoyer les anciens caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((cacheName) => {
            return (
              cacheName.startsWith('allo-tata-') &&
              cacheName !== STATIC_CACHE_NAME &&
              cacheName !== DYNAMIC_CACHE_NAME
            );
          })
          .map((cacheName) => {
            return caches.delete(cacheName);
          })
      );
    })
  );
  self.clients.claim();
});

// Stratégie de mise en cache : Cache First pour les assets statiques, Network First pour les pages
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET
  if (request.method !== 'GET') {
    return;
  }

  // Stratégie Cache First pour les assets statiques (CSS, JS, images, fonts)
  if (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|eot)$/i)
  ) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(request).then((response) => {
          // Ne mettre en cache que les réponses valides
          if (response.status === 200) {
            const responseToCache = response.clone();
            caches.open(STATIC_CACHE_NAME).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return response;
        });
      })
    );
    return;
  }

  // Stratégie Network First pour les pages HTML et API
  if (request.headers.get('accept').includes('text/html') || url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Ne mettre en cache que les réponses valides
          if (response.status === 200 && request.headers.get('accept').includes('text/html')) {
            const responseToCache = response.clone();
            caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return response;
        })
        .catch(() => {
          // En cas d'échec réseau, retourner depuis le cache
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Si pas de cache, retourner une page offline basique pour les pages HTML
            if (request.headers.get('accept').includes('text/html')) {
              return caches.match('/').then((indexCache) => {
                return indexCache || new Response('Hors ligne', {
                  status: 503,
                  headers: { 'Content-Type': 'text/plain' },
                });
              });
            }
            return new Response('Ressource non disponible hors ligne', {
              status: 503,
              headers: { 'Content-Type': 'text/plain' },
            });
          });
        })
    );
    return;
  }

  // Pour les autres requêtes, utiliser Network First
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response.status === 200) {
          const responseToCache = response.clone();
          caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
            cache.put(request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(request);
      })
  );
});