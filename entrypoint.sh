#!/bin/bash
set -e

echo "🚀 Starting EasySave (Final Production Bridge v7)..."

# 1. Environment requirements
if [ ! -f .env ]; then
    touch .env
fi

# 2. Filesystem Requirements (Alpine Fixes)
# Ensure all directories needed by Nginx/PHP exist
mkdir -p /run/nginx /var/lib/nginx/tmp/client_body /var/log/php-fpm
chown -R nginx:nginx /var/lib/nginx /var/log/nginx /run/nginx
chown -R www-data:www-data /var/log/php-fpm

# 3. Fast Database Initialization
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Initializing Database..."
    # Quick non-blocking attempt to prepare database
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration deferred."
fi

# 4. Critical Permissions for Symfony
echo "🔐 Setting permissions..."
mkdir -p var/cache var/log /var/lib/php/sessions
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log || true
chmod 1777 /var/lib/php/sessions

echo "✨ Services launching: Nginx (8080) -> PHP-FPM (127.0.0.1:9000)"
# 5. Hand over to supervisord
exec "$@"
