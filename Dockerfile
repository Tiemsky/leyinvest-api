FROM php:8.3-cli-alpine

# Installation des certificats SSL + dépendances
RUN apk add --no-cache \
    ca-certificates \
    libpng libzip libpq openssl \
    build-base linux-headers \
    postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev

# Mise à jour des certificats
RUN update-ca-certificates

# Extensions PHP
RUN docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd zip bcmath sockets pcntl exif

# Nettoyage
RUN apk del --no-cache build-base linux-headers postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
