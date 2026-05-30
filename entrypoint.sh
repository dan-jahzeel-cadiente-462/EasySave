#!/bin/bash
set -e

echo "🚀 Starting EasySave Application..."

echo "⏳ Waiting for database connection to be ready..."
# Use DATABASE_URL to parse host, or default to db service
DB_HOST_URL=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_HOST) ?: "db";')
DB_USER_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_USER) ?: "easysave";')
DB_PASS_VAL=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_PASS) ?: "easysave_pass";')

# Always ping the database before proceeding, regardless of environment
# Use a timeout of 60 seconds to avoid infinite loops on Railway
TIMEOUT=60
while ! mysqladmin ping -h"$DB_HOST_URL" -u"$DB_USER_VAL" -p"$DB_PASS_VAL" --silent; do
    echo "   Database is unavailable - sleeping..."
    sleep 2
    TIMEOUT=$((TIMEOUT-2))
    if [ $TIMEOUT -le 0 ]; then
        echo "❌ Database connection timed out!"
        exit 1
    fi
done
echo "✅ Database is ready!"

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
