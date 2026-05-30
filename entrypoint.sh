#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Railway Optimized v2)..."

# 1. Fix the .env PathException
if [ ! -f .env ]; then
    echo "📝 Creating dummy .env file to satisfy Symfony Runtime..."
    touch .env
fi

# 2. Configure Nginx Port and Upstreams
RAILWAY_PORT="${PORT:-8080}"
NGINX_CONF="/etc/nginx/conf.d/nginx-main.conf"

echo "🌐 Configuring Nginx to listen on port: $RAILWAY_PORT"
sed -i "s/LISTEN_PORT/$RAILWAY_PORT/g" $NGINX_CONF

# Explicitly map Nginx upstreams to localhost (PHP-FPM is on 9001)
echo "⚙️  Mapping Nginx upstreams to 127.0.0.1:9001..."
# We use a broader regex to ensure it matches any previous state
sed -i 's/server .*:9000;/server 127.0.0.1:9001;/g' $NGINX_CONF
sed -i 's/server .*:8080;/server 127.0.0.1:8080;/g' $NGINX_CONF

# 3. Database Connectivity Check (Non-Fatal)
echo "⏳ Checking database connectivity..."
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "root";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "";')

echo "   Target: $DB_HOST_URL:$DB_PORT_VAL (User: $DB_USER_VAL)"

ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
TIMEOUT=10
while ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; do
    echo "   Database not ready... ($TIMEOUTs remaining)"
    sleep 2
    TIMEOUT=$((TIMEOUT-2))
    if [ $TIMEOUT -le 0 ]; then
        echo "⚠️  DB Timeout. Web server will start anyway to show errors."
        break
    fi
done

# 4. Permissions
echo "🔐 Setting directory permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

# 5. Database Migrations (Non-Fatal)
if [ "$APP_ENV" = "prod" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "🔄 Attempting database preparation..."
    # We don't use 'set -e' for these so the container doesn't exit on failure
    php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "ℹ️  Database exists or skip create."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration failed. Check DB variables."
fi

echo "✨ Initialization complete. Launching services..."

# Execute the main command (supervisord)
exec "$@"
