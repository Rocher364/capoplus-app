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
    nginx \
    dos2unix

RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Bypass kòmand artisan yo pandan build an pou l pa fè crash
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

COPY nginx.conf /etc/nginx/sites-available/default

RUN dos2unix /var/www/entrypoint.sh && chmod +x /var/www/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/entrypoint.sh"]
