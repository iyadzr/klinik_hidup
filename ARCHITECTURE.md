# Clinic Management System - Container Architecture

## 🏗️ Architecture Overview

This application follows a **microservices architecture** with separated frontend and backend containers:

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │     Backend      │    │    Database     │
│   (Vue.js)      │    │   (PHP/Symfony)  │    │    (MySQL)      │
│   Port: 8090    │◄──►│   Port: 9000     │◄──►│   Port: 3306    │
│   nginx + SPA   │    │   PHP-FPM        │    │   MySQL 8.0     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 📁 Container Structure

### Frontend Container (`frontend`)
- **Base**: `node:18-alpine` + `nginx:alpine`
- **Purpose**: Serves Vue.js SPA and proxies API calls
- **Port**: 8090 (external)
- **Features**:
  - Multi-stage build (build assets → serve with nginx)
  - API proxy to backend container
  - SPA routing support
  - Static asset caching
  - CORS handling

### Backend Container (`app`)
- **Base**: `php:8.3-fpm`
- **Purpose**: Symfony API server
- **Port**: 9000 (internal only)
- **Features**:
  - PHP-FPM for handling API requests
  - Doctrine ORM for database
  - JWT authentication
  - REST API endpoints

### Database Container (`mysql`)
- **Base**: `mysql:8.0`
- **Purpose**: Data persistence
- **Port**: 3307 (external in dev), 3306 (internal)
- **Features**:
  - Optimized MySQL configuration
  - Health checks
  - Timezone support (Asia/Kuala_Lumpur)

## 🚀 Deployment Modes

### Development Mode
```bash
./deploy-containerized.sh dev
```
- Hot reloading for frontend and backend
- Database exposed on port 3307
- Debug mode enabled
- Bind mounts for live development

### Production Mode
```bash
./deploy-containerized.sh prod
```
- Optimized builds
- No external database access
- Production MySQL configuration
- Container-only volumes (no bind mounts)

## 🌐 API Communication

**Frontend → Backend Communication:**
1. Frontend makes API calls to `/api/*`
2. Nginx proxy forwards to `http://app:9000/api/*`
3. Backend processes request and returns JSON
4. Frontend receives response through proxy

**Benefits:**
- **Separation of Concerns**: Frontend and backend are independent
- **Scalability**: Can scale frontend and backend separately
- **Security**: Backend is not directly exposed
- **Development**: Independent development workflows
- **Caching**: Frontend can cache static assets aggressively

## 📋 Container Commands

### Basic Operations
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f [frontend|app|mysql]

# Stop all services
docker-compose down

# Rebuild specific service
docker-compose build --no-cache frontend
```

### Development Tools
```bash
# Access backend container
docker exec -it clinic-management-system-app-1 bash

# Access frontend container
docker exec -it clinic-management-system-frontend-1 sh

# Database access
docker exec -it klinik_hidup-mysql-1 mysql -u clinic_user -p clinic_db
```

### Production Deployment
```bash
# Deploy production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Clean rebuild
./deploy-containerized.sh prod true
```

## 🔧 Configuration Files

- `docker-compose.yml` - Base configuration
- `docker-compose.override.yml` - Development overrides (auto-loaded)
- `docker-compose.prod.yml` - Production overrides
- `docker/frontend/Dockerfile` - Frontend container build
- `docker/frontend/nginx.conf` - Frontend nginx configuration
- `docker/php/Dockerfile` - Backend container build

## 🌟 Advantages of This Architecture

1. **Independent Scaling**: Scale frontend and backend separately
2. **Technology Flexibility**: Frontend and backend can use different tech stacks
3. **Development Efficiency**: Teams can work independently
4. **Deployment Flexibility**: Deploy components independently
5. **Resource Optimization**: Optimize containers for their specific roles
6. **Security**: Backend is not directly exposed to external network
7. **Caching**: Better caching strategies for static vs dynamic content
8. **Monitoring**: Monitor each service independently

## 🔄 Migration from Monolithic

**Before**: Single container serving both frontend and backend
**After**: Separated containers with nginx proxy

**Migration Steps**:
1. ✅ Created frontend container with Vue.js build
2. ✅ Updated backend to API-only mode
3. ✅ Added nginx proxy configuration
4. ✅ Updated deployment scripts
5. ✅ Created environment-specific configurations