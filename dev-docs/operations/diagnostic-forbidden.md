# Diagnostic du problème "Forbidden" (403)

## Routes de diagnostic disponibles

1. **Route de diagnostic des autorisations** : `/diagnostic-auth`
   - Affiche les informations sur l'utilisateur connecté
   - Liste toutes les entreprises de l'utilisateur
   - Teste l'accès à une entreprise spécifique (avec paramètre `?slug=xxx`)

## Causes possibles du 403

### 1. Middleware IsAdmin
- **Fichier** : `app/Http/Middleware/IsAdmin.php`
- **Message** : "Accès refusé. Vous devez être administrateur."
- **Vérification** : `auth()->user()->is_admin` doit être `true`

### 2. ErrorLogController
- **Fichier** : `app/Http/Controllers/ErrorLogController.php`
- **Lignes** : 77, 94
- **Condition** : L'utilisateur doit être admin

### 3. SubscriptionController
- **Fichier** : `app/Http/Controllers/SubscriptionController.php`
- **Ligne** : 84
- **Condition** : L'utilisateur doit être gérant (`est_gerant`)

### 4. Vérifications d'autorisation dans les contrôleurs
Les contrôleurs suivants utilisent `where('user_id', $user->id)->firstOrFail()` :
- `SettingsController`
- `AgendaController`
- `ReservationController`
- `FactureController`
- `MessagerieController`

**Note** : Ces vérifications retournent un **404** (pas 403) si l'entreprise n'appartient pas à l'utilisateur.

## Comment diagnostiquer

1. **Accéder à la route de diagnostic** :
   ```
   GET /diagnostic-auth?slug=VOTRE_SLUG
   ```

2. **Vérifier les logs Laravel** :
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Vérifier les données de l'entreprise** :
   - Vérifier que `user_id` est bien défini dans la table `entreprises`
   - Vérifier que `user_id` correspond à l'ID de l'utilisateur connecté

4. **Tester les autorisations** :
   - Vérifier si l'utilisateur est admin : `auth()->user()->is_admin`
   - Vérifier si l'utilisateur est gérant : `auth()->user()->est_gerant`
   - Vérifier si l'entreprise appartient à l'utilisateur : `$entreprise->user_id === $user->id`

## Solutions possibles

### Si c'est un problème de middleware admin
- Vérifier que `is_admin` est bien défini dans la table `users`
- Vérifier que la valeur est `1` ou `true` (pas `0` ou `null`)

### Si c'est un problème de propriété d'entreprise
- Vérifier que `user_id` est bien défini dans la table `entreprises`
- Vérifier que `user_id` correspond à l'ID de l'utilisateur connecté
- Si l'entreprise n'a pas de `user_id`, il faut l'assigner

### Si c'est un problème de statut gérant
- Vérifier que `est_gerant` est bien défini dans la table `users`
- Vérifier que la valeur est `1` ou `true`
