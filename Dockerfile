# Phase 1: Build (Utiliser l'image composer pour installer les dépendances)
FROM composer:2.7 as build

# Définir l'utilisateur pour le build pour des raisons de sécurité
USER root

WORKDIR /app
COPY composer.json composer.lock ./
# Installation en mode production (sans les dépendances de développement)
RUN composer install --no-dev --optimize-autoloader

# --- Phase 2: Production (Image finale basée sur PHP-FPM Alpine) ---
FROM php:8.3-fpm-alpine

# Arguments pour l'environnement de production (utilisé plus tard)
ARG APP_ENV=production

# Dépendances système et extensions PHP nécessaires
# Ajout de zip, gd, et d'autres outils courants de Laravel
RUN apk update && apk add --no-cache \
    nginx-dev \
    postgresql-dev \
    libpq \
    redis-tools \
    curl \
    supervisor \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql bcmath sockets opcache zip \
    && apk del --no-cache *-dev \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www

# Copier les dépendances de Composer depuis la phase de build
COPY --from=build /app/vendor /var/www/vendor

# Copier le code source de l'application
COPY . /var/www

# --- Configuration des Performances (OPcache) ---
# Meilleure pratique : injecter la configuration directement pour éviter l'erreur "file not found"
RUN { \
    echo '[opcache]'; \
    echo 'opcache.enable=1'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.use_cwd=1'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Nettoyage et permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && find /var/www -type d -exec chmod 755 {} \; \
    && find /var/www -type f -exec chmod 644 {} \;

# Définir l'utilisateur d'exécution sur www-data pour la sécurité
USER www-data

# Exposer le port FPM
EXPOSE 9000

# Commande par défaut : démarrer PHP-FPM
CMD ["php-fpm"]
