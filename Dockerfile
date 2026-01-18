# =============================================================================
# E-commerce Admin - Production Dockerfile
# Multi-stage build for optimized image size
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Composer Dependencies
# -----------------------------------------------------------------------------
FROM composer:2.7 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# -----------------------------------------------------------------------------
# Stage 2: Node.js Assets Build
# -----------------------------------------------------------------------------
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 3: Production Image
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    sqlite-dev \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite \
        pgsql \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
        bcmath \
        pcntl

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=64" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=30000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP configuration
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/docker.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/docker.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/docker.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/docker.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/docker.ini

# Create app user
RUN addgroup -g 1000 -S app \
    && adduser -u 1000 -S app -G app

WORKDIR /var/www/html

# Copy application files
COPY --chown=app:app . .
COPY --from=composer-builder --chown=app:app /app/vendor ./vendor
COPY --from=node-builder --chown=app:app /app/public/build ./public/build

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Set permissions
RUN chown -R app:app storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create required directories
RUN mkdir -p storage/logs storage/framework/{sessions,views,cache} \
    && chown -R app:app storage \
    && chmod -R 775 storage

# Remove development files
RUN rm -rf \
    .git \
    .github \
    tests \
    docker \
    .env.example \
    phpunit.xml \
    phpstan.neon \
    README.md \
    *.md

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# Entry point
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
