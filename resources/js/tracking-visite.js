/**
 * Script de tracking des visites pour les pages publiques
 */

(function() {
    'use strict';

    // Variables globales
    let visiteId = null;
    let startTime = Date.now();
    let timeInterval = null;
    let clicsEnregistres = new Set();

    /**
     * Vérifier si le tracking est autorisé
     */
    function isTrackingAllowed() {
        // Vérifier le consentement cookies (localStorage)
        const cookieConsent = localStorage.getItem('allo_tata_cookie_consent');
        if (cookieConsent === 'refused') {
            return false;
        }

        // Pour les utilisateurs connectés, on vérifie via l'API
        // Par défaut, autoriser si pas de réponse (utilisateurs non connectés)
        return true;
    }

    /**
     * Initialiser le tracking
     */
    function initTracking() {
        // Ne tracker que sur les pages /p/{slug}*
        if (!window.location.pathname.match(/^\/p\/[^\/]+/)) {
            return;
        }

        // Vérifier le consentement
        if (!isTrackingAllowed()) {
            return;
        }

        // Extraire le slug de l'entreprise depuis l'URL
        const slugMatch = window.location.pathname.match(/^\/p\/([^\/]+)/);
        if (!slugMatch) {
            return;
        }

        const slug = slugMatch[1];

        // Enregistrer la durée toutes les 5 secondes
        timeInterval = setInterval(() => {
            enregistrerDuree(slug);
        }, 5000);

        // Enregistrer la durée finale quand l'utilisateur quitte la page
        window.addEventListener('beforeunload', () => {
            enregistrerDuree(slug, true);
        });

        // Enregistrer la durée toutes les secondes en arrière-plan
        setInterval(() => {
            enregistrerDuree(slug);
        }, 1000);

        // Détecter les clics sur les services et produits
        document.addEventListener('click', (e) => {
            detecterClic(e, slug);
        });
    }

    /**
     * Enregistrer la durée de visite
     */
    function enregistrerDuree(slug, isFinal = false) {
        // Vérifier le consentement avant d'enregistrer
        if (!isTrackingAllowed()) {
            return;
        }

        const duree = Math.floor((Date.now() - startTime) / 1000);

        // Ne rien faire si moins de 1 seconde (éviter les spams)
        if (duree < 1 && !isFinal) {
            return;
        }

        const url = `/api/tracking/visite/duree`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const data = {
            slug: slug,
            duree: duree,
            is_final: isFinal,
            _token: csrfToken
        };

        // Utiliser sendBeacon pour beforeunload
        if (isFinal) {
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('slug', slug);
                formData.append('duree', duree);
                formData.append('is_final', '1');
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                
                navigator.sendBeacon(url, formData);
            } else {
                // Fallback pour les navigateurs qui ne supportent pas sendBeacon
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data),
                    keepalive: true
                }).catch(() => {
                    // Ignorer les erreurs en cas de fermeture de page
                });
            }
        } else {
            // Requête normale
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.visite_id) {
                    visiteId = data.visite_id;
                }
            })
            .catch(error => {
                // Ignorer les erreurs silencieusement
                console.debug('Erreur tracking durée:', error);
            });
        }

        // Arrêter l'intervalle si final
        if (isFinal && timeInterval) {
            clearInterval(timeInterval);
        }
    }

    /**
     * Détecter les clics sur les services et produits
     */
    function detecterClic(event, slug) {
        // Vérifier le consentement avant d'enregistrer
        if (!isTrackingAllowed()) {
            return;
        }

        const target = event.target.closest('[data-service-id], [data-produit-id], [data-tracking-service], [data-tracking-produit]');
        
        if (!target) {
            return;
        }

        let type = null;
        let itemId = null;
        let itemNom = null;

        // Vérifier les attributs data-service-id ou data-tracking-service
        if (target.dataset.serviceId || target.closest('[data-service-id]')) {
            const serviceElement = target.dataset.serviceId ? target : target.closest('[data-service-id]');
            type = 'service';
            itemId = serviceElement.dataset.serviceId;
            itemNom = serviceElement.dataset.serviceNom || serviceElement.textContent.trim().substring(0, 50);
        }
        // Vérifier les attributs data-produit-id ou data-tracking-produit
        else if (target.dataset.produitId || target.closest('[data-produit-id]')) {
            const produitElement = target.dataset.produitId ? target : target.closest('[data-produit-id]');
            type = 'produit';
            itemId = produitElement.dataset.produitId;
            itemNom = produitElement.dataset.produitNom || produitElement.textContent.trim().substring(0, 50);
        }

        if (!type || !itemId) {
            return;
        }

        // Créer une clé unique pour éviter les doublons
        const clicKey = `${type}-${itemId}`;
        
        if (clicsEnregistres.has(clicKey)) {
            return; // Déjà enregistré dans cette session
        }

        clicsEnregistres.add(clicKey);

        // Enregistrer le clic
        fetch('/api/tracking/visite/clic', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                slug: slug,
                type: type,
                item_id: itemId,
                item_nom: itemNom
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.visite_id) {
                visiteId = data.visite_id;
            }
        })
        .catch(error => {
            console.debug('Erreur tracking clic:', error);
        });
    }

    // Initialiser le tracking quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTracking);
    } else {
        initTracking();
    }
})();
