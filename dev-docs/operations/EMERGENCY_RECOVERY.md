# Route de Secours d'Urgence - ADMIN

## ⚠️ CONFIDENTIEL - À CONSERVER EN SÉCURITÉ

Cette route permet de récupérer l'accès administrateur en cas de problème critique.

## Configuration

### 1. Ajouter le token secret dans `.env`

Ajoutez cette ligne dans votre fichier `.env` :

```env
EMERGENCY_RECOVERY_TOKEN=votre-token-super-secret-et-long-ici-changez-moi
```

**IMPORTANT :** 
- Utilisez un token long et aléatoire (minimum 32 caractères)
- Ne partagez JAMAIS ce token
- Ne commitez JAMAIS le fichier `.env` dans Git

### 2. Générer un token sécurisé

Vous pouvez générer un token avec cette commande :

```bash
php artisan tinker
```

Puis :
```php
echo \Illuminate\Support\Str::random(64);
```

## Accès à la route

### URL de la route

L'URL est générée automatiquement à partir de votre `APP_KEY` pour être unique à votre installation :

```
https://votre-domaine.com/emergency-recovery-[hash-aléatoire]?token=[votre-token-secret]
```

### Comment obtenir l'URL complète

**Méthode recommandée :** Utilisez la commande Artisan dédiée :

```bash
# En ligne de commande (sans Docker)
php artisan emergency:url

# Avec Docker
sudo docker exec laravel_app php artisan emergency:url
```

**Méthode alternative :** Via Tinker

```bash
# Sans Docker
php artisan tinker

# Avec Docker
sudo docker exec laravel_app php artisan tinker
```

Puis :
```php
$hash = md5(config('app.key') . 'emergency-recovery-allotata');
$token = env('EMERGENCY_RECOVERY_TOKEN');
echo "URL: " . url("/emergency-recovery-{$hash}?token={$token}");
```

**OU** utilisez la commande Artisan dédiée :

```bash
# En ligne de commande (sans Docker)
php artisan emergency:url

# Avec Docker
sudo docker exec laravel_app php artisan emergency:url
```

**OU** listez les routes :

```bash
php artisan route:list | grep emergency
```

## Fonctionnalités

### 1. Créer un nouveau compte administrateur
- Permet de créer un compte admin depuis zéro
- Email auto-vérifié pour accès immédiat
- Mot de passe minimum 8 caractères

### 2. Promouvoir un utilisateur existant
- Transforme n'importe quel utilisateur en admin
- **Vérifie automatiquement son email** s'il ne l'était pas encore (débloque la connexion)
- Utile si vous avez perdu l'accès à votre compte admin

### 3. Vérifier un email manuellement
- Formulaire par adresse email ou bouton par ligne dans la liste
- Débloque la connexion quand le lien de vérification n'a pas été reçu / cliqué
- Sans cette étape, même un admin promu ne peut pas se connecter

### 4. Se connecter directement
- Connexion immédiate sans mot de passe
- Utile pour tester ou récupérer l'accès

### 5. Importer et restaurer une sauvegarde
- Import de fichiers `.sql` ou `.sql.gz`
- Restauration complète de la base de données
- **Sécurité intégrée** :
  - ✅ `CREATE TABLE IF NOT EXISTS` : Évite les erreurs si les tables existent
  - ✅ `INSERT IGNORE` : Ignore les doublons sans erreur
  - ✅ Pas de suppression des tables existantes

## Sécurité

### Mesures de protection

1. **Token secret requis** : Impossible d'accéder sans le token
2. **Logging complet** : Toutes les actions sont enregistrées dans les logs
3. **URL aléatoire** : Le chemin contient un hash basé sur votre APP_KEY
4. **Interface discrète** : Design sobre pour ne pas attirer l'attention

### Recommandations

1. **Stockez l'URL en sécurité** : 
   - Dans un gestionnaire de mots de passe
   - Sur un support physique sécurisé
   - Jamais dans un fichier texte en clair

2. **Changez le token régulièrement** :
   - Après chaque utilisation
   - Tous les 6 mois minimum

3. **Surveillez les logs** :
   - Vérifiez régulièrement `storage/logs/laravel.log`
   - Recherchez "EMERGENCY RECOVERY" pour voir les accès

4. **Désactivez en production si possible** :
   - Commentez la route dans `routes/web.php` quand vous n'en avez pas besoin
   - Réactivez-la uniquement en cas d'urgence

## Utilisation

1. Accédez à l'URL avec le token en paramètre
2. Choisissez l'action souhaitée :
   - Créer un nouveau compte admin
   - Promouvoir un utilisateur existant
   - Se connecter directement
3. Une fois l'accès récupéré, changez immédiatement le token

## Dépannage

### Le token ne fonctionne pas

Vérifiez que :
- Le token dans l'URL correspond exactement à celui dans `.env`
- Il n'y a pas d'espaces avant/après dans le `.env`
- Vous avez bien redémarré le serveur après modification du `.env`

### L'URL ne fonctionne pas

Vérifiez que :
- La route est bien enregistrée : 
  - Sans Docker : `php artisan route:list | grep emergency`
  - Avec Docker : `sudo docker exec laravel_app php artisan route:list | grep emergency`
- Le hash correspond à votre `APP_KEY`
- Aucun middleware ne bloque l'accès

### Erreur 404

- Vérifiez que le hash dans l'URL correspond bien à celui généré
- Regénérez l'URL avec la commande ci-dessus

## Exemple d'URL

```
https://allotata.fr/emergency-recovery-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6?token=mon-super-token-secret-123456789
```

## ⚠️ RAPPEL IMPORTANT

- Cette route contourne TOUTES les sécurités normales
- Utilisez-la UNIQUEMENT en cas d'urgence absolue
- Changez le token après chaque utilisation
- Surveillez les logs pour détecter tout accès non autorisé
