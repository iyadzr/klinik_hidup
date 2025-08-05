#!/bin/bash

echo "🔍 Verifying Production Authentication Fix"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check 1: JWT Keys
echo -e "${BLUE}1. Checking JWT Keys...${NC}"
if [ -f "config/jwt/private.pem" ] && [ -f "config/jwt/public.pem" ]; then
    echo -e "${GREEN}✅ JWT keys exist${NC}"
    ls -la config/jwt/
else
    echo -e "${RED}❌ JWT keys missing${NC}"
    echo "Run: mkdir -p config/jwt && generate keys"
fi

echo ""

# Check 2: Container Status
echo -e "${BLUE}2. Checking Container Status...${NC}"
docker-compose ps

echo ""

# Check 3: Nginx Configuration
echo -e "${BLUE}3. Checking Nginx Configuration...${NC}"
NGINX_CONTAINER=$(docker-compose ps -q nginx)
if [ ! -z "$NGINX_CONTAINER" ]; then
    echo "Checking for HTTP_AUTHORIZATION parameter..."
    AUTH_COUNT=$(docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/default.conf | grep -c "HTTP_AUTHORIZATION")
    if [ "$AUTH_COUNT" -ge "2" ]; then
        echo -e "${GREEN}✅ Nginx configuration includes Authorization header fix${NC}"
        docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/default.conf | grep -A 2 -B 2 "HTTP_AUTHORIZATION"
    else
        echo -e "${RED}❌ Nginx configuration missing Authorization header fix${NC}"
        echo "Need to rebuild nginx container"
    fi
else
    echo -e "${RED}❌ Nginx container not running${NC}"
fi

echo ""

# Check 4: JWT Configuration
echo -e "${BLUE}4. Checking JWT Configuration...${NC}"
APP_CONTAINER=$(docker-compose ps -q app)
if [ ! -z "$APP_CONTAINER" ]; then
    docker exec $APP_CONTAINER php bin/console lexik:jwt:check-config
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ JWT configuration is correct${NC}"
    else
        echo -e "${RED}❌ JWT configuration has issues${NC}"
    fi
else
    echo -e "${RED}❌ App container not running${NC}"
fi

echo ""

# Check 5: Authorization Header Transmission Test
echo -e "${BLUE}5. Testing Authorization Header Transmission...${NC}"
echo "Testing with curl..."
RESPONSE=$(curl -s -X GET "http://localhost:8090/api/patients/count" -H "Authorization: Bearer test-token" 2>/dev/null)
if echo "$RESPONSE" | grep -q "Invalid JWT Token"; then
    echo -e "${GREEN}✅ Authorization header is being transmitted correctly${NC}"
    echo "Response: $RESPONSE"
elif echo "$RESPONSE" | grep -q "401"; then
    echo -e "${YELLOW}⚠️ Getting 401 but not JWT-specific error${NC}"
    echo "Response: $RESPONSE"
    echo "This might indicate Authorization header is not being passed"
else
    echo -e "${RED}❌ Unexpected response${NC}"
    echo "Response: $RESPONSE"
fi

echo ""

# Summary
echo -e "${BLUE}6. Summary and Next Steps...${NC}"
echo ""
echo "If Authorization header transmission test fails:"
echo "1. Rebuild nginx container: docker-compose build --no-cache nginx"
echo "2. Restart nginx: docker-compose up -d nginx"
echo "3. Re-run this verification script"
echo ""
echo "If JWT configuration fails:"
echo "1. Check JWT keys exist and have correct permissions"
echo "2. Restart app container: docker-compose restart app"
echo ""
echo "If all checks pass but login still shows session expiration:"
echo "1. Clear browser cache completely"
echo "2. Try incognito/private browsing mode"
echo "3. Check browser console for specific error messages"