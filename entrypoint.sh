#!/bin/sh
set -e

php artisan config:clear
php artisan cache:clear

php artisan migrate --force
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port=10000
