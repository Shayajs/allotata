# Guide de Déploiement Production (Debian)

Ce guide explique comment déployer l'application `allo_tata` sur un serveur Debian pour la production.

## 1. Prérequis Serveur

Assurez-vous que votre serveur est à jour et dispose des paquets nécessaires.

```bash
sudo apt update && sudo apt upgrade -y
```

### Installer les dépendances (PHP 8.2, Nginx, MariaDB/MySQL, etc.)

```bash
# Ajouter le dépôt pour PHP (si nécessaire)
sudo apt install -y lsb-release ca-certificates apt-transport-https software-properties-common wget curl
curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update

# Installer PHP et les extensions requises
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl

# Installer Nginx et Supervisor
sudo apt install -y nginx supervisor

# Installer Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installer Node.js et NPM (pour compiler les assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Installation du Projet

Clonez votre dépôt dans le dossier `/var/www/allo_tata` (ou un autre dossier de votre choix, mais pensez à mettre à jour les chemins dans les fichiers de configuration).

```bash
cd /var/www
sudo git clone <URL_DE_VOTRE_REPO> allo_tata
sudo chown -R $USER:www-data allo_tata
cd allo_tata
```

### Configuration Initiale

1.  **Copier le fichier .env** :
    ```bash
    cp .env.example .env
    nano .env
    ```
    Modifiez les variables importantes :
    -   `APP_ENV=production`
    -   `APP_DEBUG=false`
    -   `APP_URL=https://votre-domaine.com`
    -   DB_*, STRIPE_*, MAIL_*, etc.

2.  **Installer les dépendances** :
    ```bash
    composer install --no-dev --optimize-autoloader
    npm install
    npm run build
    ```

3.  **Générer la clé d'application** :
    ```bash
    php artisan key:generate
    ```

4.  **Permissions** :
    ```bash
    sudo chown -R www-data:www-data storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    ```

## 3. Configuration Nginx

1.  Copiez le fichier de configuration Nginx :
    ```bash
    sudo cp deploy/production/nginx.conf /etc/nginx/sites-available/allo_tata
    ```
2.  Modifiez le fichier `/etc/nginx/sites-available/allo_tata` pour y mettre votre nom de domaine correct et vérifier la version de PHP-FPM.
3.  Activez le site :
    ```bash
    sudo ln -s /etc/nginx/sites-available/allo_tata /etc/nginx/sites-enabled/
    sudo nginx -t
    sudo systemctl reload nginx
    ```

## 4. Configuration Supervisor (Queues)

1.  Copiez le fichier de configuration Supervisor :
    ```bash
    sudo cp deploy/production/supervisor.conf /etc/supervisor/conf.d/allo_tata-worker.conf
    ```
2.  Mettez à jour et démarrez Supervisor :
    ```bash
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start all
    ```

## 5. Script de Déploiement

Pour les mises à jour futures, vous pouvez utiliser le script `deploy.sh`.

```bash
chmod +x deploy/production/deploy.sh
./deploy/production/deploy.sh
```

N'oubliez pas de configurer une tâche Cron pour le planificateur Laravel :

```bash
crontab -e
```
Ajoutez cette ligne :
```
* * * * * cd /var/www/allo_tata && php artisan schedule:run >> /dev/null 2>&1
```
