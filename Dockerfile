# Phase 1: BUILD (Environnement de compilation pour Composer)
# Utiliser AS BUILD en majuscule pour le style et corriger l'avertissement FromAsCasing
FROM composer:2.7 AS BUILD

USER root
WORKDIR /app

# ----------------------------------------------------
# 1. Pré-requis pour Composer
# Installer les dépendances système et extensions PHP nécessaires
# pour que Composer puisse résoudre et installer toutes les dépendances Laravel.
# ----------------------------------------------------
RUN apk update && apk add --no-cache \
    git \
    # Dépendances pour la compilation d'extensions
    libzip-dev \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # Installer les extensions critiques requises par Composer/Laravel
    && docker-php-ext-install -j$(nproc) zip pdo pdo_pgsql gd \
    && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 2. Installation des dépendances PHP
# ----------------------------------------------------
COPY composer.json composer.lock ./
# Exécution de composer install SANS les dépendances de développement pour l'image finale
RUN composer install --no-dev --optimize-autoloader

# --- Phase 2: Production (Image finale basée sur PHP-FPM Alpine) ---
FROM php:8.3-fpm-alpine

# Arguments pour l'environnement de production
ARG APP_ENV=production

# Dépendances système et extensions PHP finales (pour l'exécution)
RUN apk update && apk add --no-cache \
    libpq \
    redis \
    curl \
    supervisor \
    git \
    # Paquets de développement nécessaires pour docker-php-ext-install
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # Installation des extensions PHP, y compris opcache pour la performance
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql bcmath sockets opcache zip gd \
    # Nettoyage des paquets de développement pour réduire la taille de l'image
    && apk del --no-cache *-dev \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www

# Copier les dépendances de Composer depuis la phase de BUILD
COPY --from=BUILD /app/vendor /var/www/vendor

# Copier le code source de l'application
# Le .dockerignore doit exclure les fichiers inutiles (comme node_modules, .git)
COPY . /var/www

# --- Configuration des Performances (OPcache) ---
# Injection de la configuration OPcache directement pour éviter les erreurs de fichier manquant
# opcache.revalidate_freq=0 est essentiel pour la performance en production
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
# Assurer les droits d'écriture pour l'utilisateur www-data sur les répertoires de cache
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
