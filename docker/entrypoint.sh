#!/bin/bash
set -e

# Railway / Render set $PORT — make Apache listen on it.
if [ -n "$PORT" ]; then
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# Create .env from the platform's env vars on first boot (Railway injects them).
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || true
fi

# App key (only if the platform did not provide one)
if ! grep -q "^APP_KEY=.\+" .env 2>/dev/null; then
  php artisan key:generate --force || true
fi

# Production defaults (do not override explicit env vars)
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}

# Database migrations (safe to run on every deploy)
php artisan migrate --force || echo "WARN: migrations failed - check DB_* env vars"

# Storage link + caches
php artisan storage:link || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
