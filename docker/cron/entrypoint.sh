#!/bin/sh

set -e

if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "testing" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
    php artisan view:cache
fi

exec cron -f
