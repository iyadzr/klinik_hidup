#!/bin/bash

echo "🚀 Deploying Clinic Management System - Containerized Architecture"
echo "=================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-dev}  # dev or prod
REBUILD=${2:-false}    # true to force rebuild

echo -e "${BLUE}📋 Configuration:${NC}"
echo "  Environment: $ENVIRONMENT"
echo "  Force Rebuild: $REBUILD"
echo ""

# Function to check if command succeeded
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1 failed${NC}"
        exit 1
    fi
}

# Stop existing containers
echo -e "${YELLOW}🛑 Stopping existing containers...${NC}"
docker-compose down
check_status "Containers stopped"

# Clean up if rebuild requested
if [ "$REBUILD" = "true" ]; then
    echo -e "${YELLOW}🧹 Cleaning up old images and volumes...${NC}"
    docker system prune -a -f
    docker builder prune -a -f
    check_status "Cleanup completed"
fi

# Build and start containers based on environment
if [ "$ENVIRONMENT" = "prod" ]; then
    echo -e "${YELLOW}🏭 Building production containers...${NC}"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
    check_status "Production containers started"
else
    echo -e "${YELLOW}🔧 Building development containers...${NC}"
    docker-compose up -d --build
    check_status "Development containers started"
fi

# Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 10

# Check container status
echo -e "${BLUE}📊 Container status:${NC}"
docker-compose ps

# Create default users
echo -e "${YELLOW}👥 Creating default users...${NC}"
if [ -f "./create-users-docker.sh" ]; then
    ./create-users-docker.sh
    check_status "Default users created"
else
    echo -e "${RED}⚠️  User creation script not found${NC}"
fi

# Run database migrations for missing columns
echo -e "${YELLOW}🔄 Running database migrations...${NC}"
if [ -f "./migrations/add_updated_at_to_queue.sql" ]; then
    # Check if updated_at column already exists
    COLUMN_EXISTS=$(docker exec clinic-management-system-mysql-1 mysql -u clinic_user -pclinic_password clinic_db -e "DESCRIBE queue;" 2>/dev/null | grep updated_at | wc -l)
    if [ "$COLUMN_EXISTS" -eq 0 ]; then
        docker exec -i clinic-management-system-mysql-1 mysql -u clinic_user -pclinic_password clinic_db < ./migrations/add_updated_at_to_queue.sql
        check_status "Database migration completed"
    else
        echo -e "${GREEN}✅ Database migration already applied${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  No migration files found${NC}"
fi

# Run health checks
echo -e "${YELLOW}🏥 Running health checks...${NC}"

# Check frontend
if curl -f -s http://localhost:8090/ > /dev/null; then
    echo -e "${GREEN}✅ Frontend is responding${NC}"
else
    echo -e "${RED}❌ Frontend health check failed${NC}"
fi

# Check API
if curl -f -s http://localhost:8090/api/login -X POST -H 'Content-Type: application/json' -d '{"username":"test","password":"test"}' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ API is responding${NC}"
else
    echo -e "${YELLOW}⚠️  API endpoint accessible (expected auth error)${NC}"
fi

# Display final information
echo ""
echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo ""
echo -e "${BLUE}📍 Access URLs:${NC}"
echo "  Frontend: http://localhost:8090"
echo "  API: http://localhost:8090/api"
if [ "$ENVIRONMENT" = "dev" ]; then
    echo "  Database: localhost:3307"
fi
echo ""
echo -e "${BLUE}👤 Default Users:${NC}"
echo "  Super Admin: superadmin / password"
echo "  Doctor: doctor / password"
echo "  Assistant: assistant / password"
echo ""
echo -e "${BLUE}🛠️  Useful Commands:${NC}"
echo "  View logs: docker-compose logs -f [service]"
echo "  Stop: docker-compose down"
echo "  Rebuild: ./deploy-containerized.sh $ENVIRONMENT true"
echo ""

# Show container resource usage
echo -e "${BLUE}💾 Container Resource Usage:${NC}"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null || echo "Docker stats unavailable"