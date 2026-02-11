# Comment Vérifier que Pusher Fonctionne

## ⚠️ Point Important : Whisper Events

Avec la nouvelle architecture **Master/Slave**, la plupart des communications se font via **whisper events** (client-client) qui **ne passent PAS par le serveur** et donc **n'apparaissent PAS dans le dashboard Pusher**.

C'est normal et attendu !

## 📡 Ce que vous verrez dans Pusher Dashboard

### Événements Serveur (visibles dans Pusher)

Ces événements sont émis par Laravel et apparaissent dans le dashboard :

1. **Connexion au Presence Channel**
   - Quand : Un utilisateur rejoint une note
   - Canal : `presence-note.{noteId}`
   - Visible dans : Debug Console → "Client Events" ou "Server Events"

2. **UserJoinedNote** (si émis)
   - Événement : `user.joined`
   - Canal : `presence-note.{noteId}`
   - Visible dans : Debug Console

### Whisper Events (NON visibles dans Pusher)

Ces événements sont envoyés directement entre clients et n'apparaissent PAS dans le dashboard :

- ✅ **text-change** : Modifications de texte (caractère par caractère)
- ✅ **cursor-moved** : Mouvements du curseur

**Pourquoi ?** Les whisper events sont des événements client-client qui ne transitent pas par le serveur Laravel, donc ils ne sont pas loggés dans Pusher.

## 🔍 Comment Vérifier que ça Fonctionne

### Méthode 1 : Console du Navigateur

1. Ouvrez la console (F12)
2. Vous devriez voir :
   ```
   Collaboration en temps réel activée (Master/Slave)
   💾 Vous êtes le Master (sauvegarde activée)  // Si vous êtes le premier
   ```

3. Tapez dans l'éditeur
4. Dans la console, filtrez par "pusher" ou "echo"
5. Vous verrez des connexions WebSocket

### Méthode 2 : Test avec 2 Onglets

1. Ouvrez la même note dans **2 onglets différents** (2 comptes)
2. Dans le premier : tapez du texte
3. Dans le deuxième : vous devriez voir le texte apparaître **instantanément**
4. Si ça marche → Pusher fonctionne ✅

### Méthode 3 : Dashboard Pusher - Debug Console

1. Allez sur [pusher.com](https://pusher.com) → Votre app
2. Cliquez sur **"Debug Console"** dans le menu
3. Dans l'onglet **"Channels"**, vous devriez voir :
   - `presence-note.2` (ou l'ID de votre note)
   - Nombre d'utilisateurs connectés

4. Dans l'onglet **"Events"**, vous verrez uniquement :
   - Les connexions au canal
   - Les événements serveur (user.joined, user.left)

5. **Vous ne verrez PAS** les whisper events (text-change, cursor-moved)

### Méthode 4 : Vérifier la Connexion

Dans la console du navigateur :

```javascript
// Vérifier Echo
window.Echo

// Vérifier le canal
window.Echo?.join(`note.2`)

// Vérifier les canaux actifs
window.Echo?.connector?.channels
```

## 🐛 Dépannage

### Si rien ne fonctionne

1. **Vérifiez les variables d'environnement** :
   ```env
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_KEY=votre_key
   PUSHER_APP_SECRET=votre_secret
   PUSHER_APP_ID=votre_app_id
   PUSHER_APP_CLUSTER=votre_cluster
   ```

2. **Vérifiez la console navigateur** :
   - Y a-t-il des erreurs ?
   - Le message "Collaboration en temps réel activée" apparaît-il ?

3. **Vérifiez le réseau** :
   - Ouvrez l'onglet Network (F12)
   - Filtrez par "pusher"
   - Voyez-vous des connexions WebSocket ?

4. **Testez l'authentification** :
   - Allez sur `/broadcasting/auth` dans votre navigateur
   - Vous devriez avoir une réponse JSON (pas une erreur 404)

### Si les whisper events ne fonctionnent pas

Les whisper events nécessitent que le canal soit un **Presence Channel**. Vérifiez que vous utilisez bien `join()` et non `private()` dans le JavaScript.

## 📊 Résumé : Que voir dans Pusher

**Vous VERREZ** :
- ✅ Les connexions au Presence Channel
- ✅ Les événements serveur (user.joined, user.left, cursor.moved si émis par le serveur)

**Vous NE VERREZ PAS** :
- ❌ Les whisper events text-change (client-client)
- ❌ Les whisper events cursor-moved (client-client)

**C'est normal !** Les whisper events sont conçus pour être privés et rapides, sans passer par le serveur.

## ✅ Test Rapide

Pour confirmer que tout fonctionne :

1. Ouvrez 2 onglets avec 2 comptes différents
2. Accédez à la même note
3. Dans le premier onglet : tapez "Hello"
4. Dans le deuxième onglet : vous devriez voir "Hello" apparaître immédiatement
5. **Si ça marche → Pusher fonctionne ! 🎉**

Le fait de ne rien voir dans le dashboard Pusher pour les modifications de texte est **normal** car elles passent par whisper events (client-client).
