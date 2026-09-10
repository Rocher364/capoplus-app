#!/bin/sh

# Kreye dosye yo si yo pa la
mkdir -p /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/framework/cache \
         /var/www/bootstrap/cache \
         /var/www/database

# Kreye fichiye SQLite la si l pa egziste
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
fi

# Bay pèmisyon nesesè yo
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Kouri migrations san yo pa fè container a chash si gen ti enpak
php artisan migrate --force || true

# Kòmanse PHP-FPM nan background
php-fpm -D

# Kòmanse Nginx nan foreground
nginx -g 'daemon off;'
