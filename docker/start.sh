#!/bin/sh
set -e

cd /var/www/html

# Clear any cached config from build time
php artisan config:clear 2>/dev/null || true

# Debug: show database config
echo "DB_HOST: ${DB_HOST:-not set}"
echo "DB_DATABASE: ${DB_DATABASE:-not set}"

# Wait for database to be ready (if using MySQL)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-3306}"; do
        sleep 1
    done
    echo "Database is ready!"
else
    echo "WARNING: DB_HOST not set, skipping database wait"
fi

# Run migrations
php artisan migrate --force

# Publish Livewire assets
php artisan livewire:publish --assets

# Cache config and routes for production (now with correct env vars)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
