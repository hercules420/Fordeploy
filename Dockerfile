# syntax=docker/dockerfile:1

FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-cli-alpine
WORKDIR /app

RUN apk add --no-cache \
    bash \
    libpq \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
RUN php artisan package:discover --ansi

EXPOSE 10000

CMD ["sh", "-c", "php artisan migrate --force || true; php artisan config:cache || true; php artisan route:cache || true; php artisan view:cache || true; php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
