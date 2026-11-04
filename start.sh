#!/bin/bash

mkdir -p /var/log/nginx

php-fpm -y /assets/php-fpm.conf -D

chmod -R 777 /app/storage /app/bootstrap/cache

php artisan storage:link --force
php artisan vendor:publish --tag=livewire:assets --force || echo "[warn] livewire asset publish failed, continuing"
php artisan config:cache
php artisan route:cache
php artisan migrate --force || echo "[warn] migrate failed, continuing"

if [ "$DEMO_CONTENT" = "true" ]; then
    php artisan db:seed --class=LiveDemoSeeder --force || echo "[warn] demo content seed failed, continuing"
fi

# Newsletters, confirmations and reminders only go out while these are running.
(while true; do php artisan queue:work --sleep=3 --tries=3 --max-time=3600; sleep 5; done) &
(while true; do php artisan schedule:work; sleep 5; done) &

nginx -c /app/nginx.conf
