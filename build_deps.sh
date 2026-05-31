#!/bin/sh
set -e

# Export all required environment variables for the build process
export APP_ENV=prod
export DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
export APP_SECRET=dummy_secret_for_build
export MAILER_DSN=null://null
export MESSENGER_TRANSPORT_DSN=null://null
export GOOGLE_CLIENT_ID=dummy
export GOOGLE_CLIENT_SECRET=dummy
export JWT_PASSPHRASE=dummy
export XDEBUG_MODE=off

# Create a valid .env file to satisfy Symfony Runtime's boot check
cat <<EOF > .env
APP_ENV=prod
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
APP_SECRET=dummy_secret_for_build
EOF

# Install dependencies without running scripts initially
composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# Build frontend assets: clean lock file to avoid Windows corruption
rm -f package-lock.json
npm install --legacy-peer-deps
npm run build

# Manually run the scripts that would have been triggered by composer
APP_ENV=prod DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db php bin/console cache:clear --env=prod
php bin/console assets:install

# Cleanup
rm .env
