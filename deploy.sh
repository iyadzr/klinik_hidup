#!/bin/bash

# Clinic Management System Deployment Script
# This script deploys the application on Ubuntu server with Docker

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
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

# Check if running as root or with sudo
if [ "$EUID" -eq 0 ]; then 
   print_info "Running as root user"
else
   print_error "Please run this script with sudo"
   echo "Usage: sudo ./deploy.sh"
   exit 1
fi

print_info "Starting Clinic Management System deployment..."

# 1. Check if Docker is installed
print_info "Checking Docker installation..."
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose v2 first."
    exit 1
fi
print_success "Docker and Docker Compose are installed"

# 2. Check and configure firewall
print_info "Checking firewall configuration..."
if command -v ufw &> /dev/null; then
    if ufw status | grep -q "Status: active"; then
        print_info "UFW firewall is active, checking port 8090..."
        if ! ufw status | grep -q "8090"; then
            print_info "Opening port 8090..."
            ufw allow 8090
            print_success "Port 8090 opened in firewall"
        else
            print_success "Port 8090 is already open"
        fi
    else
        print_info "UFW firewall is not active"
    fi
else
    print_info "UFW not installed, skipping firewall configuration"
fi

# 3. Pull latest changes from git
print_info "Pulling latest changes from git..."
git pull || {
    print_error "Failed to pull from git. Please commit or stash your changes."
    exit 1
}
print_success "Git repository updated"

# 4. Stop and remove old containers
print_info "Stopping and removing old containers..."
docker compose down -v 2>/dev/null || true
docker compose -f docker-compose.prod.yml down -v 2>/dev/null || true
print_success "Old containers removed"

# 5. Clean up orphan containers
print_info "Cleaning up orphan containers..."
docker compose down --remove-orphans 2>/dev/null || true
print_success "Orphan containers removed"

# 6. Check which compose file to use
COMPOSE_FILE="docker-compose.yml"
if [ -f "docker-compose.prod.yml" ]; then
    COMPOSE_FILE="docker-compose.prod.yml"
    print_info "Using production docker-compose file"
else
    print_info "Using default docker-compose file"
fi

# 7. Build and start containers
print_info "Building and starting containers..."
docker compose -f $COMPOSE_FILE build --no-cache
docker compose -f $COMPOSE_FILE up -d

# 8. Wait for containers to be healthy
print_info "Waiting for containers to be healthy..."
sleep 10

# 9. Check container status
print_info "Checking container status..."
CONTAINERS=$(docker compose -f $COMPOSE_FILE ps --format json)

# Check if all containers are running
ALL_RUNNING=true
while IFS= read -r line; do
    if [ -n "$line" ]; then
        STATE=$(echo $line | grep -o '"State":"[^"]*"' | cut -d'"' -f4)
        NAME=$(echo $line | grep -o '"Name":"[^"]*"' | cut -d'"' -f4)
        
        if [ "$STATE" != "running" ]; then
            print_error "Container $NAME is not running (State: $STATE)"
            ALL_RUNNING=false
        fi
    fi
done <<< "$CONTAINERS"

if [ "$ALL_RUNNING" = true ]; then
    print_success "All containers are running"
else
    print_error "Some containers are not running. Checking logs..."
    docker compose -f $COMPOSE_FILE logs --tail=50
    exit 1
fi

# 10. Run database migrations
print_info "Running database migrations..."
# Wait for MySQL to be fully ready
sleep 15

# Check if migrations table exists and create if not
docker compose -f $COMPOSE_FILE exec -T mysql mysql -u clinic_user -pclinic_password clinic_db -e "
CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
" 2>/dev/null || true

# Add missing columns to queue table if needed
docker compose -f $COMPOSE_FILE exec -T mysql mysql -u clinic_user -pclinic_password clinic_db -e "
ALTER TABLE queue 
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP;
" 2>/dev/null || true

print_success "Database prepared"

# 11. Clear application cache
print_info "Clearing application cache..."
docker compose -f $COMPOSE_FILE exec -T app php bin/console cache:clear --env=prod --no-debug
print_success "Application cache cleared"

