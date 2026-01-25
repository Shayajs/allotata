# Configuration des sauvegardes automatiques avec Docker

## Vue d'ensemble

Pour Docker, nous utilisons une route HTTP sécurisée au lieu d'un cron classique. Cela permet de déclencher les sauvegardes depuis :
- Un cron dans le conteneur Docker
- Un cron sur l'hôte
- Un service externe (cron-job.org, etc.)
- Un autre conteneur Docker

## Configuration

### 1. Ajouter le token secret dans `.env`

```env
AUTO_BACKUP_TOKEN=votre-token-super-secret-ici-changez-moi
```

**Générez un token sécurisé :**
```bash
php artisan tinker
```
Puis :
```php
echo \Illuminate\Support\Str::random(64);
```

### 2. URL de sauvegarde automatique

L'URL de sauvegarde est :
```
http://localhost/autosave?token=votre-token-secret
```

Ou depuis l'extérieur du conteneur :
```
http://votre-domaine.com/autosave?token=votre-token-secret
```

### 3. Paramètres optionnels

- `keep` : Nombre de sauvegardes à conserver (défaut: 30)
- `description` : Description de la sauvegarde (défaut: "Sauvegarde automatique")

Exemple :
```
http://localhost/autosave?token=votre-token&keep=50&description=Sauvegarde%20quotidienne
```

## Méthodes de configuration

### Option 1 : Cron dans le conteneur Docker

#### Avec Docker Compose

Ajoutez un service cron dans votre `docker-compose.yml` :

```yaml
services:
  app:
    # ... votre configuration existante

  cron:
    image: alpine:latest
    volumes:
      - ./:/var/www/html
    working_dir: /var/www/html
    command: >
      sh -c "
        apk add --no-cache curl &&
        echo '0 2 * * * curl -s \"http://app/autosave?token=$$AUTO_BACKUP_TOKEN\" > /dev/null' | crontab - &&
        crond -f
      "
    environment:
      - AUTO_BACKUP_TOKEN=${AUTO_BACKUP_TOKEN}
    depends_on:
      - app
    restart: unless-stopped
```

#### Avec un Dockerfile personnalisé

Créez un `Dockerfile.cron` :

```dockerfile
FROM alpine:latest

RUN apk add --no-cache curl dcron

WORKDIR /app

# Créer le script de sauvegarde
RUN echo '#!/bin/sh' > /app/backup.sh && \
    echo 'curl -s "http://app/autosave?token=${AUTO_BACKUP_TOKEN}"' >> /app/backup.sh && \
    chmod +x /app/backup.sh

# Configurer le cron (sauvegarde quotidienne à 2h)
RUN echo '0 2 * * * /app/backup.sh' | crontab -

CMD ["crond", "-f"]
```

Puis dans `docker-compose.yml` :

```yaml
services:
  cron:
    build:
      context: .
      dockerfile: Dockerfile.cron
    environment:
      - AUTO_BACKUP_TOKEN=${AUTO_BACKUP_TOKEN}
    depends_on:
      - app
    restart: unless-stopped
```

### Option 2 : Cron sur l'hôte (recommandé)

Si vous avez accès au système hôte, créez un cron qui appelle l'URL :

```bash
# Éditer le crontab
crontab -e
```

Ajoutez :
```bash
# Sauvegarde quotidienne à 2h du matin
0 2 * * * curl -s "http://localhost/autosave?token=votre-token-secret" > /dev/null

# Ou toutes les 6 heures
0 */6 * * * curl -s "http://localhost/autosave?token=votre-token-secret" > /dev/null
```

**Pour Docker avec port mapping :**
```bash
# Si votre app est sur le port 8080
0 2 * * * curl -s "http://localhost:8080/autosave?token=votre-token-secret" > /dev/null
```

### Option 3 : Service externe (cron-job.org, etc.)

