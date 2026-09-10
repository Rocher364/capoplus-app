#!/bin/sh

# Kreye fichiye SQLite la si l pa egziste
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
    chown www-data:www-data /var/www/database/database.sqlite
fi

# Asire otorizasyon fichiye ak dosye yo korèk
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Ekzekite migrations ak seeders pou kreye kont yo
php artisan migrate --force
php artisan db:seed --force

# Clear cache pou evite tout vye konfigirasyon
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Demare PHP-FPM ak Nginx
php-fpm -D
nginx -g 'daemon off;'
