# API & Intégrations Allotata

Documentation à destination des développeurs souhaitant connecter des APIs ou exploiter les données publiques d'Allotata.

## Endpoints publics

- **Adresses** : `GET /api/address/search`, `/api/address/cities`, `/api/address/geocode` — autocomplétion et géocodage.
- **Tracking visites** : `POST /api/tracking/visite/duree`, `/api/tracking/visite/clic` — enregistrement durée et clics (sans auth).
- **Entreprise publique** : `GET /p/{slug}`, `/p/{slug}/agenda`, `/p/{slug}/services`, `/p/{slug}/produits`, `/p/{slug}/store` — pages et données publiques.
- **Réservation** : `POST /p/{slug}/reservation` — création réservation; `GET /r/{hash}`, `POST /r/{hash}/annuler` — détail et annulation.
- **Site vitrine** : `GET /w/{slug}` — site web vitrine.

## Authentification

Les routes protégées utilisent sessions Laravel + CSRF. Pour des intégrations tierces, prévoir OAuth ou clés API (à documenter selon l'évolution).

## Webhooks

- **Stripe** : `POST /stripe/webhook` — signature Stripe requise. Voir section Stripe & Paiements.
