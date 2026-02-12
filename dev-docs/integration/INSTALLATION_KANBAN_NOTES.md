# Installation - Kanban et Notes Collaboratives

## Prérequis

- PHP 8.1+
- Composer
- Node.js et npm
- Laravel Reverb (pour les WebSockets)

## Installation

### 1. Installation des dépendances Composer

```bash
composer require laravel/reverb
php artisan reverb:install
```

### 2. Installation des dépendances npm

Les dépendances suivantes sont déjà dans `package.json` :
- `laravel-echo` : Client WebSocket pour Laravel
- `pusher-js` : Client Pusher pour les WebSockets
- `sortablejs` : Bibliothèque pour le drag & drop du Kanban

Installer les dépendances :

```bash
npm install
```

### 3. Compilation des assets

```bash
npm run build
```

Ou en mode développement :

```bash
npm run dev
```

### 4. Exécution des migrations

```bash
php artisan migrate
```

Les migrations suivantes seront exécutées :
- `create_kanban_boards_table`
- `create_kanban_columns_table`
- `create_kanban_cards_table`
- `create_notes_table`
- `create_note_collaborators_table`
- `create_note_cursors_table`

### 5. Configuration Reverb

Ajouter les variables suivantes dans votre fichier `.env` :

```env
REVERB_APP_ID=reverb-app
REVERB_APP_KEY=reverb-key
REVERB_SECRET=reverb-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Pour la production avec HTTPS :

```env
REVERB_SCHEME=https
REVERB_PORT=443
```

### 6. Démarrer le serveur Reverb

En développement :

```bash
php artisan reverb:start
```

En production, utilisez un gestionnaire de processus comme Supervisor pour maintenir Reverb en cours d'exécution.

### 7. Configuration Broadcasting

Vérifier que `config/broadcasting.php` utilise Reverb :

```php
'default' => env('BROADCAST_DRIVER', 'reverb'),
```

Et dans `.env` :

```env
BROADCAST_DRIVER=reverb
```

## Bibliothèques chargées via CDN

Les bibliothèques suivantes sont chargées automatiquement via CDN dans les vues :

- **Alpine.js** : Framework JavaScript réactif (chargé dans `admin/layout.blade.php`)
- **SimpleMDE** : Éditeur Markdown (chargé dans `admin/notes/show.blade.php`)
- **Marked.js** : Parser Markdown (chargé dans `admin/notes/show.blade.php`)
- **SortableJS** : Drag & drop (chargé dans `admin/kanban/index.blade.php`)

Aucune installation supplémentaire n'est nécessaire pour ces bibliothèques.

## Vérification

1. Accéder à `/admin/kanban` pour voir le board Kanban
2. Accéder à `/admin/notes` pour voir la liste des notes
3. Vérifier que le serveur Reverb est démarré et fonctionne

## Notes importantes

- Le serveur Reverb doit être démarré pour que les fonctionnalités temps réel fonctionnent
- En production, configurez Supervisor ou un système similaire pour maintenir Reverb actif
- Les channels de broadcasting sont automatiquement créés lors de l'utilisation

---

# Installation - Système de Présence en Temps Réel

## Vue d'ensemble

Le système de présence permet d'afficher le statut des utilisateurs (connecté/idle/déconnecté) dans :
- Le dashboard administrateur (liste des utilisateurs)
- La messagerie (statut des interlocuteurs)
- Le dashboard entreprise (statut des membres d'équipe)

**Note importante** : Ce système utilise uniquement MySQL et des requêtes HTTP périodiques (toutes les 10 secondes). Aucun WebSocket n'est nécessaire.

## Prérequis

- PHP 8.1+
- MySQL/MariaDB
- Aucune dépendance supplémentaire requise (pas de Reverb pour la présence)

## Installation

### 1. Migration de la base de données

Exécuter la migration pour créer la table `user_presence` :

```bash
php artisan migrate
```

La migration `2026_01_25_100000_create_user_presence_table` sera exécutée.

### 2. Compilation des assets JavaScript

Si vous avez modifié les fichiers JavaScript, recompiler les assets :

```bash
npm run build
```

Ou en mode développement :

```bash
npm run dev
```

**Note** : Aucune configuration supplémentaire n'est nécessaire. Le système fonctionne uniquement avec MySQL et des requêtes HTTP.

## Fonctionnement

### Logique de statut

- **Online** : Dernière activité < 2 minutes
- **Idle** : Dernière activité entre 2 et 5 minutes
- **Offline** : Dernière activité > 5 minutes OU utilisateur déconnecté

### Mise à jour automatique

- Le middleware `TrackUserActivity` met à jour automatiquement l'activité à chaque requête authentifiée
- Le frontend envoie des heartbeats toutes les 30 secondes
- Les changements de statut sont diffusés en temps réel via WebSocket

### Nettoyage automatique

Pour nettoyer les entrées de présence obsolètes, exécuter la commande :

```bash
php artisan presence:cleanup
```

Ou configurer un cron pour l'exécuter automatiquement (par exemple, toutes les heures) :

```bash
0 * * * * cd /path/to/project && php artisan presence:cleanup
```

## Vérification

1. Se connecter avec deux comptes différents dans deux navigateurs/onglets
2. Vérifier que les badges de présence s'affichent dans :
   - `/admin/users` (dashboard admin)
   - `/messagerie` (liste des conversations)
   - `/m/{slug}` → onglet "Équipe" (dashboard entreprise)
3. Vérifier que les statuts se mettent à jour en temps réel quand un utilisateur devient inactif

## Fichiers créés/modifiés

### Nouveaux fichiers

- `database/migrations/2026_01_25_100000_create_user_presence_table.php`
- `app/Models/UserPresence.php`
- `app/Services/PresenceService.php`
- `app/Http/Middleware/TrackUserActivity.php`
- `app/Http/Controllers/PresenceController.php`
- `app/Console/Commands/CleanupPresence.php`
- `resources/js/presence.js`
- `resources/views/components/presence-badge.blade.php`

### Fichiers modifiés

- `app/Models/User.php` (ajout de la relation `presence()`)
- `bootstrap/app.php` (enregistrement du middleware)
- `routes/web.php` (routes API de présence)
- `resources/js/app.js` (import de presence.js)
- `resources/views/admin/users/index.blade.php` (affichage des statuts)
- `resources/views/messagerie/*.blade.php` (affichage des statuts)
- `resources/views/entreprise/dashboard/tabs/equipe.blade.php` (affichage des statuts)
- `resources/views/admin/layout.blade.php` (variable currentUserId)
- `resources/views/layouts/user.blade.php` (variable currentUserId)
- `resources/views/entreprise/dashboard/index.blade.php` (variable currentUserId)

## Notes importantes

- **Aucun serveur WebSocket n'est nécessaire** - le système fonctionne uniquement avec MySQL et HTTP
- Le middleware `TrackUserActivity` est appliqué globalement et met à jour l'activité automatiquement
- Les heartbeats sont envoyés toutes les 30 secondes depuis le frontend pour mettre à jour son propre statut
- Les statuts des autres utilisateurs sont vérifiés toutes les 10 secondes via une requête HTTP
- La détection d'inactivité (idle) se base sur la dernière activité enregistrée dans la base de données
- Le système est simple, fiable et ne nécessite aucune infrastructure supplémentaire
