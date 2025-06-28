#!/bin/bash
# Script to deploy the test database for clinic management system

echo "🚀 Setting up test database for clinic management system..."

# Check if Docker containers are running
if ! docker ps | grep -q "clinic-management-system-mysql-1"; then
    echo "❌ MySQL container is not running. Please start Docker containers first with:"
    echo "   docker-compose up -d"
    exit 1
fi

echo "📋 Creating test database and setting up schema..."

# Execute the test database setup script
docker exec -i clinic-management-system-mysql-1 mysql -uroot -proot_password < setup_test_database.sql

if [ $? -eq 0 ]; then
    echo "✅ Test database deployed successfully!"
    echo ""
    echo "📊 Test database details:"
    echo "   Database name: clinic_db_test"
    echo "   User: clinic_user"
    echo "   Password: clinic_password"
    echo "   Host: localhost (or mysql container)"
    echo "   Port: 3307"
    echo ""
    echo "🧪 You can now run PHPUnit tests:"
    echo "   docker exec clinic-management-system-php-1 vendor/bin/phpunit --testdox"
    echo ""
    echo "🔧 Or run tests with coverage:"
    echo "   docker exec clinic-management-system-php-1 vendor/bin/phpunit --coverage-text"
else
    echo "❌ Failed to deploy test database. Please check the error messages above."
    exit 1
fi
