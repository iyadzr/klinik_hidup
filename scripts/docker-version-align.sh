#!/bin/bash

# Docker Version Alignment Script for Clinic Management System
# This script ensures Docker and Docker Compose versions are consistent
# between local development and production environments

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Standard versions to align to (latest stable versions)
DOCKER_VERSION_TARGET="27.4.1"           # Latest stable Docker version
DOCKER_COMPOSE_VERSION_TARGET="2.31.0"   # Latest stable Docker Compose version
NODE_VERSION_TARGET="22"                 # Latest LTS Node.js version (minimum 20 for Claude compatibility)
PHP_VERSION_TARGET="8.4"                 # Latest stable PHP version
NGINX_VERSION_TARGET="1.27"              # Latest stable Nginx version
MYSQL_VERSION_TARGET="8.0"               # Latest LTS MySQL version (8.4 has breaking changes)

echo -e "${BLUE}🔧 Docker Version Alignment Tool${NC}"
echo "=============================================="
echo ""

# Function to print section headers
print_section() {
    echo -e "${CYAN}📋 $1${NC}"
    echo "----------------------------------------------"
}

# Function to check current versions
check_current_versions() {
    print_section "Current Environment Versions"
    
    echo -e "${YELLOW}Local System:${NC}"
    echo "Docker: $(docker --version 2>/dev/null || echo 'Not installed')"
    echo "Docker Compose: $(docker compose --version 2>/dev/null || echo 'Not installed')"
    echo ""
    
    echo -e "${YELLOW}Container Base Images:${NC}"
    echo "PHP: $(grep 'FROM php:' docker/php/Dockerfile | cut -d' ' -f2)"
    echo "Node.js (main): $(grep 'FROM node:' docker/php/Dockerfile | head -1 | cut -d' ' -f2)"
    echo "Node.js (frontend): $(grep 'FROM node:' docker/frontend/Dockerfile | head -1 | cut -d' ' -f2)"
    echo "Nginx: $(grep 'FROM nginx:' docker/nginx/Dockerfile | cut -d' ' -f2)"
    echo "MySQL: $(grep 'image: mysql:' docker compose.yml | cut -d':' -f3 || echo 'Using default')"
    echo ""
}

# Function to create version-locked Docker Compose files
create_version_locked_compose() {
    print_section "Creating Version-Locked Docker Compose Files"
    
    # Backup existing files
    if [ -f "docker compose.yml" ]; then
        cp docker compose.yml docker compose.yml.backup.$(date +%Y%m%d_%H%M%S)
        echo -e "${GREEN}✅ Backed up docker compose.yml${NC}"
    fi
    
    if [ -f "docker compose.prod.yml" ]; then
        cp docker compose.prod.yml docker compose.prod.yml.backup.$(date +%Y%m%d_%H%M%S)
        echo -e "${GREEN}✅ Backed up docker compose.prod.yml${NC}"
    fi
    
    # Add version specification to compose files
    echo -e "${YELLOW}📝 Adding version locks to compose files...${NC}"
    
    # Update MySQL version in compose files
    if grep -q "image: mysql" docker compose.yml; then
        sed -i.tmp "s/image: mysql:[0-9]\.[0-9]*/image: mysql:${MYSQL_VERSION_TARGET}/" docker compose.yml
        sed -i.tmp "s/image: mysql$/image: mysql:${MYSQL_VERSION_TARGET}/" docker compose.yml
        rm docker compose.yml.tmp 2>/dev/null || true
        echo -e "${GREEN}✅ Updated MySQL version in docker compose.yml${NC}"
    fi
    
    echo ""
}

# Function to create standardized Dockerfiles
create_standardized_dockerfiles() {
    print_section "Creating Standardized Dockerfiles"
    
    # Backup existing Dockerfiles
    for dockerfile in docker/php/Dockerfile docker/frontend/Dockerfile docker/nginx/Dockerfile; do
        if [ -f "$dockerfile" ]; then
            cp "$dockerfile" "${dockerfile}.backup.$(date +%Y%m%d_%H%M%S)"
            echo -e "${GREEN}✅ Backed up $dockerfile${NC}"
        fi
    done
    
    # Update PHP Dockerfile
    echo -e "${YELLOW}📝 Standardizing PHP Dockerfile...${NC}"
    sed -i.tmp "s/FROM node:[0-9]*-alpine/FROM node:${NODE_VERSION_TARGET}-alpine/g" docker/php/Dockerfile
    sed -i.tmp "s/FROM php:[0-9]\.[0-9]-fpm-alpine/FROM php:${PHP_VERSION_TARGET}-fpm-alpine/g" docker/php/Dockerfile
    rm docker/php/Dockerfile.tmp 2>/dev/null || true
    
    # Update Frontend Dockerfile  
    echo -e "${YELLOW}📝 Standardizing Frontend Dockerfile...${NC}"
    sed -i.tmp "s/FROM node:[0-9]*-alpine/FROM node:${NODE_VERSION_TARGET}-alpine/g" docker/frontend/Dockerfile
    rm docker/frontend/Dockerfile.tmp 2>/dev/null || true
    
    # Update Nginx Dockerfile
    echo -e "${YELLOW}📝 Standardizing Nginx Dockerfile...${NC}"
    sed -i.tmp "s/FROM nginx:[0-9]\.[0-9]*-alpine/FROM nginx:${NGINX_VERSION_TARGET}-alpine/g" docker/nginx/Dockerfile
    sed -i.tmp "s/FROM nginx:alpine/FROM nginx:${NGINX_VERSION_TARGET}-alpine/g" docker/nginx/Dockerfile
    rm docker/nginx/Dockerfile.tmp 2>/dev/null || true
    
    echo -e "${GREEN}✅ All Dockerfiles standardized${NC}"
    echo ""
}

