// Détection PWA et gestion des classes CSS
(function() {
    'use strict';

    // Fonction pour détecter si l'app est en mode standalone (PWA installée)
    function isPWAInstalled() {
        // Vérifier le display-mode
        if (window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // Vérifier si l'app est lancée depuis l'écran d'accueil (iOS)
        if (window.navigator.standalone === true) {
            return true;
        }
        
        // Vérifier les paramètres de l'URL (pour les tests)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('pwa') === 'true') {
            return true;
        }
        
        return false;
    }

    // Fonction pour détecter si on est sur mobile
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               (window.innerWidth <= 768);
    }

    // Fonction pour détecter si on est sur tablette
    function isTabletDevice() {
        return /iPad|Android/i.test(navigator.userAgent) && window.innerWidth > 768 && window.innerWidth <= 1024;
    }

    // Ajouter les classes CSS appropriées au body
    function applyPWAClasses() {
        const body = document.body;
        const html = document.documentElement;
        
        const pwaInstalled = isPWAInstalled();
        const mobile = isMobileDevice();
        const tablet = isTabletDevice();
        
        // Classes pour PWA
        if (pwaInstalled) {
            body.classList.add('pwa-installed');
            html.classList.add('pwa-installed');
        } else {
            body.classList.add('web-mode');
            html.classList.add('web-mode');
        }
        
        // Classes pour le type d'appareil
        if (mobile) {
            body.classList.add('mobile-device');
            html.classList.add('mobile-device');
        }
        
        if (tablet) {
            body.classList.add('tablet-device');
            html.classList.add('tablet-device');
        }
        
        // Classe combinée pour navigation mobile (mobile ET pas PWA = burger menu)
        if (mobile && !pwaInstalled) {
            body.classList.add('mobile-web-navigation');
        }
        
        // Classe combinée pour navigation PWA (mobile ET PWA = bottom nav)
        if (mobile && pwaInstalled) {
            body.classList.add('mobile-pwa-navigation');
        }
    }

    // Enregistrer le service worker
    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker
                    .register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker enregistré avec succès:', registration.scope);
                        
                        // Vérifier les mises à jour périodiquement
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // Nouvelle version disponible
                                    console.log('Nouvelle version du service worker disponible');
                                }
                            });
                        });
                    })
                    .catch((error) => {
                        console.log('Échec de l\'enregistrement du service worker:', error);
                    });
            });
        }
    }

    // Gérer l'événement beforeinstallprompt pour les navigateurs compatibles
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        // Empêcher le prompt automatique
        e.preventDefault();
        // Stocker l'événement pour l'utiliser plus tard
        deferredPrompt = e;
        // Ajouter une classe pour indiquer que l'installation est possible
        document.body.classList.add('pwa-installable');
        
        // Créer un événement personnalisé pour notifier que l'installation est disponible
        window.dispatchEvent(new CustomEvent('pwa-installable'));
    });

    // Fonction pour déclencher manuellement l'installation
    window.installPWA = function() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('L\'utilisateur a accepté l\'installation de la PWA');
                } else {
                    console.log('L\'utilisateur a refusé l\'installation de la PWA');
                }
                deferredPrompt = null;
                document.body.classList.remove('pwa-installable');
            });
        }
    };

    // Appliquer les classes au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyPWAClasses);
    } else {
        applyPWAClasses();
    }

    // Réappliquer les classes si la taille de la fenêtre change (pour le responsive)
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            applyPWAClasses();
        }, 250);
    });

    // Enregistrer le service worker
    registerServiceWorker();

    // Exporter les fonctions utiles
    window.PWADetection = {
        isPWAInstalled: isPWAInstalled,
        isMobileDevice: isMobileDevice,
        isTabletDevice: isTabletDevice,
        installPWA: window.installPWA
    };
})();