#!/bin/bash

echo "🔧 Fixing Production Nginx Configuration"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to check if command succeeded
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1 failed${NC}"
        exit 1
    fi
}

echo -e "${BLUE}Step 1: Stopping nginx container...${NC}"
docker-compose stop nginx
check_status "Nginx stopped"

echo -e "${BLUE}Step 2: Removing nginx container and image...${NC}"
docker-compose rm -f nginx
docker rmi $(docker images -q klinik_hidup-nginx) 2>/dev/null
check_status "Nginx container and image removed"

echo -e "${BLUE}Step 3: Rebuilding nginx container with latest configuration...${NC}"
docker-compose build --no-cache nginx
check_status "Nginx rebuilt"

echo -e "${BLUE}Step 4: Starting nginx container...${NC}"
docker-compose up -d nginx
check_status "Nginx started"

echo -e "${BLUE}Step 5: Waiting for nginx to be ready...${NC}"
sleep 5

echo -e "${BLUE}Step 6: Verifying nginx configuration...${NC}"
NGINX_CONTAINER=$(docker-compose ps -q nginx)
if [ ! -z "$NGINX_CONTAINER" ]; then
    AUTH_COUNT=$(docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/default.conf | grep -c "HTTP_AUTHORIZATION")
    if [ "$AUTH_COUNT" -ge "2" ]; then
        echo -e "${GREEN}✅ Nginx configuration correctly includes Authorization header fix${NC}"
        echo "Found HTTP_AUTHORIZATION parameter in nginx config:"
        docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/default.conf | grep -A 1 -B 1 "HTTP_AUTHORIZATION"
    else
        echo -e "${RED}❌ Nginx configuration still missing Authorization header fix${NC}"
        echo "This indicates the docker/nginx/nginx.conf file may not have been updated"
        exit 1
    fi
else
    echo -e "${RED}❌ Nginx container not running${NC}"
    exit 1
fi

echo -e "${BLUE}Step 7: Testing Authorization header transmission...${NC}"
sleep 2
RESPONSE=$(curl -s -X GET "http://localhost:8090/api/patients/count" -H "Authorization: Bearer test-token" 2>/dev/null)
if echo "$RESPONSE" | grep -q "Invalid JWT Token"; then
    echo -e "${GREEN}✅ Authorization header transmission working correctly!${NC}"
    echo "Test response: $RESPONSE"
    echo ""
    echo -e "${GREEN}🎉 Production nginx fix applied successfully!${NC}"
    echo "The session expiration issue should now be resolved."
    echo ""
    echo "Next steps:"
    echo "1. Clear browser cache completely"
    echo "2. Test login flow in incognito/private browsing mode"
    echo "3. Verify dashboard loads without session expiration popup"
else
    echo -e "${YELLOW}⚠️ Unexpected response from test:${NC}"
    echo "Response: $RESPONSE"
    echo "You may need to check firewall/network settings"
fi