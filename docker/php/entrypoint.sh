#!/bin/sh
set -eu

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -L public/storage ]; then
    rm -rf public/storage
    ln -s ../storage/app/public public/storage
fi

chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM master cần chạy với user mặc định của container.
# Worker của FPM vẫn chạy dưới www-data theo www.conf.
if [ "${1:-}" = "php-fpm" ]; then
    exec "$@"
fi

# Artisan queue/reverb/scheduler và các command khác chạy non-root.
exec su-exec www-data "$@"
