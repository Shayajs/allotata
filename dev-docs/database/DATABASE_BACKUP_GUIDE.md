# Guide de gestion des sauvegardes de base de données

## Vue d'ensemble

Ce système permet de gérer complètement les sauvegardes et restaurations de votre base de données depuis l'interface d'administration.

**⚠️ IMPORTANT : Les sauvegardes incluent TOUT :**
- ✅ **Structure complète** : Toutes les tables, colonnes, index, contraintes
- ✅ **TOUTES les données** : Tous les enregistrements de toutes les tables
- ✅ **Relations** : Clés étrangères et contraintes
- ✅ **Routines** : Procédures stockées et fonctions
- ✅ **Triggers** : Tous les triggers de la base de données

**🛡️ Sécurité pour la restauration d'urgence :**
- ✅ **CREATE TABLE IF NOT EXISTS** : Évite les erreurs si les tables existent déjà
- ✅ **INSERT IGNORE** : Ignore les doublons lors de l'import (évite les erreurs de clés primaires)
- ✅ **Pas de DROP TABLE** : Les tables existantes ne sont pas supprimées

## Fonctionnalités

### Sauvegardes manuelles
- Création de sauvegardes à la demande depuis l'interface admin
- Ajout d'une description pour chaque sauvegarde
- Sauvegarde complète avec structure, données et relations
- **Vérification automatique** que les données sont bien présentes dans la sauvegarde

### Sauvegardes automatiques
- Configuration via cron pour des sauvegardes régulières
- Nettoyage automatique des anciennes sauvegardes
- Conservation d'un nombre configurable de sauvegardes

### Restauration
- Restauration complète depuis l'interface admin
- Import de sauvegardes depuis des fichiers
- Double confirmation pour éviter les erreurs

### Gestion
- Liste de toutes les sauvegardes avec métadonnées
- Téléchargement des sauvegardes
- Suppression des sauvegardes inutiles
- Nettoyage automatique des anciennes sauvegardes

## Accès

Accédez à la gestion des sauvegardes via :
- Menu admin → **Base de données**
- URL : `/admin/database`

## Configuration des sauvegardes automatiques

### 1. Exécuter la migration

```bash
php artisan migrate
```

Cela créera la table `database_backups` pour stocker les métadonnées.

### 2. Choisir votre méthode de sauvegarde automatique

#### Option A : Avec Docker (Recommandé pour Docker)

Utilisez une route HTTP sécurisée. Voir le guide complet : **[DOCKER_BACKUP_SETUP.md](./DOCKER_BACKUP_SETUP.md)**

**Configuration rapide :**

1. Ajoutez dans `.env` :
```env
AUTO_BACKUP_TOKEN=votre-token-super-secret-ici
```

2. Configurez un cron qui appelle :
```
http://localhost/autosave?token=votre-token-secret
```

**Exemple avec curl depuis l'hôte :**
```bash
# Dans votre crontab (crontab -e)
0 2 * * * curl -s "http://localhost/autosave?token=votre-token-secret" > /dev/null
```

#### Option B : Commande Artisan classique

Ajoutez cette ligne à votre crontab (éditez avec `crontab -e`) :

```bash
# Sauvegarde quotidienne à 2h du matin
0 2 * * * cd /chemin/vers/allotata && php artisan db:backup --keep=30 >> /dev/null 2>&1
```

Ou pour une sauvegarde toutes les 6 heures :

```bash
# Sauvegarde toutes les 6 heures
0 */6 * * * cd /chemin/vers/allotata && php artisan db:backup --keep=30 >> /dev/null 2>&1
```

**Important :** Remplacez `/chemin/vers/allotata` par le chemin absolu vers votre projet.

### 3. Options de la commande

```bash
php artisan db:backup [--keep=N]
```

- `--keep=N` : Nombre de sauvegardes à conserver (défaut: 10)
  - Les sauvegardes plus anciennes seront automatiquement supprimées

### 4. Vérifier que mysqldump est installé

La commande nécessite `mysqldump` qui est généralement inclus avec MySQL/MariaDB :

```bash
which mysqldump
```

Si la commande ne retourne rien, installez MySQL client :

```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# CentOS/RHEL
sudo yum install mysql
```

## Utilisation

### Créer une sauvegarde manuelle

1. Allez dans **Admin → Base de données**
2. Cliquez sur **Créer une sauvegarde**
3. (Optionnel) Ajoutez une description
4. Cliquez sur **Créer la sauvegarde**

### Restaurer une sauvegarde

⚠️ **ATTENTION :** La restauration remplace TOUTE la base de données actuelle. Cette action est IRRÉVERSIBLE.

**🛡️ Sécurité intégrée :**
- Les sauvegardes utilisent `CREATE TABLE IF NOT EXISTS` : les tables existantes ne sont pas supprimées
- Les sauvegardes utilisent `INSERT IGNORE` : les doublons sont ignorés sans erreur
- Vous pouvez restaurer même si certaines tables existent déjà

