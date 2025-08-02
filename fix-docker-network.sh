#!/bin/bash

# Fix Docker network access issue

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "======================================"
echo "Fixing Docker Network Access"
echo "======================================"

# 1. Stop containers
echo -e "${YELLOW}→ Stopping containers...${NC}"
docker compose down

# 2. Clean up Docker networks
echo -e "${YELLOW}→ Cleaning Docker networks...${NC}"
docker network prune -f

# 3. Ensure Docker daemon settings
echo -e "${YELLOW}→ Checking Docker daemon settings...${NC}"
if [ -f /etc/docker/daemon.json ]; then
    echo "Current Docker daemon config:"
    cat /etc/docker/daemon.json
else
    echo "No custom Docker daemon config found"
fi

# 4. Restart Docker service
echo -e "${YELLOW}→ Restarting Docker service...${NC}"
sudo systemctl restart docker
sleep 5

# 5. Start containers with explicit port binding
echo -e "${YELLOW}→ Starting containers...${NC}"
docker compose up -d

# 6. Wait for containers
echo -e "${YELLOW}→ Waiting for containers to be ready...${NC}"
sleep 10

# 7. Force recreate iptables rules
echo -e "${YELLOW}→ Checking iptables rules...${NC}"
# Check if Docker created the rules
if ! sudo iptables -t nat -L -n | grep -q 8090; then
    echo -e "${YELLOW}→ Manually adding iptables rules...${NC}"
    # Get container IP
    CONTAINER_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $(docker ps -q -f name=nginx))
    
    # Add NAT rules manually if needed
    sudo iptables -t nat -A DOCKER -p tcp --dport 8090 -j DNAT --to-destination ${CONTAINER_IP}:80
    sudo iptables -t filter -A DOCKER -p tcp -d ${CONTAINER_IP} --dport 80 -j ACCEPT
fi

# 8. Verify everything
echo -e "${YELLOW}→ Verifying setup...${NC}"
echo "Docker port binding:"
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep nginx

echo -e "\nIPtables NAT rules:"
sudo iptables -t nat -L -n -v | grep 8090 || echo "No NAT rules found"

echo -e "\nTesting access:"
curl -s -o /dev/null -w "Local (localhost): %{http_code}\n" http://localhost:8090
curl -s -o /dev/null -w "Local (IP): %{http_code}\n" http://192.168.100.71:8090

echo -e "\n${GREEN}✓ Fix completed!${NC}"
echo "Try accessing from your Mac now: curl http://192.168.100.71:8090/"