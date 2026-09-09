#!/bin/sh
set -e

# Kreye dosye yo si yo pa egziste
mkdir -p /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache /var/www/database

# Kreye fichiye SQLite la si l poko la
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
fi

# Ban nouvo dosye yo tout pèmisyon nesesè
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Kouri migrations ak seeders
php artisan migrate --force
php artisan db:seed --force

# Kòmanse PHP-FPM nan background ak Nginx nan foreground
php-fpm -D
nginx -g 'daemon off;'
