#!/bin/sh
set -e

echo " Environment: $APP_ENV"

# --- 1. Fixer les permissions ---
# On cible uniquement les dossiers nécessaires pour ne pas ralentir le démarrage
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- 2. Configuration spécifique par environnement ---

if [ "$APP_ENV" = "local" ]; then
    echo "🛠️ Mode Local : On vide les caches pour le développement..."
    php artisan optimize:clear
else
    echo " Mode $APP_ENV : Optimisation des performances..."
    # En Prod/Staging, on génère les caches pour une vitesse maximale
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Optionnel : lancer les migrations automatiquement en staging seulement
    if [ "$APP_ENV" = "staging" ]; then
        echo "Running migrations..."
        php artisan migrate --force
    fi
fi

# --- 3. Gestion des processus ---

echo "Starting PHP-FPM..."
# Démarrage de PHP-FPM en mode démon (arrière-plan)
php-fpm -D

echo "Starting Nginx..."
# 'exec' remplace le script shell par le processus Nginx.
# Nginx devient le PID 1 et recevra correctement les signaux d'arrêt (SIGTERM) de Docker.
exec nginx -g 'daemon off;'
