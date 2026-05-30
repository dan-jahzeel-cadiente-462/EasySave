# Multi-stage build for production-ready Symfony application
# Stage 1: Builder - Prepare application dependencies and assets
FROM php:8.3-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    opcache


# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Increase composer timeout
ENV COMPOSER_PROCESS_TIMEOUT=2000

WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies (production)
RUN XDEBUG_MODE=off composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Create cache directories
RUN mkdir -p var/cache var/log

# Stage 2: Runtime - Minimal production image
FROM php:8.3-fpm-alpine AS runtime

# Install system dependencies for runtime
RUN apk add --no-cache \
    curl \
    mysql-client \
    libpng \
    libjpeg-turbo \
    libfreetype \
    libzip \
    nginx \
    supervisor \
    bash

# Install PHP extensions
RUN docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    opcache


# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/app.conf

# Copy from builder
COPY --from=builder /app /app

# Copy Nginx and Supervisor configs
COPY nginx.conf /etc/nginx/nginx.conf
COPY nginx-main.conf /etc/nginx/conf.d/nginx-main.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /app

# Create necessary directories
RUN mkdir -p var/cache var/log /var/log/php-fpm public && \
    chown -R www-data:www-data var public /var/log/php-fpm

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port
EXPOSE 80

# Run entrypoint
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
