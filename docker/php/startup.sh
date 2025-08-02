#!/bin/bash

# Startup script for PHP container
# This script runs migrations and then starts PHP-FPM

set -e

echo "Starting clinic management system..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "Database not ready yet, waiting 2 seconds..."
    sleep 2
done

echo "Database connection established!"

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Migrations completed successfully!"

# Clear cache
echo "Clearing application cache..."
php bin/console cache:clear --env=prod --no-debug

echo "Cache cleared!"

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm