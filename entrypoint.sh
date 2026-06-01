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
    
    echo "🌱 Running production seed..."
    php bin/console app:seed-production --no-interaction || echo "⚠️  Seed deferred."
fi

# 4. Critical Permissions for Symfony
echo "🔐 Setting permissions..."
mkdir -p var/cache var/log /var/lib/php/sessions
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log || true
chmod 1777 /var/lib/php/sessions

# 5. Inject Port into Nginx
echo "🌍 Configuring Nginx for port ${PORT:-8080}..."
# Ensure PORT is set for envsubst
export PORT=${PORT:-8080}
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

echo "✨ Services launching: Nginx (${PORT}) -> PHP-FPM (127.0.0.1:9000)"
# 6. Hand over to supervisord
exec "$@"
