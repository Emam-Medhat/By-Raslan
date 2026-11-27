# BY RASLAN ERP — Production Docker image (Laravel 12 + PHP 8.3 + Apache)
# Multi-stage: Node builds Vite assets, PHP serves the app.
# Works on Railway / Render / any Docker host. Cloudflare sits in front
# (DNS / Tunnel) — Cloudflare Workers/Pages alone CANNOT run PHP.

# ---- Stage 1: build frontend assets ----
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- Stage 2: PHP runtime ----
FROM php:8.3-apache

# System deps + PHP extensions Laravel needs
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
        libicu-dev libpq-dev default-mysql-client \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql pdo_sqlite pdo_pgsql zip gd bcmath intl pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# App code (respect .dockerignore: no vendor, node_modules, .env, .git)
COPY . .
COPY --from=frontend /app/public/build ./public/build

# PHP dependencies (no dev packages in production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
