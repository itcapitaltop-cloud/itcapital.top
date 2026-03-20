#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

mkdir -p /home/sail/.composer

if [ -f composer.json ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f package.json ]; then
    npm install
fi

if [ -f artisan ]; then
    php artisan key:generate --force --no-interaction || true
    php artisan optimize:clear || true
fi
