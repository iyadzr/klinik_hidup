# Makefile for Clinic Management System
# Standardizes Docker Compose operations to reduce errors

# Variables
DOCKER_COMPOSE = docker-compose
DOCKER_COMPOSE_PROD = docker-compose -f docker-compose.yml -f docker-compose.prod.yml
DOCKER_COMPOSE_DEV = docker-compose
PROJECT_NAME = clinic-management-system
MYSQL_CONTAINER = $(PROJECT_NAME)-mysql-1

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[1;33m
BLUE = \033[0;34m
RED = \033[0;31m
NC = \033[0m # No Color

.PHONY: help
help: ## Show this help message
	@echo "$(BLUE)Clinic Management System - Make Commands$(NC)"
	@echo "================================================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

.PHONY: status
status: ## Show status of all containers
	@echo "$(BLUE)📊 Container Status:$(NC)"
	@$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Show logs for all services
	@$(DOCKER_COMPOSE) logs -f

.PHONY: logs-app
logs-app: ## Show logs for app service only
	@$(DOCKER_COMPOSE) logs -f app

.PHONY: logs-nginx
logs-nginx: ## Show logs for nginx service only
	@$(DOCKER_COMPOSE) logs -f nginx

.PHONY: logs-mysql
logs-mysql: ## Show logs for mysql service only
	@$(DOCKER_COMPOSE) logs -f mysql

.PHONY: logs-frontend
logs-frontend: ## Show logs for frontend service only
	@$(DOCKER_COMPOSE) logs -f frontend

# Development Commands
.PHONY: dev
dev: ## Start development environment
	@echo "$(YELLOW)🔧 Starting development environment...$(NC)"
	@$(DOCKER_COMPOSE_DEV) up -d
	@echo "$(GREEN)✅ Development environment started$(NC)"
	@$(MAKE) status

.PHONY: dev-build
dev-build: ## Build and start development environment
	@echo "$(YELLOW)🔧 Building and starting development environment...$(NC)"
	@$(DOCKER_COMPOSE_DEV) up -d --build
	@echo "$(GREEN)✅ Development environment built and started$(NC)"
	@$(MAKE) status

.PHONY: dev-rebuild
dev-rebuild: clean dev-build ## Clean and rebuild development environment
	@echo "$(GREEN)✅ Development environment rebuilt$(NC)"

# Production Commands
.PHONY: prod
prod: ## Start production environment
	@echo "$(YELLOW)🏭 Starting production environment...$(NC)"
	@$(DOCKER_COMPOSE_PROD) up -d
	@echo "$(GREEN)✅ Production environment started$(NC)"
	@$(MAKE) status

.PHONY: prod-build
prod-build: ## Build and start production environment
	@echo "$(YELLOW)🏭 Building and starting production environment...$(NC)"
	@$(DOCKER_COMPOSE_PROD) up -d --build
	@echo "$(GREEN)✅ Production environment built and started$(NC)"
	@$(MAKE) status

.PHONY: prod-rebuild
prod-rebuild: clean prod-build ## Clean and rebuild production environment
	@echo "$(GREEN)✅ Production environment rebuilt$(NC)"

# Deploy Commands (using existing scripts)
.PHONY: deploy-dev
deploy-dev: ## Deploy development environment with full setup
	@echo "$(YELLOW)🚀 Deploying development with full setup...$(NC)"
	@./deploy-containerized.sh dev

.PHONY: deploy-prod
deploy-prod: ## Deploy production environment with full setup
	@echo "$(YELLOW)🚀 Deploying production with full setup...$(NC)"
	@./deploy-containerized.sh prod

.PHONY: deploy-dev-rebuild
deploy-dev-rebuild: ## Deploy development with forced rebuild
	@echo "$(YELLOW)🚀 Deploying development with rebuild...$(NC)"
	@./deploy-containerized.sh dev true

.PHONY: deploy-prod-rebuild
deploy-prod-rebuild: ## Deploy production with forced rebuild
	@echo "$(YELLOW)🚀 Deploying production with rebuild...$(NC)"
	@./deploy-containerized.sh prod true

# Container Management
.PHONY: stop
stop: ## Stop all containers
	@echo "$(YELLOW)🛑 Stopping all containers...$(NC)"
	@$(DOCKER_COMPOSE) down
	@echo "$(GREEN)✅ All containers stopped$(NC)"

.PHONY: restart
restart: stop dev ## Restart development environment
	@echo "$(GREEN)✅ Development environment restarted$(NC)"

.PHONY: restart-prod
restart-prod: stop prod ## Restart production environment
	@echo "$(GREEN)✅ Production environment restarted$(NC)"

# Cleanup Commands
.PHONY: clean
clean: ## Stop containers and remove volumes
	@echo "$(YELLOW)🧹 Cleaning up containers and volumes...$(NC)"
	@$(DOCKER_COMPOSE) down -v
	@echo "$(GREEN)✅ Cleanup completed$(NC)"

.PHONY: deep-clean
deep-clean: ## Deep clean - remove everything including images
	@echo "$(RED)🗑️  Performing deep clean (removes images)...$(NC)"
	@$(DOCKER_COMPOSE) down -v --rmi all
	@docker system prune -a -f
	@docker builder prune -a -f
	@echo "$(GREEN)✅ Deep clean completed$(NC)"

# Database Commands
.PHONY: db-connect
db-connect: ## Connect to database container
	@echo "$(BLUE)🗄️  Connecting to database...$(NC)"
	@docker exec -it $(MYSQL_CONTAINER) mysql -u clinic_user -pclinic_password clinic_db

.PHONY: db-backup
db-backup: ## Create database backup
	@echo "$(YELLOW)💾 Creating database backup...$(NC)"
	@docker exec $(MYSQL_CONTAINER) mysqldump -u clinic_user -pclinic_password clinic_db > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Database backup created$(NC)"

.PHONY: db-restore
db-restore: ## Restore database from backup (specify BACKUP_FILE=filename.sql)
	@if [ -z "$(BACKUP_FILE)" ]; then \
		echo "$(RED)❌ Please specify BACKUP_FILE=filename.sql$(NC)"; \
		exit 1; \
	fi
	@echo "$(YELLOW)🔄 Restoring database from $(BACKUP_FILE)...$(NC)"
	@docker exec -i $(MYSQL_CONTAINER) mysql -u clinic_user -pclinic_password clinic_db < $(BACKUP_FILE)
	@echo "$(GREEN)✅ Database restored$(NC)"

.PHONY: db-migrate
db-migrate: ## Run database migrations
	@echo "$(YELLOW)🔄 Running database migrations...$(NC)"
	@if [ -f "./migrations/add_updated_at_to_queue.sql" ]; then \
		docker exec -i $(MYSQL_CONTAINER) mysql -u clinic_user -pclinic_password clinic_db < ./migrations/add_updated_at_to_queue.sql; \
		echo "$(GREEN)✅ Database migrations completed$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  No migration files found$(NC)"; \
	fi

.PHONY: create-users
create-users: ## Create default users
	@echo "$(YELLOW)👥 Creating default users...$(NC)"
	@if [ -f "./create-users-docker.sh" ]; then \
		./create-users-docker.sh; \
		echo "$(GREEN)✅ Default users created$(NC)"; \
	else \
		echo "$(RED)❌ User creation script not found$(NC)"; \
	fi

# Frontend Commands
.PHONY: npm-install
npm-install: ## Install npm dependencies
	@echo "$(YELLOW)📦 Installing npm dependencies...$(NC)"
	@npm install
	@echo "$(GREEN)✅ NPM dependencies installed$(NC)"

.PHONY: npm-dev
npm-dev: ## Run npm development server
	@echo "$(YELLOW)🔧 Starting npm development server...$(NC)"
	@npm run dev-server

.PHONY: npm-build
npm-build: ## Build frontend assets for production
	@echo "$(YELLOW)🏗️  Building frontend assets...$(NC)"
	@npm run build
	@echo "$(GREEN)✅ Frontend assets built$(NC)"

.PHONY: npm-watch
npm-watch: ## Watch frontend assets for changes
	@echo "$(YELLOW)👀 Watching frontend assets...$(NC)"
	@npm run watch

.PHONY: npm-test
npm-test: ## Run frontend tests
	@echo "$(YELLOW)🧪 Running frontend tests...$(NC)"
	@npm run test:unit

# Health Check Commands
.PHONY: health
health: ## Run health checks on services
	@echo "$(BLUE)🏥 Running health checks...$(NC)"
	@echo "$(YELLOW)Checking Frontend...$(NC)"
	@if curl -f -s http://localhost:8090/ > /dev/null; then \
		echo "$(GREEN)✅ Frontend is responding$(NC)"; \
	else \
		echo "$(RED)❌ Frontend health check failed$(NC)"; \
	fi
	@echo "$(YELLOW)Checking API...$(NC)"
	@if curl -f -s http://localhost:8090/api/login -X POST -H 'Content-Type: application/json' -d '{"username":"test","password":"test"}' > /dev/null 2>&1; then \
		echo "$(GREEN)✅ API is responding$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  API endpoint accessible (expected auth error)$(NC)"; \
	fi

.PHONY: stats
stats: ## Show container resource usage
	@echo "$(BLUE)💾 Container Resource Usage:$(NC)"
	@docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null || echo "$(RED)Docker stats unavailable$(NC)"

# Development Helpers
.PHONY: shell-app
shell-app: ## Access app container shell
	@echo "$(BLUE)🐚 Accessing app container shell...$(NC)"
	@docker exec -it $(PROJECT_NAME)-app-1 /bin/bash

.PHONY: shell-mysql
shell-mysql: ## Access mysql container shell
	@echo "$(BLUE)🐚 Accessing mysql container shell...$(NC)"
	@docker exec -it $(MYSQL_CONTAINER) /bin/bash

.PHONY: shell-nginx
shell-nginx: ## Access nginx container shell
	@echo "$(BLUE)🐚 Accessing nginx container shell...$(NC)"
	@docker exec -it $(PROJECT_NAME)-nginx-1 /bin/sh

# Quick Commands
.PHONY: up
up: dev ## Alias for dev command

.PHONY: down
down: stop ## Alias for stop command

.PHONY: build
build: dev-build ## Alias for dev-build command

.PHONY: rebuild
rebuild: dev-rebuild ## Alias for dev-rebuild command

# Info Commands
.PHONY: urls
urls: ## Show access URLs
	@echo "$(BLUE)📍 Access URLs:$(NC)"
	@echo "  Frontend: http://localhost:8090"
	@echo "  API: http://localhost:8090/api"
	@echo "  Database: localhost:3307"
	@echo ""
	@echo "$(BLUE)👤 Default Users:$(NC)"
	@echo "  Super Admin: superadmin / password"
	@echo "  Doctor: doctor / password"  
	@echo "  Assistant: assistant / password"

.PHONY: debug
debug: ## Show debug information
	@echo "$(BLUE)🔍 Debug Information:$(NC)"
	@echo "Docker version:"
	@docker --version
	@echo ""
	@echo "Docker Compose version:"
	@docker-compose --version
	@echo ""
	@echo "Container status:"
	@$(MAKE) status
	@echo ""
	@echo "Network information:"
	@docker network ls | grep clinic || echo "No clinic networks found"

# Default target
.DEFAULT_GOAL := help