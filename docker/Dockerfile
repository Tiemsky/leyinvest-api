# Multi-stage build for optimized production image
FROM php:8.3-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    nginx \
    supervisor \
    bash

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies stage
FROM base AS dependencies

ARG APP_ENV=production

RUN if [ "$APP_ENV" = "production" ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; \
    else \
    composer install --optimize-autoloader --no-interaction --prefer-dist; \
    fi

# Production stage
FROM base AS production

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

# Copy installed dependencies
COPY --from=dependencies /var/www/vendor /var/www/vendor

# Copy application files
COPY . /var/www

# Create necessary directories and set permissions
RUN mkdir -p \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    /run/nginx

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy nginx configuration (if files exist)
COPY --chown=www-data:www-data docker/nginx/*.conf /etc/nginx/ 2>/dev/null || true

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Optimize for production
RUN if [ "$APP_ENV" = "production" ]; then \
    php artisan config:cache || true; \
    php artisan route:cache || true; \
    php artisan view:cache || true; \
    fi

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php artisan --version || exit 1

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
