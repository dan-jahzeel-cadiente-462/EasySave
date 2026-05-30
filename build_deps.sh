#!/bin/sh
set -e
export APP_ENV=prod
export DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
export APP_SECRET=dummy
export DEFAULT_URI=https://easysave.up.railway.app
export MAILER_DSN=null://null
export MESSENGER_TRANSPORT_DSN=null://null
export GOOGLE_CLIENT_ID=dummy
export GOOGLE_CLIENT_SECRET=dummy
export CORS_ALLOW_ORIGIN=dummy
export JWT_PASSPHRASE=dummy
export XDEBUG_MODE=off

composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist
php bin/console cache:clear --env=prod
php bin/console assets:install
