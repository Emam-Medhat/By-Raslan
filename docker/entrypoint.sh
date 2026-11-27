#!/usr/bin/env bash
set -e

cd /var/www/html

PORT="${PORT:-8080}"
sed -i "s/listen 8080;/listen ${PORT};/" /etc/nginx/sites-available/default

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Ensure required runtime env defaults for free/container deploys.
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_URL="${APP_URL:-http://localhost}"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export CACHE_STORE="${CACHE_STORE:-database}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

if [ "$DB_CONNECTION" = "sqlite" ]; then
  mkdir -p "$(dirname "$DB_DATABASE")"
  touch "$DB_DATABASE"
fi

php artisan key:generate --force --no-interaction || true
php artisan storage:link --no-interaction || true
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction || true
php artisan route:cache --no-interaction || true
php artisan view:cache --no-interaction || true

php-fpm -D
exec nginx -g 'daemon off;'
