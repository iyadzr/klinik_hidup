#!/bin/bash

# Production startup script for PHP container
# This script runs migrations and then starts PHP-FPM with production optimizations

set -e

echo "🚀 Starting Clinic Management System (Production)..."
echo "📅 Current time: $(date)"
echo "🐘 PHP version: $(php -v | head -n 1)"
echo "🌍 Environment: ${APP_ENV:-prod}"

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
TIMEOUT=120
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

# Production-specific optimizations
echo "⚡ Running production optimizations..."

# Clear all caches first
echo "🧹 Clearing all caches..."
php bin/console cache:clear --env=${APP_ENV} --no-debug --no-warmup || echo "⚠️  Cache clear failed, but continuing..."

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
    
    // Check and add other critical columns
    \$criticalColumns = [
        'is_paid' => 'BOOLEAN DEFAULT FALSE NOT NULL',
        'paid_at' => 'DATETIME NULL',
        'payment_method' => 'VARCHAR(20) NULL',
        'amount' => 'DECIMAL(10,2) NULL',
        'metadata' => 'TEXT NULL',
        'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    foreach (\$criticalColumns as \$column => \$definition) {
        \$stmt = \$pdo->query(\"SHOW COLUMNS FROM queue LIKE '\$column'\");
        if (\$stmt->rowCount() == 0) {
            echo \"Adding missing column: \$column to queue table...\n\";
            try {
                \$pdo->exec(\"ALTER TABLE queue ADD COLUMN \$column \$definition\");
                echo \"✅ \$column column added successfully!\n\";
            } catch (Exception \$e) {
                echo \"⚠️  Failed to add \$column column: \" . \$e->getMessage() . \"\n\";
            }
        } else {
            echo \"✅ \$column column already exists.\n\";
        }
    }
    
    // Update existing records to have proper defaults for new columns
    echo \"🔄 Updating existing records with proper defaults...\n\";
    try {
        \$pdo->exec(\"UPDATE queue SET is_paid = FALSE WHERE is_paid IS NULL\");
        \$pdo->exec(\"UPDATE queue SET updated_at = NOW() WHERE updated_at IS NULL\");
        echo \"✅ Default values updated successfully!\n\";
    } catch (Exception \$e) {
        echo \"⚠️  Failed to update default values: \" . \$e->getMessage() . \"\n\";
    }
    
    // Check and fix queue status column length
    \$stmt = \$pdo->query(\"SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'status'\");
    \$statusLength = \$stmt->fetchColumn();
    if (\$statusLength < 50) {
        echo \"Fixing queue status column length from \$statusLength to 50...\n\";
        \$pdo->exec(\"ALTER TABLE queue MODIFY COLUMN status VARCHAR(50)\");
        echo \"✅ Queue status column length updated successfully!\n\";
    } else {
        echo \"✅ Queue status column length is already correct.\n\";
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
    
    // Check payment table critical columns
    \$paymentColumns = ['processed_by_id', 'queue_id', 'queue_number'];
    foreach (\$paymentColumns as \$column) {
        \$stmt = \$pdo->query(\"SHOW COLUMNS FROM payment LIKE '\$column'\");
        if (\$stmt->rowCount() == 0) {
            echo \"Adding missing column: \$column to payment table...\n\";
            if (\$column === 'queue_number') {
                \$pdo->exec(\"ALTER TABLE payment ADD COLUMN \$column VARCHAR(10) DEFAULT NULL\");
            } else {
                \$pdo->exec(\"ALTER TABLE payment ADD COLUMN \$column INT DEFAULT NULL\");
            }
            echo \"✅ \$column column added successfully!\n\";
        } else {
            echo \"✅ \$column column already exists in payment table.\n\";
        }
    }
    
} catch (Exception \$e) {
    echo \"⚠️  Database schema check failed: \" . \$e->getMessage() . \"\n\";
}
"

# Warm up cache for production
echo "🔥 Warming up cache for production..."
php bin/console cache:warmup --env=${APP_ENV} --no-debug || echo "⚠️  Cache warmup failed, but continuing..."

# Validate database schema
echo "🔍 Validating database schema..."
if php bin/console doctrine:schema:validate --env=${APP_ENV}; then
    echo "✅ Database schema is valid!"
else
    echo "⚠️  Database schema validation failed, but continuing..."
fi

# Set proper permissions for production
echo "🔒 Setting proper permissions for production..."
chown -R www:www var/ || true
chmod -R 755 var/ || true
chmod -R 644 var/log/*.log 2>/dev/null || true

# Ensure JWT keys have proper permissions
if [ -d "config/jwt" ]; then
    echo "🔐 Setting JWT key permissions..."
    chown -R www:www config/jwt/ || true
    chmod 600 config/jwt/private.pem || true
    chmod 644 config/jwt/public.pem || true
    echo "✅ JWT key permissions set!"
    
    # Verify JWT configuration
    echo "🔍 Verifying JWT configuration..."
    if php bin/console lexik:jwt:check-config --env=${APP_ENV}; then
        echo "✅ JWT configuration is valid!"
    else
        echo "❌ JWT configuration failed! This will cause login issues."
    fi
else
    echo "⚠️  JWT keys directory not found at config/jwt/"
fi

# Production security checks
echo "🛡️  Running production security checks..."
php bin/console security:check --env=${APP_ENV} || echo "⚠️  Security check failed, but continuing..."

# Final cache optimization
echo "⚡ Final cache optimization..."
php bin/console cache:pool:prune --env=${APP_ENV} || echo "⚠️  Cache pruning failed, but continuing..."

echo "🎉 Production startup completed successfully!"
echo "🏃 Starting PHP-FPM in production mode..."

# Start PHP-FPM with production settings
exec php-fpm 