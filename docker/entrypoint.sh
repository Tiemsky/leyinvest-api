#!/bin/sh
set -e

echo "🚀 Rôle du conteneur : ${CONTAINER_ROLE:-app}"
echo "🌐 Environnement : $APP_ENV"

# --- 1. Fixer les permissions ---
# On utilise || true pour éviter que le conteneur crash si le chown échoue (souvent le cas en lecture seule)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# --- 2. Phase d'optimisation ---
if [ "$APP_ENV" != "local" ]; then
    echo "⚡ Optimisation des caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan queue:restart || true
fi

# --- 3. Routage selon le CONTAINER_ROLE ---
case "${CONTAINER_ROLE}" in
    "worker")
        echo "👷 Démarrage du Worker (Queue: high, default)..."
        exec php artisan queue:work --queue=high,default --tries=3 --timeout=90
        ;;
    "horizon")
        echo "🌅 Démarrage de Laravel Horizon..."
        exec php artisan horizon
        ;;
    "scheduler")
        echo "⏰ Démarrage du Scheduler..."
        exec sh -c "while true; do php artisan schedule:run --no-interaction; sleep 60; done"
        ;;
    *)
        echo "🌐 Démarrage de PHP-FPM & Nginx..."
        php-fpm -D
        exec nginx -g 'daemon off;'
        ;;
esac
