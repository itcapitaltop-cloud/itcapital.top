#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

mkdir -p storage/logs storage/framework/{sessions,views,cache} bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ -f artisan ]; then
    php artisan optimize:clear || true
fi
