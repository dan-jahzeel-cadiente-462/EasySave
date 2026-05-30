#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Railway Optimized v4)..."

# 1. Fix the .env PathException
# Create dummy if missing, so Symfony Runtime doesn't crash
if [ ! -f .env ]; then
    echo "📝 Creating dummy .env file..."
    touch .env
fi

# 2. Configure Nginx Port
# Use $PORT provided by Railway, or default to 8080.
RAILWAY_PORT="${PORT:-8080}"
NGINX_CONF="/etc/nginx/conf.d/nginx-main.conf"

echo "🌐 Setting Nginx to listen on port: $RAILWAY_PORT"
# Ensure the config is clean and only contains our current port.
sed -i "s/LISTEN_PORT/$RAILWAY_PORT/g" $NGINX_CONF

# 3. Alpine Nginx Setup
# Ensure Nginx has its required run directories
mkdir -p /run/nginx /var/lib/nginx/tmp/client_body
chown -R nginx:nginx /var/lib/nginx

# 4. Database Connectivity & Migrations
echo "⏳ Checking database connectivity..."
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "root";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "";')

echo "   DB Host: $DB_HOST_URL:$DB_PORT_VAL"

ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
# Fast non-fatal check to prevent boot loop
if ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; then
    echo "⚠️  Database not ready yet. Continuing to boot web server..."
else
    echo "✅ Database is ready!"
    # Only run migrations if DB is reachable
    if [ "$APP_ENV" = "prod" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
        echo "🔄 Running migrations..."
        php bin/console doctrine:database:create --if-not-exists --no-interaction || true
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration failed."
    fi
fi

# 5. Permissions
echo "🔐 Setting permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

echo "✨ Initialization complete. Starting Nginx & PHP-FPM..."

# Execute supervisord
exec "$@"
