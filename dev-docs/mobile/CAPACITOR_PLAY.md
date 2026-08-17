# Capacitor + Google Play — Allo Tata

Build : `npm run mobile:apk` (ou `mobile:aab` / `mobile:release`). JDK 21 requis (Capacitor 7) — le script utilise `~/jdk/jdk-21` s’il existe.

## Parcours app

1. L’APK charge `https://sign.allotata.fr/signin` (inscription : `/signup`).
2. Cookie de session `SESSION_DOMAIN=.allotata.fr`.
3. Redirection post-login vers `dash.allotata.fr`.
4. Un client Capacitor qui touche l’apex `/` est renvoyé vers login ou dashboard.

User-Agent ajouté : `AlloTataApp/1.0`. Header accepté : `X-Capacitor: 1`.

## Paiements

Dans l’APK, les abonnements numériques passent par **Google Play Billing** (exigence Play). Stripe reste le chemin web / desktop. Google Pay dans Stripe Elements est activé pour le checkout navigateur uniquement.

| Grant | productId Play (défaut) |
|--------|-------------------------|
| Premium | `fr.allotata.premium` |
| Site vitrine | `fr.allotata.site_web` |
| Multi-personnes | `fr.allotata.multi_personnes` |

Créer ces abonnements dans Play Console (même package `fr.allotata.app`).

### Vérification serveur

1. Déposer le JSON du compte de service Android Publisher dans `storage/app/google/play-service-account.json` (gitignored).
2. Activer l’API **Google Play Android Developer**.
3. Lier le compte de service à Play Console → Accès API.
4. Variables : voir `.env.example` (`PLAY_*`).

Endpoint authentifié : `POST /play-billing/verify`  
Notifications temps réel : `POST /webhooks/play-billing` (Pub/Sub RTDN).

## Digital Asset Links

`GET /.well-known/assetlinks.json` lit `PLAY_SHA256_FINGERPRINTS` (certificat d’upload, puis aussi celui de Play App Signing).

Après génération du keystore :

```bash
keytool -list -v -keystore mobile/keystore/allotata-upload.jks -alias allotata-upload
```

Copier le SHA-256 dans `PLAY_SHA256_FINGERPRINTS`.

## Soumission Play Console

1. Créer l’application `fr.allotata.app`.
2. Uploader `mobile/dist/AlloTata.aab`.
3. Fiche store, classification, politique de confidentialité (`https://allotata.fr`).
4. Déclarer les abonnements in-app.
5. Track interne → production.
