FROM php:8.2-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libzip-dev \
    nginx

RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

COPY nginx.conf /etc/nginx/sites-available/default

RUN mkdir -p /var/www/storage/framework/sessions \
             /var/www/storage/framework/views \
             /var/www/storage/framework/cache \
             /var/www/bootstrap/cache \
             /var/www/database

RUN touch /var/www/database/database.sqlite

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/database

EXPOSE 80

# Kouri migrations AK seeders an premye, apre sa kouri nginx
CMD php-fpm -D && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    nginx -g 'daemon off;'
