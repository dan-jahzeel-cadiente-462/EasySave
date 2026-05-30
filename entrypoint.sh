#!/bin/bash
set -e

echo "🚀 Starting EasySave Application (Final Production Build)..."

# 1. Ensure .env exists for Symfony Runtime
if [ ! -f .env ]; then
    touch .env
fi

# 2. Alpine Nginx directories
mkdir -p /run/nginx /var/lib/nginx/tmp/client_body
chown -R nginx:nginx /var/lib/nginx

# 3. DB Connectivity & Migrations
# We use DATABASE_URL if provided, else skip migrations to avoid boot hang.
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Checking database connection..."
    DB_HOST=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_HOST) ?: "";')
    DB_PORT=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_PORT) ?: "3306";')
    DB_USER=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_USER) ?: "";')
    DB_PASS=$(php -r 'echo parse_url(getenv("DATABASE_URL"), PHP_URL_PASS) ?: "";')
    
    ADMIN_CMD=$(command -v mariadb-admin || command -v mysqladmin)
    
    if "$ADMIN_CMD" ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent --connect-timeout=5; then
        echo "✅ Database reachable. Running migrations..."
        php bin/console doctrine:database:create --if-not-exists --no-interaction || true
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️ Migration skip/fail."
    else
        echo "⚠️ Database not reachable yet. Starting anyway."
    fi
fi

# 4. Final Prep
echo "🔐 Setting permissions..."
chmod -R 775 var/cache var/log || true
chown -R www-data:www-data var/cache var/log public || true
mkdir -p /var/lib/php/sessions
chmod 1777 /var/lib/php/sessions

echo "✨ All systems ready. Nginx (8080) -> PHP-FPM (9001)"
exec "$@"
