#!/bin/bash
set -e

echo "==> Pulling latest code..."
cd /homez.2212/teensgg/alex/fervio
git fetch origin
git reset --hard origin/main

echo "==> Installing Composer dependencies..."
cd /homez.2212/teensgg/alex/fervio/backend
php /homez.2212/teensgg/composer.phar install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Clearing and warming cache..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

echo "==> Deploy complete."
