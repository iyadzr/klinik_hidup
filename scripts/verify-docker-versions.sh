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
COMPOSE_CURRENT=$(docker-compose --version | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)
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
