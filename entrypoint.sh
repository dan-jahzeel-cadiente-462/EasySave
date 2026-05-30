#!/bin/bash
set -e

echo "🚀 Launching EasySave (Railway Optimized v6)..."

# 1. Ensure Symfony runtime file exists
if [ ! -f .env ]; then
    echo "📝 Initializing environment file..."
    touch .env
fi

# 2. Fix Alpine Nginx Runtime Requirements
# Nginx in Alpine often fails to start if these don't exist
mkdir -p /run/nginx /var/lib/nginx/tmp/client_body /var/log/php-fpm
chown -R nginx:nginx /var/lib/nginx /var/log/nginx
chmod -R 775 /var/lib/nginx
chown -R www-data:www-data /var/log/php-fpm

# 3. Fast Database Readiness Check & Migrations
# We run migrations in the background or with a fast timeout to avoid 504 on boot
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Database setup..."
    ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
    
    # Extract host and port for a quick ping
    DB_HOST=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_HOST) ?: "";')
    DB_PORT=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_PORT) ?: "3306";')
    DB_USER=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_USER) ?: "";')
    DB_PASS=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_PASS) ?: "";')

    # Quick 5s check
    if "$ADMIN_CMD" ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent --connect-timeout=5; then
        echo "✅ DB ready. Applying migrations..."
        # If migration fails, we don't stop the container
        php bin/console doctrine:database:create --if-not-exists --no-interaction || true
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️ Migration failed."
    else
        echo "⚠️ DB not reachable. Web server will start anyway."
    fi
fi

# 4. Critical Permissions for Symfony
echo "🔐 Setting application permissions..."
mkdir -p var/cache var/log /var/lib/php/sessions
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log || true
chmod 1777 /var/lib/php/sessions

echo "✨ Services starting: Nginx (8080) -> PHP-FPM (127.0.0.1:9001)"
# 5. Hand over to supervisord
exec "$@"
