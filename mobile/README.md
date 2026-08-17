# Allo Tata — shell Android (Capacitor)

Application native Android : UI Pocket locale (carnet / timeline), pas le site. Première connexion via `https://sign.allotata.fr/signin?native=1`, puis jeton `allotata://handoff`. Les relances suivantes démarrent hors ligne.

## Prérequis

- Node 20+
- JDK 21 (Capacitor 7). Si absent : extraire Temurin 21 dans `~/jdk/jdk-21`
- Android SDK (API 35) — `ANDROID_HOME` ou `~/Android/Sdk`
- Compte Google Play Console

## Premier setup

```bash
cd mobile
npm install
npx cap add android   # une seule fois
npx cap sync android
```

Générer la clé d’upload (une fois, à sauvegarder hors git) :

```bash
bash scripts/generate-keystore.sh
```

## Builds signés (AAB + APK)

Depuis la racine du repo :

```bash
npm run mobile:apk       # APK de test + copie dans public/downloads/
npm run mobile:aab       # AAB Play Console
npm run mobile:release   # les deux
```

Artefacts :

- `mobile/dist/AlloTata.aab` — à uploader sur Play Console
- `mobile/dist/AlloTata.apk` — tests sideload
- `public/downloads/AlloTata.apk` — lien « Télécharger l’APK » du dashboard

## Versions

Fichier : [`version.properties`](version.properties). Tu changes **uniquement** `major` (X) et `minor` (Y).  
`patch` (Z) s’incrémente à chaque `npm run mobile:apk` / `aab` / `release`.

Exemple : `major=1` + `minor=2` → 1re compile `1.2.1`, 2456e compile `1.2.2456`.  
Si tu passes à `minor=3`, Z repart à 1 (`1.3.1`). `versionCode` Play continue d’augmenter (jamais de reset).

## Pocket (carnet offline)

L’APK démarre sur l’UI locale `mobile/pocket/` (timeline du jour), pas le site.
`npm run pocket:build` est lancé par le script de release. Sans jeton : connexion `sign.allotata.fr?native=1` puis `allotata://handoff`.

## Play Billing

Les achats passent par le plugin natif `PlayBilling` puis `POST /play-billing/verify`.
Voir [dev-docs/mobile/CAPACITOR_PLAY.md](../dev-docs/mobile/CAPACITOR_PLAY.md).
