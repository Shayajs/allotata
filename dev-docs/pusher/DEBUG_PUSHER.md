# Guide de Débogage Pusher pour les Notes Collaboratives

## Ce qu'on envoie à Pusher

Lorsque vous travaillez sur une note collaborative, voici ce qui est envoyé à Pusher :

### 1. Canal Privé

Tous les événements sont envoyés sur un **canal privé** :
```
private-note.{noteId}
```

Exemple : `private-note.2` pour la note avec l'ID 2.

### 2. Événements Émis

#### A. `content.updated` (Mise à jour du contenu)
**Quand** : Chaque fois qu'une note est sauvegardée (toutes les 2 secondes d'inactivité)

**Données envoyées** :
```json
{
  "note": {
    "id": 2,
    "titre": "Ma Note",
    "contenu_markdown": "# Titre\nContenu...",
    "created_by": 1,
    "updated_by": 1,
    "updated_at": "2026-01-20T22:00:00.000000Z"
  },
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean@example.com"
  }
}
```

**Code** : `app/Events/NoteContentUpdated.php`
**Émission** : `app/Http/Controllers/Admin/NotesController.php:128`

---

#### B. `cursor.moved` (Mouvement du curseur)
**Quand** : Chaque fois qu'un utilisateur déplace son curseur (toutes les 200ms max)

**Données envoyées** :
```json
{
  "note": {
    "id": 2,
    "titre": "Ma Note"
  },
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean@example.com"
  },
  "cursor": {
    "id": 5,
    "user_id": 1,
    "note_id": 2,
    "position": 42,
    "selection_start": null,
    "selection_end": null,
    "updated_at": "2026-01-20T22:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "Jean Dupont"
    }
  }
}
```

**Code** : `app/Events/NoteCursorMoved.php`
**Émission** : `app/Http/Controllers/Admin/NotesController.php:175`

---

#### C. `user.joined` (Utilisateur rejoint)
**Quand** : Quand un utilisateur ouvre une note

**Données envoyées** :
```json
{
  "note": {
    "id": 2,
    "titre": "Ma Note"
  },
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean@example.com"
  }
}
```

**Code** : `app/Events/UserJoinedNote.php`
**Émission** : `app/Http/Controllers/Admin/NotesController.php:51` et `:102`

---

#### D. `user.left` (Utilisateur part)
**Quand** : Quand un utilisateur ferme une note

**Données envoyées** :
```json
{
  "note": {
    "id": 2
  },
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean@example.com"
  }
}
```

**Code** : `app/Events/UserLeftNote.php`
**Émission** : `app/Http/Controllers/Admin/NotesController.php:144`

---

## Comment vérifier que Pusher fonctionne

### Méthode 1 : Console du navigateur

1. Ouvrez la console du navigateur (F12)
2. Allez sur une note collaborative
3. Vous devriez voir :
   ```
   Collaboration en temps réel activée
   ```

4. Dans l'onglet **Network**, filtrez par "pusher" ou "channel"
5. Vous devriez voir des requêtes vers Pusher

### Méthode 2 : Dashboard Pusher.com

1. Connectez-vous sur [pusher.com](https://pusher.com)
2. Allez dans votre application
3. Cliquez sur **Debug Console** dans le menu latéral
4. Vous verrez en temps réel :
   - Les événements reçus
   - Les canaux actifs
   - Les messages envoyés

### Méthode 3 : Console JavaScript

Dans la console du navigateur, tapez :
```javascript
// Vérifier que Echo est initialisé
window.Echo

// Voir les canaux actifs
window.Echo?.connector?.channels

// Forcer un test d'événement
window.Echo?.private('note.2')?.whisper('test', { message: 'hello' })
```

### Méthode 4 : Logs Laravel

Vérifiez les logs Laravel pour voir si les événements sont émis :
```bash
tail -f storage/logs/laravel.log | grep -i pusher
```

### Méthode 5 : Test manuel

1. Ouvrez la même note dans **2 onglets différents** (avec 2 comptes utilisateurs différents)
2. Dans le premier onglet, modifiez le contenu
3. Dans le deuxième onglet, vous devriez voir :
   - Le contenu se mettre à jour automatiquement
   - Le curseur du premier utilisateur apparaître

---

## Vérification de la configuration

### Variables d'environnement requises

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=votre_app_id
PUSHER_APP_KEY=votre_key
PUSHER_APP_SECRET=votre_secret
PUSHER_APP_CLUSTER=votre_cluster
```

### Vérifier dans le navigateur

Dans la console :
```javascript
// Vérifier les clés Pusher
window.PUSHER_APP_KEY
window.PUSHER_APP_CLUSTER
```

---

## Problèmes courants

### 1. "Pusher non configuré"
**Cause** : `PUSHER_APP_KEY` n'est pas défini dans `.env`
**Solution** : Vérifiez vos variables d'environnement

### 2. "401 Unauthorized" dans la console
**Cause** : Problème d'authentification sur le canal privé
**Solution** : Vérifiez que `/broadcasting/auth` fonctionne et que le CSRF token est valide

### 3. Les événements ne sont pas reçus
**Causes possibles** :
- Le canal privé n'est pas autorisé (vérifiez `routes/channels.php`)
- Les clés Pusher sont incorrectes
- Le cluster ne correspond pas

### 4. Les curseurs ne s'affichent pas
**Cause** : Les événements `cursor.moved` ne sont pas reçus ou les curseurs ne sont pas dessinés
**Solution** : Vérifiez la console pour les erreurs JavaScript

---

## Test complet

Pour tester complètement le système :

1. **Ouvrir 2 navigateurs/onglets** avec 2 comptes différents
2. **Accéder à la même note** dans les deux
3. **Dans le premier** :
   - Taper du texte → devrait apparaître dans le 2ème après 2 secondes
   - Bouger le curseur → devrait apparaître dans le 2ème
4. **Dans le deuxième** :
   - Vérifier que le curseur du 1er apparaît
   - Vérifier que les modifications arrivent en temps réel

Si tout fonctionne, vous verrez :
- ✅ Les modifications en temps réel
- ✅ Les curseurs colorés des autres utilisateurs
- ✅ Le statut "En ligne" avec les avatars

---

## Monitoring Pusher

Dans le dashboard Pusher, vous pouvez voir :
- **Messages/Day** : Nombre de messages envoyés
- **Peak Connections** : Nombre max de connexions simultanées
- **Events** : Liste des événements en temps réel
- **Channels** : Canaux actifs

C'est très utile pour vérifier que les événements sont bien envoyés !
