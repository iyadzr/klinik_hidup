# Docker Development Setup

This document explains how to set up and run the Clinic Management System using Docker for development.

## Architecture Overview

The application uses a **monorepo** structure with separate Docker containers for different services:

- **PHP Container**: Symfony backend with PHP-FPM
- **Node Container**: Vue.js frontend development server
- **Nginx Container**: Reverse proxy and static file server
- **MySQL Container**: Database

## Quick Start

### Prerequisites

- Docker and Docker Compose installed
- Git

### 1. Clone and Setup

```bash
git clone <repository-url>
cd clinic-management-system
```

### 2. Start Development Environment

```bash
# Make the setup script executable (first time only)
chmod +x scripts/dev-setup.sh

# Start all services
./scripts/dev-setup.sh start
```

### 3. Access the Application

- **Frontend**: http://localhost:8090
- **Backend API**: http://localhost:8090/api
- **Database**: localhost:3307

## Development Scripts

The `scripts/dev-setup.sh` script provides several commands:

```bash
# Start all services
./scripts/dev-setup.sh start

# Stop all services
./scripts/dev-setup.sh stop

# Restart all services
./scripts/dev-setup.sh restart

# View logs
./scripts/dev-setup.sh logs

# Install dependencies
./scripts/dev-setup.sh install

# Run database migrations
./scripts/dev-setup.sh migrate

# Seed the database
./scripts/dev-setup.sh seed

# Show help
./scripts/dev-setup.sh help
```

## Manual Docker Commands

If you prefer to use Docker commands directly:

```bash
# Start all services
docker-compose up -d

# Build and start (if you made changes to Dockerfiles)
docker-compose up -d --build

# View logs
docker-compose logs -f

# Stop all services
docker-compose down

# Access PHP container
docker-compose exec php bash

# Access Node container
docker-compose exec node sh

# Run Symfony commands
docker-compose exec php bin/console cache:clear

# Run npm commands
docker-compose exec node npm run dev
```

## Service Details

### PHP Container (Backend)
- **Image**: Custom PHP 8.2 with FPM
- **Port**: 9000 (internal)
- **Volume**: `.:/var/www/html`
- **Purpose**: Symfony application server

### Node Container (Frontend)
- **Image**: Node.js 18 Alpine
- **Port**: 8080 (internal)
- **Volume**: `.:/app`
- **Purpose**: Vue.js development server with hot reload

### Nginx Container (Proxy)
- **Image**: Nginx Alpine
- **Port**: 8090 (external)
- **Purpose**: Reverse proxy, serves static files, routes API requests

### MySQL Container (Database)
- **Image**: MySQL 8.0
- **Port**: 3307 (external)
- **Database**: clinic_db
- **User**: clinic_user
- **Password**: clinic_password

## Development Workflow

### Frontend Development (Vue.js)

1. The Node container runs `npm run dev-server` which starts the Vue.js development server
2. Nginx proxies frontend requests to the Node container
3. Hot reloading is enabled for instant feedback
4. Changes to Vue components are reflected immediately

### Backend Development (Symfony)

1. The PHP container runs the Symfony application
2. API requests (`/api/*`) are routed to the PHP container
3. Static assets (`/build/*`) are served by Nginx
4. Changes to PHP code are reflected after cache clearing

### Database Development

1. MySQL runs in a separate container
2. Database is accessible on port 3307
3. Initial setup script runs automatically
4. Use migrations for schema changes

## File Structure

```
clinic-management-system/
├── assets/                 # Vue.js frontend source
│   ├── js/
│   │   ├── components/     # Vue components
│   │   ├── views/          # Vue pages
│   │   ├── stores/         # Pinia stores
│   │   └── services/       # API services
│   └── styles/             # SCSS styles
├── src/                    # Symfony backend source
│   ├── Controller/         # API controllers
│   ├── Entity/             # Database entities
│   ├── Repository/         # Data repositories
│   └── Service/            # Business logic
├── public/                 # Web root
│   └── build/              # Compiled assets
├── docker/                 # Docker configuration
│   ├── nginx/              # Nginx configs
│   └── php/                # PHP Dockerfile
└── scripts/                # Development scripts
```

## Troubleshooting

### Services Won't Start

```bash
# Check if Docker is running
docker info

# Check service status
docker-compose ps

# View detailed logs
docker-compose logs [service-name]
```

### Frontend Not Loading

```bash
# Check Node container logs
docker-compose logs node

# Restart Node container
docker-compose restart node

# Check if dev server is running
docker-compose exec node ps aux
```

### Backend API Issues

```bash
# Clear Symfony cache
docker-compose exec php bin/console cache:clear

# Check PHP logs
docker-compose logs php

# Verify database connection
docker-compose exec php bin/console doctrine:query:sql "SELECT 1"
```

### Database Issues

```bash
# Check MySQL container
docker-compose logs mysql

# Access MySQL directly
docker-compose exec mysql mysql -u clinic_user -p clinic_db

# Reset database (WARNING: destroys data)
docker-compose down -v
docker-compose up -d
```

## Production Setup

For production deployment, see `PRODUCTION_SETUP.md` for a different configuration that:

- Uses production-optimized images
- Implements proper SSL/TLS
- Includes security headers
- Optimizes for performance
- Uses separate build and runtime stages

## Environment Variables

Key environment variables:

- `APP_ENV`: Set to `dev` for development
- `DATABASE_URL`: MySQL connection string
- `NODE_ENV`: Set to `development` for hot reloading

## Contributing

When contributing to this project:

1. Use the development setup for local development
2. Ensure all tests pass before submitting PRs
3. Follow the existing code structure
4. Update documentation if needed
5. Test both frontend and backend changes 