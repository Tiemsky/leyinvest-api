# Utiliser PHP CLI (plus simple que FPM pour Dokploy)
FROM php:8.3-cli-alpine

# 1. Installer dépendances système (runtime + build)
RUN apk add --no-cache \
    # Runtime (nécessaire au lancement des extensions)
    libpng \
    libzip \
    libpq \
    # Build tools (pour compiler les extensions)
    build-base \
    linux-headers \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev

# 2. Compiler les extensions PHP requises
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    gd \
    zip \
    bcmath \
    sockets \
    pcntl \
    exif

# 3. Nettoyer les outils de compilation (garder le runtime)
RUN apk del --no-cache \
    build-base \
    linux-headers \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev

# 4. Installer Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 5. Préparer le répertoire de travail
WORKDIR /var/www

# 6. Installer les dépendances PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Copier le code source
COPY . .

# 8. Fixer les permissions (non-root)
RUN chown -R www-www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

# 9. Passer à l'utilisateur non-root
USER www-data

# 10. Exposer le port et démarrer avec le serveur PHP intégré
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