# 12. Test local access
print_info "Testing local access..."
sleep 5
if curl -f -s http://localhost:8090 > /dev/null; then
    print_success "Local access test passed"
else
    print_error "Local access test failed"
    docker compose -f $COMPOSE_FILE logs --tail=50
    exit 1
fi

# 13. Check network binding
print_info "Checking network binding..."
# Try ss first (modern), then netstat (legacy), then docker port
if command -v ss &> /dev/null; then
    BINDING=$(ss -tlnp 2>/dev/null | grep :8090 | grep -o '0.0.0.0:8090' || true)
elif command -v netstat &> /dev/null; then
    BINDING=$(netstat -tlnp 2>/dev/null | grep :8090 | grep -o '0.0.0.0:8090' || true)
else
    # Use docker port command as fallback
    BINDING=$(docker port $(docker compose -f $COMPOSE_FILE ps -q nginx) 80 2>/dev/null | grep -o '0.0.0.0:8090' || true)
fi

if [ "$BINDING" = "0.0.0.0:8090" ]; then
    print_success "Application is bound to all interfaces (0.0.0.0:8090)"
else
    # Check with docker directly
    DOCKER_BINDING=$(docker compose -f $COMPOSE_FILE ps --format json | grep -o '"8090->80"' || true)
    if [ -n "$DOCKER_BINDING" ]; then
        print_success "Application is accessible on port 8090 (verified via Docker)"
    else
        print_warning "Could not verify network binding (tools not available)"
    fi
fi

# 14. Get server IP
# Try multiple methods to get the correct IP
if command -v ip &> /dev/null; then
    # Get IP from default route interface
    SERVER_IP=$(ip route get 1.1.1.1 | grep -oP 'src \K[^ ]+' | head -1)
elif command -v hostname &> /dev/null; then
    SERVER_IP=$(hostname -I | awk '{print $1}')
else
    # Fallback to parsing ifconfig
    SERVER_IP=$(ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1' | head -1)
fi

# Validate IP
if [[ ! "$SERVER_IP" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
    SERVER_IP="<server-ip>"
fi

# 15. Show all network interfaces (for debugging)
print_info "Network interfaces:"
if command -v ip &> /dev/null; then
    ip addr show | grep -E "inet " | grep -v "127.0.0.1" | awk '{print "  - " $2 " on " $NF}'
elif command -v ifconfig &> /dev/null; then
    ifconfig | grep -E "inet " | grep -v "127.0.0.1" | awk '{print "  - " $2}'
fi

# 16. Final status
echo ""
echo "=========================================="
echo -e "${GREEN}Deployment completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Application URLs:"
echo -e "  Local:    ${GREEN}http://localhost:8090${NC}"
echo -e "  Network:  ${GREEN}http://${SERVER_IP}:8090${NC}"
echo ""
echo "If the network URL above is incorrect, try these:"
ip addr show 2>/dev/null | grep -E "inet " | grep -v "127.0.0.1" | awk '{print "  - http://" $2 ":8090"}' | sed 's|/[0-9]*:|\:|g'
echo ""
echo "Container status:"
docker compose -f $COMPOSE_FILE ps
echo ""
echo "To view logs:"
echo "  docker compose -f $COMPOSE_FILE logs -f"
echo ""
echo "To stop the application:"
echo "  docker compose -f $COMPOSE_FILE down"
echo ""

# Create systemd service for auto-start (optional)
print_info "Do you want to enable auto-start on system boot? (y/N)"
read -r response
if [[ "$response" =~ ^[Yy]$ ]]; then
    cat > /etc/systemd/system/clinic-management.service << EOF
[Unit]
Description=Clinic Management System
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=$(pwd)
ExecStart=/usr/bin/docker compose -f $COMPOSE_FILE up -d
ExecStop=/usr/bin/docker compose -f $COMPOSE_FILE down
StandardOutput=journal

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable clinic-management.service
    print_success "Auto-start service enabled"
fi

print_success "Deployment script completed!"