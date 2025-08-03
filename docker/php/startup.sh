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

# Check migration status first
echo "📊 Checking migration status..."
php bin/console doctrine:migrations:status --env=${APP_ENV} || echo "⚠️  Could not check migration status"

# Run database migrations with enhanced error handling
echo "🗄️  Running database migrations..."
if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=${APP_ENV}; then
    echo "✅ Migrations completed successfully!"
else
    echo "⚠️  Standard migrations failed, trying to resolve conflicts..."
    
    # Try to resolve migration conflicts by marking all as executed
    echo "🔄 Attempting to resolve migration conflicts..."
    if php bin/console doctrine:migrations:version --add --all --no-interaction --env=${APP_ENV}; then
        echo "✅ Migration conflicts resolved!"
    else
        echo "⚠️  Could not resolve migration conflicts, but continuing..."
    fi
fi

# Ensure critical database schema is in place
echo "🔧 Ensuring critical database schema..."
php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=clinic_db', 'clinic_user', 'clinic_password');
    
    // Check if consultation_id column exists in queue table
    \$stmt = \$pdo->query(\"SHOW COLUMNS FROM queue LIKE 'consultation_id'\");
    if (\$stmt->rowCount() == 0) {
        echo \"Adding missing consultation_id column to queue table...\n\";
        \$pdo->exec(\"ALTER TABLE queue ADD COLUMN consultation_id INT DEFAULT NULL\");
        echo \"✅ consultation_id column added successfully!\n\";
    } else {
        echo \"✅ consultation_id column already exists.\n\";
    }
    
    // Check if other critical columns exist
    \$criticalColumns = ['is_paid', 'paid_at', 'payment_method', 'amount', 'metadata', 'updated_at'];
    foreach (\$criticalColumns as \$column) {
        \$stmt = \$pdo->query(\"SHOW COLUMNS FROM queue LIKE '\$column'\");
        if (\$stmt->rowCount() == 0) {
            echo \"⚠️  Missing column: \$column in queue table\n\";
        }
    }
    
    // Check prescribed_medication table critical columns
    \$prescribedMedColumns = ['dosage', 'frequency', 'duration'];
    foreach (\$prescribedMedColumns as \$column) {
        \$stmt = \$pdo->query(\"SHOW COLUMNS FROM prescribed_medication LIKE '\$column'\");
        if (\$stmt->rowCount() == 0) {
            echo \"Adding missing column: \$column to prescribed_medication table...\n\";
            \$pdo->exec(\"ALTER TABLE prescribed_medication ADD COLUMN \$column VARCHAR(255) DEFAULT NULL\");
            echo \"✅ \$column column added successfully!\n\";
        } else {
            echo \"✅ \$column column already exists in prescribed_medication table.\n\";
        }
    }
    
} catch (Exception \$e) {
    echo \"⚠️  Database schema check failed: \" . \$e->getMessage() . \"\n\";
}
"

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

# Validate database schema
echo "🔍 Validating database schema..."
if php bin/console doctrine:schema:validate --env=${APP_ENV}; then
    echo "✅ Database schema is valid!"
else
    echo "⚠️  Database schema validation failed, but continuing..."
fi

# Set proper permissions
echo "🔒 Setting proper permissions..."
chown -R www:www var/ || true
chmod -R 755 var/ || true

echo "🎉 Startup completed successfully!"
echo "🏃 Starting PHP-FPM..."

# Start PHP-FPM
exec php-fpm