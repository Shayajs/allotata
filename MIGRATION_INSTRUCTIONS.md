# Instructions pour exécuter les migrations

## Problème
La colonne `notifications_erreurs_actives` n'existe pas encore dans la table `users`.

## Solution (CHOISISSEZ UNE OPTION)

### ⭐ Option 1 : Via le navigateur (RECOMMANDÉ - Le plus simple)
1. Ouvrez votre navigateur et allez à : `http://votre-domaine.com/run_migrations.php`
2. Le script exécutera automatiquement les migrations
3. **IMPORTANT : Supprimez le fichier `run_migrations.php` après utilisation pour des raisons de sécurité !**

### Option 2 : Via phpMyAdmin ou un client MySQL
1. Ouvrez phpMyAdmin ou votre client MySQL
2. Sélectionnez votre base de données
3. Allez dans l'onglet "SQL"
4. Copiez-collez le contenu du fichier `add_notifications_erreurs_actives.sql`
5. Cliquez sur "Exécuter"

### Option 3 : Via la ligne de commande MySQL
```bash
mysql -u votre_utilisateur -p votre_base_de_donnees < add_notifications_erreurs_actives.sql
```

### Option 4 : Via Laravel Artisan (si la connexion DB fonctionne)
```bash
php artisan migrate
```

## Vérification
Après avoir exécuté la migration, la colonne `notifications_erreurs_actives` devrait être présente dans la table `users`, et la table `error_logs` devrait être créée.

Vous pouvez vérifier dans phpMyAdmin :
- Table `users` : doit avoir une colonne `notifications_erreurs_actives` (type BOOLEAN)
- Table `error_logs` : doit exister avec les colonnes appropriées

## Note
Le code a été modifié pour gérer gracieusement le cas où ces colonnes/tables n'existent pas encore, donc l'application ne plantera pas en attendant que vous exécutiez les migrations.
