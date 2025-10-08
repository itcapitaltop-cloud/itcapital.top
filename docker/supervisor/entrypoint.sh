#!/bin/sh
set -e

# Change to the application directory
cd /var/www/html

if [ "$APP_ENV" != "local" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
    php artisan view:cache
fi

exec supervisord -c /etc/supervisord.conf