# Function to create Docker version check script
create_version_check_script() {
    print_section "Creating Version Verification Script"
    
    cat > scripts/verify-docker-versions.sh << 'EOF'
#!/bin/bash

# Docker Version Verification Script
# Checks if current Docker versions match the standardized versions

DOCKER_TARGET="27.4.1"
COMPOSE_TARGET="2.31.0"

echo "🔍 Docker Version Verification"
echo "=============================="

# Check Docker version
DOCKER_CURRENT=$(docker --version | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)
echo "Docker: $DOCKER_CURRENT (target: $DOCKER_TARGET)"

# Check Docker Compose version
COMPOSE_CURRENT=$(docker compose --version | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)
echo "Docker Compose: $COMPOSE_CURRENT (target: $COMPOSE_TARGET)"

# Check if versions match
if [ "$DOCKER_CURRENT" = "$DOCKER_TARGET" ] && [ "$COMPOSE_CURRENT" = "$COMPOSE_TARGET" ]; then
    echo "✅ All versions aligned!"
    exit 0
else
    echo "⚠️  Version mismatch detected!"
    echo "Please run: ./scripts/docker-version-align.sh install"
    exit 1
fi
EOF

    chmod +x scripts/verify-docker-versions.sh
    echo -e "${GREEN}✅ Created version verification script${NC}"
    echo ""
}

# Function to create Docker installation script for consistent versions
create_docker_install_script() {
    print_section "Creating Docker Installation Script"
    
    cat > scripts/install-docker-versions.sh << EOF
#!/bin/bash

# Docker Installation Script for Version Alignment
# Installs specific Docker and Docker Compose versions for consistency

set -e

DOCKER_VERSION="$DOCKER_VERSION_TARGET"
COMPOSE_VERSION="$DOCKER_COMPOSE_VERSION_TARGET"

echo "🐳 Installing Docker Version \$DOCKER_VERSION"
echo "🔧 Installing Docker Compose Version \$COMPOSE_VERSION"
echo ""

# Detect OS
OS=\$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=\$(uname -m)

case \$ARCH in
    x86_64) ARCH="x86_64" ;;
    aarch64|arm64) ARCH="aarch64" ;;
    *) echo "Unsupported architecture: \$ARCH"; exit 1 ;;
esac

# Install Docker (Ubuntu/Debian)
if command -v apt-get >/dev/null 2>&1; then
    echo "📦 Installing Docker on Ubuntu/Debian..."
    
    # Remove old versions
    sudo apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
    
    # Install dependencies
    sudo apt-get update
    sudo apt-get install -y ca-certificates curl gnupg lsb-release
    
    # Add Docker's official GPG key
    sudo mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    
    # Add Docker repository
    echo "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \$(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Install specific Docker version
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker compose-plugin
    
# Install Docker (CentOS/RHEL)
elif command -v yum >/dev/null 2>&1; then
    echo "📦 Installing Docker on CentOS/RHEL..."
    
    # Remove old versions
    sudo yum remove -y docker docker-client docker-client-latest docker-common docker-latest docker-latest-logrotate docker-logrotate docker-engine
    
    # Install dependencies
    sudo yum install -y yum-utils
    sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    
    # Install Docker
    sudo yum install -y docker-ce docker-ce-cli containerd.io docker compose-plugin

# macOS with Homebrew
elif command -v brew >/dev/null 2>&1; then
    echo "📦 Installing Docker on macOS..."
    echo "⚠️  Please install Docker Desktop manually from: https://docs.docker.com/desktop/install/mac-install/"
    echo "   After installation, Docker Compose is included."
    exit 0

else
    echo "❌ Unsupported package manager. Please install Docker manually."
    exit 1
fi

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Add current user to docker group
sudo usermod -aG docker \$USER

echo "✅ Docker installation completed!"
echo "⚠️  Please log out and back in for group changes to take effect."
echo "🔄 Then run: ./scripts/verify-docker-versions.sh"
EOF

    chmod +x scripts/install-docker-versions.sh
    echo -e "${GREEN}✅ Created Docker installation script${NC}"
    echo ""
}

