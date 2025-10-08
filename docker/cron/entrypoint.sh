#!/bin/bash
set -e

# Change to the application directory
cd /var/www/html

# Wait for application to be ready
sleep 5

# Create environment file for cron
echo "Creating environment file for cron..."
cat > /var/www/html/.env.cron << 'EOF'
# Source the main environment file
if [ -f /var/www/html/.env ]; then
    export $(grep -v '^#' /var/www/html/.env | xargs -d '\n')
fi
EOF

chmod 644 /var/www/html/.env.cron

# Create log file
touch /var/log/cron.log
chmod 666 /var/log/cron.log

# Apply cron job
crontab /etc/cron.d/app

# Cache configuration if not in local/testing
if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "testing" ]; then
    echo "Caching Laravel configuration..."
    php artisan config:cache || true
fi

# Start cron in foreground and tail log
echo "Starting cron daemon..."
cron

# Tail the log file to keep container running and see output
echo "Tailing cron log..."
tail -f /var/log/cron.log
