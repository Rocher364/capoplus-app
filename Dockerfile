FROM php:8.2-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1

# Enstale depandans ak Nginx
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

# Kopye Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Kopye tout pwojè a
COPY . .

# Enstale paki Laravel yo
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Konfigirasyon Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Kreye dosye nesesè yo
RUN mkdir -p /var/www/storage/framework/sessions \
             /var/www/storage/framework/views \
             /var/www/storage/framework/cache \
             /var/www/bootstrap/cache \
             /var/www/database

RUN touch /var/www/database/database.sqlite

# Pèmisyon total pou Laravel ka ekri nan storage ak database
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/database

EXPOSE 80

# Script demaraj ki kouri san l pa janm bloke
CMD php-fpm -D && \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan migrate --force && \
    nginx -g 'daemon off;'
