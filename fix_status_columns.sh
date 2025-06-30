#!/bin/bash

# Fix status column length issue in consultation and queue tables
# This resolves the error: "Data too long for column 'status' at row 1"

echo "🔧 Fixing status column length in consultation and queue tables..."

# Check if Docker is running
if docker-compose ps app | grep -q "Up"; then
    echo "✅ Docker container is running, applying fix via container..."
    
    # Apply the SQL fix via Docker
    docker-compose exec app mysql -u root -proot clinic_management < fix_consultation_status_column.sql
    
    # Run the migration as well
    docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
    
    echo "✅ Database fix applied successfully via Docker!"
    
else
    echo "⚠️  Docker container is not running."
    echo "Please apply the following SQL manually to your database:"
    echo ""
    echo "-- Fix status column length for consultation and queue tables"
    echo "ALTER TABLE consultation MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending';"
    echo "ALTER TABLE queue MODIFY COLUMN status VARCHAR(50) DEFAULT 'waiting';"
    echo ""
    echo "You can also run: mysql -u [username] -p [database_name] < fix_consultation_status_column.sql"
    echo ""
    echo "📁 SQL fix file created: fix_consultation_status_column.sql"
    echo "📁 Migration file created: migrations/Version20250130000000_FixConsultationStatusLength.php"
fi

echo ""
echo "🎯 This fix allows the status field to store values like:"
echo "   - 'completed_consultation' (22 characters)"
echo "   - 'in_consultation' (15 characters)" 
echo "   - 'waiting' (7 characters)"
echo "   - 'completed' (9 characters)"
echo ""
echo "✨ After applying this fix, your consultation saving should work properly!" 