# Phase 1: build (Environnement de compilation pour Composer)
# Utiliser une image composer minimale pour des raisons de vitesse
FROM composer:2.7 AS build

USER root
WORKDIR /app

# ----------------------------------------------------
# 1. Préparation des dépendances de build (Phase 1)
# ----------------------------------------------------
RUN apk update && apk add --no-cache \
    git \
    build-base \
    libzip-dev \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/*

# 2. Compilation des extensions PHP pour Composer (Phase 1)
RUN docker-php-ext-install -j$(nproc) zip pdo pdo_pgsql gd

# 3. Nettoyage après compilation (Phase 1)
RUN apk del --no-cache build-base *-dev && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 4. Installation des dépendances PHP (Phase 1)
# ----------------------------------------------------
COPY composer.json composer.lock ./
# Installation en mode production (sans les dépendances de développement)
RUN composer install --no-dev --optimize-autoloader

# --- Phase 2: Production (Image finale basée sur PHP-FPM Alpine) ---
FROM php:8.3-fpm-alpine

# Arguments pour l'environnement de production
ARG APP_ENV=production

# ----------------------------------------------------
# 1. Installation des dépendances Runtime et Dev (Phase 2)
# ----------------------------------------------------
RUN apk update && apk add --no-cache \
    # Runtime packages
    libpq \
    redis \
    curl \
    supervisor \
    git \
    # Outils de compilation temporaires pour les extensions
    build-base \
    # Dépendances de développement pour les extensions
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 2. Compilation des extensions PHP (Phase 2)
# Ajout de pcntl (requis pour les workers/supervisord) et exif
# ----------------------------------------------------
RUN docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql bcmath sockets opcache zip gd \
    pcntl \
    exif

# ----------------------------------------------------
# 3. Nettoyage après compilation (Phase 2)
# ----------------------------------------------------
RUN apk del --no-cache build-base *-dev \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www

# Copier les dépendances de Composer depuis la phase de build
# Utilisation de --chown pour définir immédiatement le propriétaire
COPY --from=build --chown=www-data:www-data /app/vendor /var/www/vendor

# Copier le code source de l'application
# Utilisation de --chown pour définir immédiatement le propriétaire
COPY --chown=www-data:www-data . /var/www

# --- Configuration des Performances (OPcache) ---
# Active OPcache pour accélérer l'exécution du code PHP
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
# Rendre les dossiers storage/ et cache/ accessibles en écriture
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && find /var/www -type d -exec chmod 755 {} \; \
    && find /var/www -type f -exec chmod 644 {} \;

# Définir l'utilisateur d'exécution sur www-data pour la sécurité
USER www-data

# Exposer le port FPM (Dokploy le lira automatiquement)
EXPOSE 9000

# Commande par défaut : démarrer PHP-FPM
CMD ["php-fpm"]
