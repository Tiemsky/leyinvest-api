# =================================================================
# Phase 1: Build (Installation des dépendances Composer)
# =================================================================
FROM composer:2.7 AS build

USER root
WORKDIR /app

# ----------------------------------------------------
# 1. Dépendances de build (Phase 1)
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

# 2. Compiler les extensions nécessaires pour Composer (si besoin)
RUN docker-php-ext-install -j$(nproc) zip pdo pdo_pgsql gd

# 3. Nettoyage après compilation (Phase 1)
RUN apk del --no-cache build-base *-dev && rm -rf /var/cache/apk/*

# 4. Installer les dépendances PHP (sans dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# =================================================================
# Phase 2: Production (Image finale)
# =================================================================
FROM php:8.3-fpm-alpine

ARG APP_ENV=production

# ----------------------------------------------------
# 1. Installer les dépendances runtime + outils de compilation
# ----------------------------------------------------
RUN apk update && apk add --no-cache \
    # Runtime
    libpq \
    redis \
    curl \
    supervisor \
    git \
    # Outils de compilation (NECESSAIRES pour sockets, pcntl, etc.)
    build-base \
    linux-headers \          # ← ESSENTIEL pour sockets
    # Dépendances de développement pour extensions
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 2. Compiler les extensions PHP
# ----------------------------------------------------
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    bcmath \
    sockets \                # ← Maintenant compilable
    opcache \
    zip \
    gd \
    pcntl \                  # ← Pour queue:work, Octane
    exif

# ----------------------------------------------------
# 3. Nettoyage (SUPPRIMER les outils de build APRÈS compilation)
# ----------------------------------------------------
RUN apk del --no-cache build-base *-dev \
    && rm -rf /var/cache/apk/*

# =================================================================
# Configuration de l'application
# =================================================================
WORKDIR /var/www

# Copier les dépendances depuis la phase build
COPY --from=build --chown=www-data:www-data /app/vendor /var/www/vendor

# Copier le code source
COPY --chown=www-data:www-data . /var/www

# Configurer OPcache pour la production
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

# Permissions (storage/bootstrap/cache doivent être en écriture)
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && find /var/www -type d -exec chmod 755 {} \; \
    && find /var/www -type f -exec chmod 644 {} \;

# Sécurité : exécuter en tant que www-data
USER www-data

# Exposer le port FPM (Dokploy le détectera automatiquement)
EXPOSE 9000

# Démarrer PHP-FPM
CMD ["php-fpm"]
