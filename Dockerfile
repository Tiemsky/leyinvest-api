FROM php:8.3-fpm-alpine

ARG APP_ENV=production

# ----------------------------------------------------
# 1. Installer dépendances système + outils de compilation
# ----------------------------------------------------
RUN apk update && apk add --no-cache \
    # Runtime
    libpq \
    redis \
    curl \
    supervisor \
    git \
    # Build tools
    build-base \
    linux-headers \
    # Dev deps for PHP extensions
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 2. Installer Composer
# ----------------------------------------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ----------------------------------------------------
# 3. Compiler les extensions PHP
# ----------------------------------------------------
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    bcmath \
    sockets \
    opcache \
    zip \
    gd \
    pcntl \
    exif

# ----------------------------------------------------
# 4. Nettoyage
# ----------------------------------------------------
RUN apk del --no-cache build-base *-dev \
    && rm -rf /var/cache/apk/*

# ----------------------------------------------------
# 5. Configurer l'application
# ------------------------------------------------
WORKDIR /var/www

# Copier composer.json en premier (pour le cache Docker)
COPY composer.json composer.lock ./

# Installer les dépendances (maintenant que les extensions sont actives)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copier le reste du code
COPY . .

# OPcache
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

# Permissions
RUN chmod -R 775 storage bootstrap/cache \
    && find . -type d -exec chmod 755 {} \; \
    && find . -type f -exec chmod 644 {} \; \
    && chown -R www-data:www-data .

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
