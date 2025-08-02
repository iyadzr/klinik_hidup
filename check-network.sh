#!/bin/bash

# Clinic Management System - Network Check Script
# This script only checks network binding without re-deploying

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

echo "=========================================="
echo "Checking Clinic Management System Network"
echo "=========================================="
echo ""

# 1. Check if containers are running
print_info "Checking Docker containers..."
NGINX_RUNNING=$(docker ps --filter "name=nginx" --format "{{.Names}}" | grep -c nginx || echo 0)
if [ "$NGINX_RUNNING" -gt 0 ]; then
    print_success "Nginx container is running"
else
    print_error "Nginx container is not running"
    exit 1
fi

# 2. Check port binding
print_info "Checking port 8090 binding..."

# Method 1: Docker port command (most reliable)
DOCKER_PORT=$(docker port $(docker ps -q -f name=nginx) 80 2>/dev/null || echo "")
if [[ "$DOCKER_PORT" == *"0.0.0.0:8090"* ]]; then
    print_success "Port 8090 is properly bound to all interfaces (0.0.0.0)"
else
    # Method 2: Try ss command
    if command -v ss &> /dev/null; then
        SS_CHECK=$(ss -tlnp 2>/dev/null | grep :8090 || echo "")
        if [[ "$SS_CHECK" == *"*:8090"* ]] || [[ "$SS_CHECK" == *"0.0.0.0:8090"* ]]; then
            print_success "Port 8090 is accessible (verified with ss)"
        else
            print_warning "Could not verify port binding with ss"
        fi
    # Method 3: Try netstat if available
    elif command -v netstat &> /dev/null; then
        NETSTAT_CHECK=$(netstat -tln 2>/dev/null | grep :8090 || echo "")
        if [[ "$NETSTAT_CHECK" == *":8090"* ]]; then
            print_success "Port 8090 is accessible (verified with netstat)"
        else
            print_warning "Could not verify port binding with netstat"
        fi
    else
        print_info "Network tools not available, checking with curl..."
    fi
fi

# 3. Test local access
print_info "Testing local access..."
if curl -f -s -o /dev/null http://localhost:8090; then
    print_success "Local access working (http://localhost:8090)"
else
    print_error "Local access failed"
fi

# 4. Get all network IPs
print_info "Detecting network interfaces..."
echo ""
echo "Available network addresses:"

# Method 1: Using ip command
if command -v ip &> /dev/null; then
    ip addr show 2>/dev/null | grep "inet " | grep -v "127.0.0.1" | while read line; do
        IP=$(echo $line | awk '{print $2}' | cut -d'/' -f1)
        IFACE=$(echo $line | awk '{print $NF}')
        echo -e "  ${GREEN}http://${IP}:8090${NC} (interface: $IFACE)"
    done
# Method 2: Using hostname command
elif command -v hostname &> /dev/null; then
    hostname -I 2>/dev/null | tr ' ' '\n' | grep -v '^$' | while read IP; do
        echo -e "  ${GREEN}http://${IP}:8090${NC}"
    done
# Method 3: Using ifconfig
elif command -v ifconfig &> /dev/null; then
    ifconfig 2>/dev/null | grep "inet " | grep -v "127.0.0.1" | awk '{print $2}' | sed 's/addr://' | while read IP; do
        echo -e "  ${GREEN}http://${IP}:8090${NC}"
    done
else
    print_warning "Cannot detect network interfaces automatically"
    echo "  Try accessing: http://<your-server-ip>:8090"
fi

echo ""
echo "=========================================="
print_success "Network check completed!"
echo "=========================================="
echo ""
echo "Quick tests you can run from another machine:"
echo "  curl -I http://192.168.100.70:8090"
echo "  curl -I http://192.168.100.71:8090"
echo ""