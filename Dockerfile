# Imagen para Render (https://render.com). Se construye en 3 pasos:
#  1. vendor: dependencias de PHP (composer)
#  2. assets: CSS/JS/fuentes con Vite (necesita las vistas de paginación de vendor para Tailwind)
#  3. app:    PHP 8.5 + Apache con las extensiones que usa el proyecto (Postgres, GD, zip, intl)

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM php:8.5-apache AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev libzip-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd zip intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Apache: sirve /public, permite el .htaccess de Laravel y escucha en el puerto que da Render ($PORT)
ENV PORT=10000 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/app/private storage/app/public storage/fonts storage/logs \
        storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
