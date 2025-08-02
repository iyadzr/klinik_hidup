#!/bin/bash

echo "🚀 Deploying Default Users for Clinic Management System"
echo "======================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running in Docker
if [ -f /.dockerenv ]; then
    echo -e "${GREEN}✅ Running inside Docker container${NC}"
    php /var/www/html/create-default-users.php
else
    echo -e "${YELLOW}📦 Running on host - using Docker exec${NC}"
    
    # Try different possible container names
    CONTAINER_NAMES=("klinik_hidup-app-1" "clinic-management-system-app-1" "app-1")
    CONTAINER_FOUND=""
    
    for container in "${CONTAINER_NAMES[@]}"; do
        if docker ps --format "table {{.Names}}" | grep -q "$container"; then
            CONTAINER_FOUND="$container"
            break
        fi
    done
    
    if [ -z "$CONTAINER_FOUND" ]; then
        echo -e "${RED}❌ No app container found. Tried: ${CONTAINER_NAMES[*]}${NC}"
        echo "Available containers:"
        docker ps --format "table {{.Names}}\t{{.Status}}"
        exit 1
    fi
    
    echo -e "${GREEN}📋 Found container: $CONTAINER_FOUND${NC}"
    
    # Copy script to container and execute
    docker cp create-default-users.php "$CONTAINER_FOUND":/var/www/html/
    docker exec "$CONTAINER_FOUND" php /var/www/html/create-default-users.php
    
    # Clean up
    docker exec "$CONTAINER_FOUND" rm -f /var/www/html/create-default-users.php
fi

echo ""
echo -e "${GREEN}🎉 Deployment completed!${NC}"
echo ""
echo -e "${YELLOW}Test login with curl:${NC}"
echo "curl -X POST http://localhost:8090/api/login \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"username\":\"superadmin\",\"password\":\"password\"}'"