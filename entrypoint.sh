#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Railway High-Stability Edition)..."

# 1. Fix the .env PathException
if [ ! -f .env ]; then
    echo "📝 Creating dummy .env file to satisfy Symfony Runtime..."
    touch .env
fi

# 2. Configure Nginx Port
# Use $PORT provided by Railway, or default to 8080.
# We ensure the config is clean and only contains our current port.
RAILWAY_PORT="${PORT:-8080}"
NGINX_CONF="/etc/nginx/conf.d/nginx-main.conf"

echo "🌐 Setting Nginx to listen on Railway-assigned port: $RAILWAY_PORT"
# Replace the placeholder with the actual port
sed -i "s/LISTEN_PORT/$RAILWAY_PORT/g" $NGINX_CONF

# 3. Database Connectivity Check (Informational)
echo "⏳ Checking database connectivity..."
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "root";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "";')

echo "   Target DB: $DB_HOST_URL:$DB_PORT_VAL"

ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
if ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; then
    echo "⚠️  Database not ready yet. Web server will start anyway."
else
    echo "✅ Database is ready!"
fi

# 4. Permissions
echo "🔐 Setting directory permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

# 5. Database Migrations
if [ "$APP_ENV" = "prod" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "🔄 Attempting database migrations..."
    # Always attempt to create DB and migrate
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration failed - application will report the error."
fi

echo "✨ Initialization complete. Starting Nginx & PHP-FPM..."

# Execute supervisord
exec "$@"
