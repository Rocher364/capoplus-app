#!/bin/sh
set -e

# Efase ansyen cache pou Laravel ka li DATABASE_URL san konfizyon
php artisan config:clear
php artisan cache:clear

# Ekzekite migrasyon ak seeder yo sou PostgreSQL
php artisan migrate --force
php artisan db:seed --force

# Demare aplikasyon an
if [ -f /usr/sbin/apache22 ] || [ -f /usr/sbin/apache2 ]; then
    exec apache2-foreground
else
    exec php artisan serve --host=0.0.0.0 --port=10000
fi
