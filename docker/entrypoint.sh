#!/bin/sh
set -e

# --- 1. Fixer les permissions (Essentiel après déploiement) ---
# www-data est l'utilisateur sous lequel Nginx et PHP-FPM tournent.
# Ceci garantit que Laravel peut écrire dans ses dossiers de cache et de logs.
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache


echo "Running Laravel setup..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- 2. Démarrer PHP-FPM en arrière-plan ---
echo "Starting PHP-FPM..."
php-fpm &

# --- 3. Démarrer Nginx au premier plan ---
# Le 'exec' assure que Nginx devient le PID 1 du conteneur.
echo "Starting Nginx..."
exec nginx -g 'daemon off;'
