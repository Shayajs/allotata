#!/bin/bash

# Configuration
PROJECT_DIR="/var/www/allo_tata"
USER="www-data"

echo "Début du déploiement..."

# Aller dans le répertoire du projet
cd $PROJECT_DIR || exit

# Mettre le site en maintenance
php artisan down || true

# Récupérer les derniers changements
git pull origin main

# Installer les dépendances PHP
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Installer les dépendances JS et compiler les assets
npm install
npm run build

# Exécuter les migrations de base de données
php artisan migrate --force

# Vider et mettre en cache la configuration
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Redémarrer les workers de file d'attente
php artisan queue:restart

# Sortir du mode maintenance
php artisan up

# Ajuster les permissions (si nécessaire)
chown -R $USER:$USER $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage $PROJECT_DIR/bootstrap/cache

echo "Déploiement terminé !"
