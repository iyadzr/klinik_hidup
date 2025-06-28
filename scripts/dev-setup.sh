#!/bin/bash

# Development setup script for Clinic Management System
# This script helps manage the Docker development environment

set -e

echo "🏥 Clinic Management System - Development Setup"
echo "================================================"

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo "❌ Docker is not running. Please start Docker and try again."
        exit 1
    fi
    echo "✅ Docker is running"
}

# Function to build and start services
start_services() {
    echo "🚀 Starting development services..."
    
    # Build and start all services
    docker-compose up -d --build
    
    echo "⏳ Waiting for services to be ready..."
    sleep 10
    
    # Check if services are running
    if docker-compose ps | grep -q "Up"; then
        echo "✅ All services are running!"
        echo ""
        echo "🌐 Access your application:"
        echo "   Frontend (Vue.js): http://localhost:8090"
        echo "   Backend API: http://localhost:8090/api"
        echo "   Database: localhost:3307"
        echo ""
        echo "📊 Service Status:"
        docker-compose ps
    else
        echo "❌ Some services failed to start. Check logs with: docker-compose logs"
        exit 1
    fi
}

# Function to stop services
stop_services() {
    echo "🛑 Stopping development services..."
    docker-compose down
    echo "✅ Services stopped"
}

# Function to restart services
restart_services() {
    echo "🔄 Restarting development services..."
    docker-compose down
    docker-compose up -d --build
    echo "✅ Services restarted"
}

# Function to view logs
view_logs() {
    echo "📋 Viewing logs..."
    docker-compose logs -f
}

# Function to install dependencies
install_deps() {
    echo "📦 Installing dependencies..."
    
    # Install PHP dependencies
    echo "Installing PHP dependencies..."
    docker-compose exec php composer install
    
    # Install Node.js dependencies
    echo "Installing Node.js dependencies..."
    docker-compose exec node npm install
    
    echo "✅ Dependencies installed"
}

# Function to run database migrations
run_migrations() {
    echo "🗄️ Running database migrations..."
    docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
    echo "✅ Migrations completed"
}

# Function to seed database
seed_database() {
    echo "🌱 Seeding database..."
    docker-compose exec php bin/console doctrine:fixtures:load --no-interaction
    echo "✅ Database seeded"
}

# Function to show help
show_help() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  start     - Start all development services"
    echo "  stop      - Stop all development services"
    echo "  restart   - Restart all development services"
    echo "  logs      - View service logs"
    echo "  install   - Install dependencies"
    echo "  migrate   - Run database migrations"
    echo "  seed      - Seed the database"
    echo "  help      - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start    # Start the development environment"
    echo "  $0 logs     # View logs from all services"
}

# Main script logic
case "${1:-start}" in
    start)
        check_docker
        start_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        check_docker
        restart_services
        ;;
    logs)
        view_logs
        ;;
    install)
        check_docker
        install_deps
        ;;
    migrate)
        check_docker
        run_migrations
        ;;
    seed)
        check_docker
        seed_database
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo "❌ Unknown command: $1"
        echo ""
        show_help
        exit 1
        ;;
esac 