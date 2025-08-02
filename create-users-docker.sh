#!/bin/bash

echo "🚀 Creating Default Users via Docker MySQL"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Try to find MySQL container
MYSQL_CONTAINERS=("clinic-management-system-mysql-1" "klinik_hidup-mysql-1" "symfony_db")
MYSQL_CONTAINER=""

for container in "${MYSQL_CONTAINERS[@]}"; do
    if docker ps --format "table {{.Names}}" | grep -q "$container"; then
        MYSQL_CONTAINER="$container"
        break
    fi
done

if [ -z "$MYSQL_CONTAINER" ]; then
    echo -e "${RED}❌ No MySQL container found. Tried: ${MYSQL_CONTAINERS[*]}${NC}"
    echo "Available containers:"
    docker ps --format "table {{.Names}}\t{{.Status}}"
    exit 1
fi

echo -e "${GREEN}📋 Found MySQL container: $MYSQL_CONTAINER${NC}"

# Create users directly in MySQL container
echo -e "${YELLOW}📝 Creating default users...${NC}"

docker exec "$MYSQL_CONTAINER" mysql -u clinic_user -pclinic_password clinic_db -e "
INSERT IGNORE INTO \`user\` (
    \`username\`, 
    \`email\`, 
    \`name\`, 
    \`password\`, 
    \`roles\`, 
    \`allowed_pages\`, 
    \`is_active\`, 
    \`created_at\`, 
    \`updated_at\`
) VALUES 
(
    'superadmin',
    'superadmin@clinic.com',
    'Super Admin',
    '\$2y\$13\$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '[\"ROLE_SUPER_ADMIN\",\"ROLE_ADMIN\",\"ROLE_USER\"]',
    '[\"dashboard\",\"patients\",\"doctors\",\"queue\",\"consultations\",\"payments\",\"medicines\",\"users\",\"settings\",\"reports\"]',
    1,
    NOW(),
    NOW()
),
(
    'doctor',
    'doctor@clinic.com',
    'Dr. Default',
    '\$2y\$13\$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '[\"ROLE_DOCTOR\",\"ROLE_USER\"]',
    '[\"dashboard\",\"queue\",\"consultations\",\"patients\",\"medicines\"]',
    1,
    NOW(),
    NOW()
),
(
    'assistant',
    'assistant@clinic.com',
    'Clinic Assistant',
    '\$2y\$13\$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '[\"ROLE_ASSISTANT\",\"ROLE_USER\"]',
    '[\"dashboard\",\"queue\",\"patients\",\"payments\"]',
    1,
    NOW(),
    NOW()
);"

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
    echo ""
    echo -e "${YELLOW}Verify users created:${NC}"
    echo "docker exec $MYSQL_CONTAINER mysql -u clinic_user -pclinic_password clinic_db -e 'SELECT username, email, name FROM user;'"
else
    echo -e "${RED}❌ Failed to create users${NC}"
    exit 1
fi