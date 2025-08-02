#!/bin/bash

echo "🚀 Creating Default Users with SQL"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Database connection details
DB_HOST="localhost"
DB_PORT="3307"
DB_NAME="clinic_db"
DB_USER="clinic_user"
DB_PASS="clinic_password"

echo -e "${YELLOW}📋 Executing SQL to create users...${NC}"

# Execute SQL file
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < create-users-simple.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Users created successfully!${NC}"
    echo ""
    echo -e "${GREEN}Default Login Credentials:${NC}"
    echo "• Super Admin: superadmin / password"
    echo "• Doctor: doctor / password"
    echo "• Assistant: assistant / password"
    echo ""
    echo -e "${YELLOW}Test Login:${NC}"
    echo "curl -X POST http://192.168.68.56:8090/api/login \\"
    echo "  -H 'Content-Type: application/json' \\"
    echo "  -d '{\"username\":\"superadmin\",\"password\":\"password\"}'"
else
    echo -e "${RED}❌ Failed to create users${NC}"
    exit 1
fi