1. Allez dans **Admin → Base de données**
2. Trouvez la sauvegarde à restaurer
3. Cliquez sur le bouton **Restaurer** (icône de flèche circulaire)
4. Confirmez deux fois l'action
5. Attendez la restauration (peut prendre plusieurs minutes)

### Importer une sauvegarde

1. Allez dans **Admin → Base de données**
2. Cliquez sur **Importer une sauvegarde**
3. Sélectionnez un fichier `.sql` ou `.sql.gz`
4. Le fichier sera importé et disponible dans la liste

### Télécharger une sauvegarde

1. Allez dans **Admin → Base de données**
2. Trouvez la sauvegarde à télécharger
3. Cliquez sur le bouton **Télécharger** (icône de flèche vers le bas)

### Supprimer une sauvegarde

1. Allez dans **Admin → Base de données**
2. Trouvez la sauvegarde à supprimer
3. Cliquez sur le bouton **Supprimer** (icône de poubelle)
4. Confirmez la suppression

### Nettoyer les anciennes sauvegardes

1. Allez dans **Admin → Base de données**
2. Cliquez sur **Nettoyer les anciennes sauvegardes**
3. Entrez le nombre de sauvegardes à conserver
4. Confirmez l'action

## Emplacement des sauvegardes

Les sauvegardes sont stockées dans :
```
storage/app/backups/database/
```

Chaque sauvegarde comprend :
- Un fichier `.sql` avec le dump de la base de données
- Un fichier `.json` avec les métadonnées (description, date, taille, etc.)

## Sécurité

### Permissions

Assurez-vous que le dossier de sauvegardes a les bonnes permissions :

```bash
chmod 755 storage/app/backups/database/
```

### Accès

- Seuls les administrateurs peuvent accéder à cette interface
- Les sauvegardes contiennent toutes les données sensibles
- Ne partagez jamais les fichiers de sauvegarde publiquement

### Recommandations

1. **Sauvegardes externes** : Téléchargez régulièrement les sauvegardes et stockez-les dans un endroit sûr (cloud, serveur distant, etc.)

2. **Test de restauration** : Testez régulièrement la restauration sur un environnement de test pour vous assurer que tout fonctionne

3. **Rotation des sauvegardes** : Configurez le nettoyage automatique pour éviter de remplir le disque

4. **Surveillance** : Vérifiez régulièrement que les sauvegardes automatiques fonctionnent correctement

## Vérification des sauvegardes

### Vérifier qu'une sauvegarde contient bien des données

Utilisez le script de vérification :

```bash
./verify-backup-data.sh storage/app/backups/database/backup_2026-01-25_14-30-00.sql
```

Ce script vérifie :
- ✅ La présence de la structure (CREATE TABLE)
- ✅ La présence des données (INSERT INTO)
- ✅ Les routines et triggers
- ✅ La taille du fichier

### Vérification manuelle

Ouvrez le fichier de sauvegarde et vérifiez qu'il contient :
- Des instructions `CREATE TABLE` (structure)
- Des instructions `INSERT INTO` (données)
- Des instructions `CREATE PROCEDURE/FUNCTION` (routines)
- Des instructions `CREATE TRIGGER` (triggers)

**Exemple de contenu d'une sauvegarde complète :**
```sql
-- Structure
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données
INSERT INTO `users` (`id`, `name`, `email`, ...) VALUES
(1, 'John Doe', 'john@example.com', ...),
(2, 'Jane Smith', 'jane@example.com', ...);
```

## Dépannage

### Erreur "mysqldump: command not found"

Installez le client MySQL :
```bash
sudo apt-get install mysql-client  # Ubuntu/Debian
sudo yum install mysql            # CentOS/RHEL
```

### Erreur "Permission denied"

Vérifiez les permissions du dossier :
```bash
chmod 755 storage/app/backups/database/
chown -R www-data:www-data storage/app/backups/  # Adaptez selon votre serveur
```

### Erreur "Access denied for user"

Vérifiez les identifiants de la base de données dans votre fichier `.env` :
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_base
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

### La sauvegarde est vide

- Vérifiez que la base de données n'est pas vide
- Vérifiez les permissions d'écriture dans le dossier de sauvegardes
- Vérifiez les logs Laravel : `storage/logs/laravel.log`

### La restauration échoue

- Vérifiez que le fichier de sauvegarde n'est pas corrompu
- Vérifiez que vous avez assez d'espace disque
- Vérifiez les logs Laravel pour plus de détails
- Assurez-vous que la base de données cible est accessible

## Support

En cas de problème, consultez les logs :
- Laravel : `storage/logs/laravel.log`
- Système : `/var/log/syslog` ou `journalctl -u votre-service`
