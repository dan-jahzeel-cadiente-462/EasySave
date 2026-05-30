#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Railway Optimized)..."

# 1. Fix the .env PathException
# Symfony's Dotenv component requires a physical file to exist in prod if not configured otherwise.
if [ ! -f .env ]; then
    echo "📝 Creating dummy .env file to satisfy Symfony Runtime..."
    touch .env
fi

# 2. Configure Nginx Port for Railway
# Railway provides a dynamic $PORT environment variable.
# If not provided, we default to 80.
RAILWAY_PORT="${PORT:-80}"
echo "🌐 Configuring Nginx to listen on port: $RAILWAY_PORT"
sed -i "s/LISTEN_PORT/$RAILWAY_PORT/g" /etc/nginx/conf.d/nginx-main.conf

# 3. Database Connectivity Check
echo "⏳ Checking database connectivity..."
# Use DATABASE_URL to parse host and port, or default to db:3306
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "root";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "";')

echo "   Target: $DB_HOST_URL:$DB_PORT_VAL (User: $DB_USER_VAL)"

# Use mariadb-admin if available (newer Alpine), fallback to mysqladmin
ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)

# Non-fatal check so the container starts and provides a better error page
TIMEOUT=15
while ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; do
    echo "   Database not yet ready - retrying ($TIMEOUT seconds remaining)..."
    sleep 3
    TIMEOUT=$((TIMEOUT-3))
    if [ $TIMEOUT -le 0 ]; then
        echo "⚠️  Database connection timed out! Will try to start anyway."
        break
    fi
done

if [ $TIMEOUT -gt 0 ]; then
    echo "✅ Database is ready!"
fi

# 4. Permissions and Cache
echo "🔐 Setting directory permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

# 5. Database Migrations
# Force run in production on Railway
if [ "$APP_ENV" = "prod" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "🔄 Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration failed - check DB credentials"
fi

# 6. Final Production Tuning
if [ "$APP_ENV" = "prod" ]; then
    echo "⚙️  Optimizing Nginx for production (single-container)..."
    sed -i 's/server php:9000;/server 127.0.0.1:9000;/g' /etc/nginx/conf.d/nginx-main.conf
    sed -i 's/server websocket:8080;/server 127.0.0.1:8080;/g' /etc/nginx/conf.d/nginx-main.conf
fi

echo "✨ Initialization complete. Launching services..."

# Execute the main command (supervisord)
exec "$@"
