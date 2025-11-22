# Phase 1: Build (pour installer les dépendances sans encombrer l'image finale)
FROM composer:2.7 as build

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Phase 2: Production (Image finale)
FROM php:8.3-fpm-alpine

# Arguments pour l'environnement de production
ARG APP_ENV=production

# Dépendances système : nginx-dev pour les libs, curl, et les extensions PHP
RUN apk update && apk add --no-cache \
    nginx-dev \
    postgresql-dev \
    libpq \
    redis \
    curl \
    && docker-php-ext-install pdo pdo_pgsql bcmath sockets opcache \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www

# Copier les fichiers du build et le code source
COPY --from=build /app/vendor /var/www/vendor
COPY . /var/www

# Ajuster les permissions pour PHP-FPM
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Configurer opcache (pour la performance)
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Exposer le port FPM
EXPOSE 9000

# Commande par défaut : démarrer PHP-FPM
CMD ["php-fpm"]
