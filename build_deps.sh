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
export TRUSTED_PROXIES=127.0.0.1
export XDEBUG_MODE=off

# Create a valid .env file to satisfy Symfony Runtime's boot check
cat <<EOF > .env
APP_ENV=prod
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
APP_SECRET=dummy_secret_for_build
TRUSTED_PROXIES=127.0.0.1
EOF

# Install dependencies without running scripts initially
composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# Build frontend assets: clean lock file to avoid Windows corruption
rm -f package-lock.json

# Retry mechanism for npm install to handle transient network issues
n=0
until [ "$n" -ge 5 ]
do
   npm install --legacy-peer-deps && break
   n=$((n+1))
   echo "npm install failed, retrying ($n/5) in 5 seconds..."
   sleep 5
done

# Run the build
npm run build

# Manually run the scripts that would have been triggered by composer
# Pass env vars explicitly to ensure they are available to the Symfony console process
APP_ENV=prod DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db php bin/console cache:clear --env=prod
php bin/console assets:install

# Cleanup
rm .env
