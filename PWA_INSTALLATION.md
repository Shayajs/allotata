# Guide d'installation PWA - Allo Tata

## Prérequis

### 1. Créer les icônes PWA

Le dossier `public/icons/` doit contenir les icônes suivantes :
- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png

**Recommandation :** Créez une icône principale (512x512px) avec votre logo et utilisez un outil en ligne comme [PWA Asset Generator](https://github.com/onderceylan/pwa-asset-generator) ou [RealFaviconGenerator](https://realfavicongenerator.net/) pour générer toutes les tailles.

### 2. HTTPS requis

La PWA nécessite HTTPS pour fonctionner (sauf en localhost pour le développement).

## Installation sur mobile

### Sur Android (Chrome/Samsung Internet)

1. **Ouvrez votre site** dans Chrome ou Samsung Internet
2. **Le navigateur affichera automatiquement une bannière** "Installer l'application" ou "Ajouter à l'écran d'accueil"
3. **Cliquez sur "Installer"** ou sur le menu (3 points) → "Ajouter à l'écran d'accueil"
4. **Confirmez l'installation**
5. L'application apparaîtra sur l'écran d'accueil et pourra être lancée comme une app native

**Alternative manuelle :**
- Menu (3 points) → "Installer l'application" ou "Ajouter à l'écran d'accueil"

### Sur iOS (Safari)

1. **Ouvrez votre site** dans Safari (pas dans Chrome/Firefox sur iOS)
2. **Tapez sur le bouton de partage** (carré avec flèche vers le haut)
3. **Faites défiler vers le bas** et sélectionnez **"Sur l'écran d'accueil"**
4. **Personnalisez le nom** si nécessaire et appuyez sur **"Ajouter"**
5. L'icône apparaîtra sur l'écran d'accueil et pourra être lancée comme une app native

### Sur Desktop (Chrome/Edge)

1. **Ouvrez votre site** dans Chrome ou Edge
2. **Une icône d'installation** apparaîtra dans la barre d'adresse (symbole + ou icône d'installation)
3. **Cliquez sur l'icône** et sélectionnez "Installer"
4. L'application s'ouvrira dans une fenêtre standalone

## Vérification

Une fois installée, l'application PWA :
- S'ouvre **sans la barre d'adresse du navigateur** (mode standalone)
- Possède sa **propre icône** sur l'écran d'accueil
- Fonctionne **hors ligne** (grâce au service worker)
- Se comporte comme une **application native**

## Détection dans le code

Le script `resources/js/pwa-detection.js` détecte automatiquement si l'app est installée en PWA :
- En mode PWA : la classe `pwa-installed` est ajoutée au `<body>`
- En mode web : la classe `web-mode` est ajoutée au `<body>`

Cela permet d'afficher des éléments différents selon le mode (ex: navigation en bas pour PWA, menu burger pour web mobile).

## Test en local

Pour tester en local :
1. Assurez-vous que votre serveur est accessible via `localhost` ou `127.0.0.1`
2. Ouvrez Chrome DevTools (F12)
3. Allez dans l'onglet "Application" → "Service Workers"
4. Vérifiez que le service worker est enregistré
5. Allez dans "Application" → "Manifest" pour vérifier le manifest.json

## Dépannage

### L'icône d'installation n'apparaît pas
- Vérifiez que vous êtes en HTTPS (ou localhost)
- Vérifiez que les icônes existent dans `public/icons/`
- Vérifiez que le manifest.json est accessible via `/manifest.json`
- Vérifiez la console du navigateur pour les erreurs

### Le service worker ne fonctionne pas
- Vérifiez que `public/sw.js` est accessible
- Vérifiez la console pour les erreurs de service worker
- Dans Chrome DevTools → Application → Service Workers, vérifiez l'état

### Sur iOS, ça ne fonctionne pas
- Assurez-vous d'utiliser Safari (pas Chrome/Firefox)
- Vérifiez que les meta tags Apple sont présents (dans `partials/pwa-head.blade.php`)
- Vérifiez que les icônes Apple Touch Icon sont présentes

## Fichiers importants

- `public/manifest.json` : Configuration de la PWA
- `public/sw.js` : Service Worker pour le cache offline
- `resources/js/pwa-detection.js` : Script de détection PWA
- `resources/views/partials/pwa-head.blade.php` : Meta tags PWA
- `public/icons/*.png` : Icônes de l'application (à créer)