1. Créez un compte sur [cron-job.org](https://cron-job.org) ou similaire
2. Ajoutez une nouvelle tâche :
   - **URL** : `https://votre-domaine.com/autosave?token=votre-token-secret`
   - **Méthode** : GET
   - **Fréquence** : Selon vos besoins (quotidien, toutes les 6h, etc.)

### Option 4 : Script shell dans le conteneur

Créez un script `backup-cron.sh` :

```bash
#!/bin/sh
TOKEN="votre-token-secret"
URL="http://app/autosave?token=${TOKEN}"

curl -s "${URL}" || echo "Erreur lors de la sauvegarde"
```

Rendez-le exécutable :
```bash
chmod +x backup-cron.sh
```

Puis dans `docker-compose.yml` :

```yaml
services:
  cron:
    image: alpine:latest
    volumes:
      - ./backup-cron.sh:/app/backup.sh
    command: >
      sh -c "
        apk add --no-cache curl &&
        echo '0 2 * * * /app/backup.sh' | crontab - &&
        crond -f
      "
    depends_on:
      - app
    restart: unless-stopped
```

## Exemples de configuration complète

### Docker Compose avec cron intégré

```yaml
version: '3.8'

services:
  app:
    image: php:8.2-fpm
    # ... votre configuration

  cron:
    image: alpine:latest
    volumes:
      - ./:/var/www/html
    working_dir: /var/www/html
    command: >
      sh -c "
        apk add --no-cache curl &&
        echo '0 2 * * * curl -s \"http://app/autosave?token=$$AUTO_BACKUP_TOKEN\" > /dev/null' | crontab - &&
        crond -f
      "
    environment:
      - AUTO_BACKUP_TOKEN=${AUTO_BACKUP_TOKEN}
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

### Avec variables d'environnement

Créez un fichier `.env.docker` :

```env
AUTO_BACKUP_TOKEN=mon-super-token-secret-123456789
```

Puis dans `docker-compose.yml` :

```yaml
services:
  cron:
    env_file:
      - .env.docker
    # ... reste de la configuration
```

## Test de la route

Testez manuellement la route :

```bash
# Depuis l'hôte
curl "http://localhost/autosave?token=votre-token-secret"

# Depuis le conteneur
docker exec -it votre-conteneur curl "http://app/autosave?token=votre-token-secret"
```

Réponse attendue :
```json
{
  "success": true,
  "message": "Sauvegarde créée avec succès",
  "backup": {
    "filename": "backup_2026-01-25_14-30-00.sql",
    "size": 5242880,
    "created_at": "2026-01-25 14:30:00"
  },
  "cleaned": {
    "deleted": 2,
    "kept": 30
  }
}
```

## Sécurité

### Protection

- ✅ Token secret requis
- ✅ Logging de toutes les tentatives (valides et invalides)
- ✅ Route publique mais protégée
- ✅ Pas d'authentification admin requise (pour permettre l'appel depuis cron)

### Recommandations

1. **Utilisez un token long et aléatoire** (minimum 32 caractères)
2. **Ne commitez JAMAIS le token** dans Git
3. **Changez le token régulièrement**
4. **Surveillez les logs** pour détecter les tentatives d'accès
5. **Utilisez HTTPS en production** pour protéger le token en transit

## Dépannage

### Erreur 403 "Token invalide"

- Vérifiez que le token dans l'URL correspond exactement à celui dans `.env`
- Vérifiez qu'il n'y a pas d'espaces avant/après dans le `.env`
- Redémarrez le conteneur après modification du `.env`

### Erreur 500 "Token non configuré"

- Ajoutez `AUTO_BACKUP_TOKEN` dans votre `.env`
- Redémarrez le conteneur

### Le cron ne s'exécute pas

- Vérifiez les logs du conteneur cron : `docker logs nom-du-conteneur-cron`
- Vérifiez que le conteneur cron est bien démarré : `docker ps`
- Testez manuellement la route avec `curl`

### Erreur de connexion depuis le cron

- Vérifiez que le nom du service (`app`) correspond à celui dans `docker-compose.yml`
- Utilisez le nom du service Docker au lieu de `localhost`
- Vérifiez que les conteneurs sont sur le même réseau Docker

## Fréquences recommandées

- **Quotidien** : `0 2 * * *` (2h du matin)
- **Toutes les 6 heures** : `0 */6 * * *`
- **Toutes les 12 heures** : `0 */12 * * *`
- **Hebdomadaire** : `0 2 * * 0` (Dimanche à 2h)

## Exemple complet avec Docker Compose

```yaml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    environment:
      - AUTO_BACKUP_TOKEN=${AUTO_BACKUP_TOKEN}
    networks:
      - app-network

  cron:
    image: alpine:latest
    command: >
      sh -c "
        apk add --no-cache curl &&
        echo '0 2 * * * curl -s \"http://app/autosave?token=$$AUTO_BACKUP_TOKEN\" > /dev/null' | crontab - &&
        crond -f
      "
    environment:
      - AUTO_BACKUP_TOKEN=${AUTO_BACKUP_TOKEN}
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

Puis dans votre `.env` :
```env
AUTO_BACKUP_TOKEN=changez-moi-par-un-token-secret-long-et-aleatoire
```
