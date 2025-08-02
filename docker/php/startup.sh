#!/bin/bash

# Startup script for PHP container
# This script runs migrations and then starts PHP-FPM

set -e

echo "🚀 Starting Clinic Management System..."
echo "📅 Current time: $(date)"
echo "🐘 PHP version: $(php -v | head -n 1)"

# Function to check database connection
check_database() {
    php -r "
    try {
        \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=clinic_db', 'clinic_user', 'clinic_password');
        \$pdo->query('SELECT 1');
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
    "
}

# Wait for database to be ready with timeout
echo "⏳ Waiting for database connection..."
TIMEOUT=60
COUNTER=0

until check_database; do
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ Database connection timeout after ${TIMEOUT} seconds!"
        exit 1
    fi
    echo "🔄 Database not ready yet, waiting 2 seconds... ($COUNTER/$TIMEOUT)"
    sleep 2
    ((COUNTER+=2))
done

echo "✅ Database connection established!"

# Check if we can access Symfony console
if [ ! -f "bin/console" ]; then
    echo "❌ Symfony console not found!"
    exit 1
fi

# Set proper environment
export APP_ENV=${APP_ENV:-prod}
export SYMFONY_ENV=${APP_ENV}

# Run database migrations
echo "🗄️  Running database migrations..."
if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=${APP_ENV}; then
    echo "✅ Migrations completed successfully!"
else
    echo "⚠️  Migrations failed, but continuing..."
fi

# Clear and warm up cache
echo "🧹 Clearing application cache..."
if php bin/console cache:clear --env=${APP_ENV} --no-debug; then
    echo "✅ Cache cleared successfully!"
else
    echo "⚠️  Cache clear failed, but continuing..."
fi

# Warm up cache
echo "🔥 Warming up cache..."
php bin/console cache:warmup --env=${APP_ENV} --no-debug || echo "⚠️  Cache warmup failed, but continuing..."

# Set proper permissions
echo "🔒 Setting proper permissions..."
chown -R www:www var/ || true
chmod -R 755 var/ || true

echo "🎉 Startup completed successfully!"
echo "🏃 Starting PHP-FPM..."

# Start PHP-FPM
exec php-fpm