#!/bin/bash
set -e

mkdir -p /var/log/nginx

php-fpm -y /assets/php-fpm.conf -D

chmod -R 775 /app/storage /app/bootstrap/cache

php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan migrate --force

nginx -c /app/nginx.conf
