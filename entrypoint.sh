#!/bin/bash
set -e

echo "🚀 Starting EasySave Application..."

echo "⏳ Checking database connectivity..."
# Use DATABASE_URL to parse host and port, or default to db:3306
# We handle the parse failure gracefully to avoid shell errors
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "db";')
DB_PORT_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PORT) ?: "3306";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_USER) ?: "easysave";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_PASS) ?: "easysave_pass";')

echo "   Target: $DB_HOST_URL:$DB_PORT_VAL (User: $DB_USER_VAL)"

# Use mariadb-admin if available (newer Alpine), fallback to mysqladmin
ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)

# Wait for database, but DO NOT EXIT on failure. 
# We want the container to start so we don't get 502/504 gateway errors.
TIMEOUT=30
while ! "$ADMIN_CMD" ping -h"$DB_HOST_URL" -P"$DB_PORT_VAL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; do
    echo "   Database not yet ready - retrying ($TIMEOUT seconds remaining)..."
    sleep 2
    TIMEOUT=$((TIMEOUT-2))
    if [ $TIMEOUT -le 0 ]; then
        echo "⚠️  Database connection check timed out!"
        echo "   The application will try to start anyway to provide better error pages."
        break
    fi
done

if [ $TIMEOUT -gt 0 ]; then
    echo "✅ Database is ready!"
fi

# Set permissions
echo "🔐 Setting directory permissions..."
chmod -R 755 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true

# Ensure session directory exists with proper permissions
echo "📝 Ensuring session directory..."
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

# Run migrations only when RUN_MIGRATIONS is explicitly set to 1
# (avoid long-running tasks by default in ephemeral platforms like Railway)
# Automatically run in production to ensure database is initialized.
if [ "${RUN_MIGRATIONS:-0}" = "1" ] || [ "$APP_ENV" = "prod" ]; then
    echo "🔄 Running database migrations..."
    php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    
    if [ $? -eq 0 ]; then
        echo "✅ Migrations completed successfully!"
    else
        echo "⚠️  Migrations completed with warnings"
    fi
else
    echo "⏭️  Skipping database migrations (RUN_MIGRATIONS=0)"
fi

# Load fixtures if in development environment
if [ "$APP_ENV" = "dev" ]; then
    echo "📦 Loading fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction || echo "⚠️  Fixtures not available"
fi

# Cache clear and warmup only for non-production environments
if [ "$APP_ENV" != "prod" ]; then
    echo "🧹 Clearing application cache..."
    php bin/console cache:clear --env=${APP_ENV:-dev}

    echo "🔥 Warming up application cache..."
    php bin/console cache:warmup --env=${APP_ENV:-dev}
else
    echo "🚀 Production mode: skipping runtime cache warmup (already built)"
    echo "⚙️  Configuring Nginx for production (single-container)..."
    sed -i 's/server php:9000;/server 127.0.0.1:9000;/g' /etc/nginx/conf.d/nginx-main.conf
    sed -i 's/server websocket:8080;/server 127.0.0.1:8080;/g' /etc/nginx/conf.d/nginx-main.conf
fi

echo "✨ Application initialization completed!"

# Execute the main command passed to the container (supervisord by default)
echo "🚀 Executing command: $@"
exec "$@"
