FROM php:8.3-fpm-alpine

# 1. Installer dépendances runtime + build
RUN apk add --no-cache \
    # Runtime (NE PAS SUPPRIMER)
    libpng16 \
    libzip \
    libpq \
    # Build tools
    build-base \
    linux-headers \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev

# 2. Compiler extensions PHP
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    gd \
    zip \
    bcmath \
    sockets \
    pcntl \
    exif

# 3. Nettoyer BUILD TOOLS (mais garder runtime !)
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

# 5. Préparer le répertoire
WORKDIR /var/www

# 6. Copier dépendances
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Copier le code
COPY . .

# 8. Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

# 9. Utilisateur non-root
USER www-data

# 10. Démarrer avec php artisan serve (HTTP compatible)
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
