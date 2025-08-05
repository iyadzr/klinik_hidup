#!/bin/bash

# Rebuild Script with Docker Version Alignment
# Rebuilds all containers with version-aligned base images

set -e

echo "🔄 Rebuilding with Aligned Docker Versions"
echo "=========================================="

# Stop all containers
echo "🛑 Stopping containers..."
docker-compose down -v

# Remove all related images to force rebuild
echo "🧹 Cleaning up old images..."
docker images | grep klinik_hidup | awk '{print $3}' | xargs -r docker rmi -f 2>/dev/null || true
docker system prune -f

# Pull latest base images with specific versions
echo "📥 Pulling aligned base images..."
docker pull php:8.3-fpm-alpine
docker pull node:20-alpine  
docker pull nginx:1.25-alpine
docker pull mysql:8.0

# Rebuild and start with no cache
echo "🏗️  Rebuilding containers..."
docker-compose build --no-cache --pull
docker-compose up -d

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 15

# Check status
echo "📊 Container status:"
docker-compose ps

echo "✅ Rebuild completed with aligned versions!"
