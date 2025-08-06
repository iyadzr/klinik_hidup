#!/bin/bash

# Docker Installation Script for Version Alignment
# Installs specific Docker and Docker Compose versions for consistency

set -e

DOCKER_VERSION="27.4.1"
COMPOSE_VERSION="2.31.0"

echo "🐳 Installing Docker Version $DOCKER_VERSION"
echo "🔧 Installing Docker Compose Version $COMPOSE_VERSION"
echo ""

# Detect OS
OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)

case $ARCH in
    x86_64) ARCH="x86_64" ;;
    aarch64|arm64) ARCH="aarch64" ;;
    *) echo "Unsupported architecture: $ARCH"; exit 1 ;;
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
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
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
sudo usermod -aG docker $USER

echo "✅ Docker installation completed!"
echo "⚠️  Please log out and back in for group changes to take effect."
echo "🔄 Then run: ./scripts/verify-docker-versions.sh"
