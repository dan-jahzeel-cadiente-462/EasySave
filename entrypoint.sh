#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Railway Optimized v3)..."

# 1. Fix the .env PathException
# Required for Symfony's runtime boot process
if [ ! -f .env ]; then
    echo "📝 Creating dummy .env file to satisfy Symfony Runtime..."
    touch .env
fi

# 2. Configure Nginx Port
# Use $PORT provided by Railway, or default to 8080
RAILWAY_PORT="${PORT:-8080}"
NGINX_CONF="/etc/nginx/conf.d/nginx-main.conf"

echo "🌐 Configuring Nginx to listen on port: $RAILWAY_PORT"
# Remove any accidental duplicates and set the correct port
sed -i "s/LISTEN_PORT/$RAILWAY_PORT/g" $NGINX_CONF

# 3. Database Connectivity Check (Informational)
echo "⏳ Checking database connectivity..."
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "root";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "";')

echo "   Target: $DB_HOST_URL:$DB_PORT_VAL (User: $DB_USER_VAL)"

ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
# Fast non-fatal check
if ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; then
    echo "⚠️  Database not immediately available. The web server will start anyway to show Symfony errors."
else
    echo "✅ Database is ready!"
fi

# 4. Permissions
echo "🔐 Setting directory permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

# 5. Database Migrations (Automatic for Production)
if [ "$APP_ENV" = "prod" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "🔄 Attempting database migrations..."
    # Ensure database exists and run migrations
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  Migration failed. Application will display the error."
fi

echo "✨ Initialization complete. Launching services..."

# Execute the main command (supervisord)
exec "$@"