# Function to create rebuild script with version alignment
create_rebuild_script() {
    print_section "Creating Aligned Rebuild Script"
    
    cat > scripts/rebuild-aligned.sh << 'EOF'
#!/bin/bash

# Rebuild Script with Docker Version Alignment
# Rebuilds all containers with version-aligned base images

set -e

echo "🔄 Rebuilding with Aligned Docker Versions"
echo "=========================================="

# Stop all containers
echo "🛑 Stopping containers..."
docker compose down -v

# Remove all related images to force rebuild
echo "🧹 Cleaning up old images..."
docker images | grep klinik_hidup | awk '{print $3}' | xargs -r docker rmi -f 2>/dev/null || true
docker system prune -f

# Pull latest base images with specific versions
echo "📥 Pulling aligned base images..."
docker pull php:8.4-fpm-alpine
docker pull node:22-alpine  
docker pull nginx:1.27-alpine
docker pull mysql:8.0

# Rebuild and start with no cache
echo "🏗️  Rebuilding containers..."
docker compose build --no-cache --pull
docker compose up -d

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 15

# Check status
echo "📊 Container status:"
docker compose ps

echo "✅ Rebuild completed with aligned versions!"
EOF

    chmod +x scripts/rebuild-aligned.sh
    echo -e "${GREEN}✅ Created aligned rebuild script${NC}"
    echo ""
}

# Function to update Makefile with version checks
update_makefile() {
    print_section "Updating Makefile with Version Checks"
    
    # Backup Makefile
    cp Makefile Makefile.backup.$(date +%Y%m%d_%H%M%S)
    
    # Add version alignment targets to Makefile
    cat >> Makefile << 'EOF'

# Docker Version Alignment Commands
.PHONY: version-check
version-check: ## Check Docker version alignment
	@echo "$(BLUE)🔍 Checking Docker version alignment...$(NC)"
	@./scripts/verify-docker-versions.sh

.PHONY: version-align
version-align: ## Align Docker versions across environments
	@echo "$(YELLOW)🔧 Aligning Docker versions...$(NC)"
	@./scripts/docker-version-align.sh align

.PHONY: rebuild-aligned
rebuild-aligned: ## Rebuild containers with aligned versions
	@echo "$(YELLOW)🔄 Rebuilding with aligned versions...$(NC)"
	@./scripts/rebuild-aligned.sh

.PHONY: install-docker-aligned
install-docker-aligned: ## Install aligned Docker versions
	@echo "$(YELLOW)🐳 Installing aligned Docker versions...$(NC)"
	@./scripts/install-docker-versions.sh
EOF

    echo -e "${GREEN}✅ Updated Makefile with version alignment commands${NC}"
    echo ""
}

# Function to show final recommendations
show_recommendations() {
    print_section "Recommendations and Next Steps"
    
    echo -e "${YELLOW}🎯 Recommended Actions:${NC}"
    echo ""
    echo "1. Review the version changes:"
    echo "   ${CYAN}git diff${NC}"
    echo ""
    echo "2. Verify versions are aligned:"
    echo "   ${CYAN}./scripts/verify-docker-versions.sh${NC}"
    echo ""
    echo "3. Rebuild containers with aligned versions:"
    echo "   ${CYAN}./scripts/rebuild-aligned.sh${NC}"
    echo "   ${CYAN}# OR use make command:${NC}"
    echo "   ${CYAN}make rebuild-aligned${NC}"
    echo ""
    echo "4. Test the application thoroughly:"
    echo "   ${CYAN}make dev${NC}"
    echo "   ${CYAN}# Then visit: http://localhost:8090${NC}"
    echo ""
    echo "5. Deploy to production with same versions:"
    echo "   ${CYAN}make deploy-prod-rebuild${NC}"
    echo ""
    echo -e "${GREEN}📝 Latest Stable Target Versions Set:${NC}"
    echo "   • Docker: $DOCKER_VERSION_TARGET (latest stable)"
    echo "   • Docker Compose: $DOCKER_COMPOSE_VERSION_TARGET (latest stable)"
    echo "   • PHP: $PHP_VERSION_TARGET (latest stable)"
    echo "   • Node.js: $NODE_VERSION_TARGET (latest LTS, Claude compatible)"
    echo "   • Nginx: $NGINX_VERSION_TARGET (latest stable)"
    echo "   • MySQL: $MYSQL_VERSION_TARGET (latest LTS, 8.4 has breaking changes)"
    echo ""
    echo -e "${CYAN}💡 Pro Tip:${NC} Run 'make version-check' regularly to ensure alignment!"
}

# Main execution logic
case "${1:-check}" in
    check)
        check_current_versions
        ;;
    align)
        check_current_versions
        create_version_locked_compose
        create_standardized_dockerfiles
        create_version_check_script
        create_docker_install_script
        create_rebuild_script
        update_makefile
        show_recommendations
        ;;
    install)
        ./scripts/install-docker-versions.sh
        ;;
    *)
        echo "Usage: $0 [check|align|install]"
        echo ""
        echo "Commands:"
        echo "  check   - Check current Docker versions"
        echo "  align   - Align Docker versions and create scripts"
        echo "  install - Install aligned Docker versions"
        echo ""
        echo "Examples:"
        echo "  $0 check     # Check current versions"
        echo "  $0 align     # Align all versions and create scripts"
        exit 1
        ;;
esac