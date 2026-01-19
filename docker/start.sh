#!/bin/sh
set -e

cd /var/www/html

# Wait for database to be ready (if using MySQL)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-3306}"; do
        sleep 1
    done
    echo "Database is ready!"
fi

# Run migrations
php artisan migrate --force

# Cache config and routes for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
