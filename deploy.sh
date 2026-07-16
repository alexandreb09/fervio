#!/bin/bash
set -e

echo "==> Pulling latest code..."
cd /homez.2212/teensgg/alex/fervio
git fetch origin
git reset --hard origin/main

echo "==> Clearing stale cache..."
cd /homez.2212/teensgg/alex/fervio/backend
rm -rf var/cache/prod

echo "==> Installing Composer dependencies..."
php /homez.2212/teensgg/composer.phar install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Warming cache..."
php bin/console cache:warmup --env=prod --no-debug

echo "==> Deploy complete."
