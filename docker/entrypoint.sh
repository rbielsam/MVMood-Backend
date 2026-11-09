#!/bin/sh
set -e

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force || true

su-exec www-data php artisan config:cache
su-exec www-data php artisan view:cache

exec "$@"
