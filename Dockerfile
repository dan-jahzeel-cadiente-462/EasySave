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
    zip \
    unzip && \
    docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

# Build assets and warm cache (with dummy env)
RUN touch .env && \
    export APP_ENV=prod && \
    export DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db && \
    export APP_SECRET=dummy && \
    export DEFAULT_URI=https://easysave.up.railway.app && \
    export MAILER_DSN=null://null && \
    export MESSENGER_TRANSPORT_DSN=null://null && \
    export GOOGLE_CLIENT_ID=dummy && \
    export GOOGLE_CLIENT_SECRET=dummy && \
    export CORS_ALLOW_ORIGIN=dummy && \
    export JWT_PASSPHRASE=dummy && \
    export XDEBUG_MODE=off && \
    composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist && \
    php bin/console cache:clear --env=prod && \
    php bin/console assets:install && \
    rm .env

# Stage 2: Runtime
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
    icu-dev \
    icu-libs \
    nginx \
    supervisor \
    bash && \
    docker-php-ext-install -j2 \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache

# Configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/app.conf
COPY --from=builder /app /app
RUN rm -rf /etc/nginx/conf.d/*
COPY nginx.conf /etc/nginx/nginx.conf
COPY nginx-main.conf /etc/nginx/conf.d/nginx-main.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /app
RUN mkdir -p var/cache var/log /var/log/php-fpm public && \
    chown -R www-data:www-data var public /var/log/php-fpm && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
