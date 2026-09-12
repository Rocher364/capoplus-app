#!/bin/sh
set -e

# Ekzekite migrasyon yo ak seeder sou PostgreSQL
php artisan migrate --force
php artisan db:seed --force

# Laliman cache ak optimisation
php artisan config:cache
php artisan route:cache

# Demare s?v? a
exec apache2-foreground || php artisan serve --host=0.0.0.0 --port=10000
