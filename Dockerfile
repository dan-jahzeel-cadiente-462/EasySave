# Stage 1: Builder
FROM php:8.3-fpm-alpine AS builder

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zlib-dev \
    icu-dev \
    libxml2-dev \
    libsodium-dev \
    nodejs \
    npm \
    zip \
    unzip && \
    docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache \
    xml \
    sodium

# Install Composer via official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY . .
COPY build_deps.sh /app/build_deps.sh

# Use a build script to handle dependencies and cache clearing reliably
RUN sed -i 's/\r$//' /app/build_deps.sh && \
    chmod +x /app/build_deps.sh && \
    /app/build_deps.sh && \
    rm /app/build_deps.sh

# Create cache directories
RUN mkdir -p var/cache var/log
FROM php:8.3-fpm-alpine AS runtime

RUN apk update && apk add --no-cache \
    curl \
    mysql-client \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    libzip \
    libzip-dev \
    zlib-dev \
    libxml2-dev \
    libsodium-dev \
    icu-dev \
    icu-libs \
    nginx \
    supervisor \
    bash \
    gettext && \
    docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache \
    xml \
    sodium

# Configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/app.conf
COPY --from=builder /app /app
RUN rm -rf /etc/nginx/conf.d/*
COPY nginx.conf /etc/nginx/nginx.conf
COPY nginx-main.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN echo "Force rebuild for configuration stability"

WORKDIR /app
RUN mkdir -p var/cache var/log /var/log/php-fpm public && \
    chown -R www-data:www-data var public /var/log/php-fpm && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
