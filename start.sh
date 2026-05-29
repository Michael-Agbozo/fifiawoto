#!/bin/bash
mkdir -p /var/log/nginx
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
chmod -R 775 /app/storage /app/bootstrap/cache
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
nginx -c /nginx.conf
