FROM php:8.2-fpm

# Permèt Composer kouri kòm root
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

# Enstale depandans yo san inyore pèmisyon yo
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache /var/www/database
RUN touch /var/www/database/database.sqlite
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

COPY nginx.conf /etc/nginx/sites-available/default

EXPOSE 80

CMD php artisan migrate --force && php artisan db:seed --force && php-fpm -D && nginx -g 'daemon off;'
