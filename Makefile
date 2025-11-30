# Laravel Docker Makefile - Optimized for Local & Production
# Usage: make <command> [ENV=local|prod]
.PHONY: help install up down restart logs shell test migrate fresh seed clear optimize deploy backup restore

# ============================================
# CONFIGURATION
# ============================================

# Detect environment (local by default)
ENV ?= local
COMPOSE_FILE := docker-compose.$(ENV).yml

# Detect if running in WSL (for Windows users)
IS_WSL := $(shell grep -qi microsoft /proc/version 2>/dev/null && echo 1 || echo 0)

# Colors for beautiful output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
PURPLE := \033[0;35m
CYAN := \033[0;36m
NC := \033[0m

# Docker compose command with file selection
DC := docker-compose -f $(COMPOSE_FILE)

# ============================================
# HELP & INFO
# ============================================

help: ## 📚 Show this help message
	@echo '${CYAN}╔════════════════════════════════════════════════════╗${NC}'
	@echo '${CYAN}║     Laravel Docker Commands (ENV=$(ENV))          ║${NC}'
	@echo '${CYAN}╚════════════════════════════════════════════════════╝${NC}'
	@echo ''
	@echo '${BLUE}Usage:${NC} make <command> [ENV=local|prod]'
	@echo ''
	@echo '${YELLOW}Available commands:${NC}'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${GREEN}%-25s${NC} %s\n", $$1, $$2}'
	@echo ''
	@echo '${CYAN}Examples:${NC}'
	@echo '  ${PURPLE}make up${NC}              → Start local environment'
	@echo '  ${PURPLE}make up ENV=prod${NC}     → Start production environment'
	@echo '  ${PURPLE}make logs-queue${NC}      → Follow queue worker logs'
	@echo '  ${PURPLE}make shell${NC}           → Access app container'
	@echo ''

info: ## ℹ️  Show current environment info
	@echo '${BLUE}Current Configuration:${NC}'
	@echo '  Environment: ${GREEN}$(ENV)${NC}'
	@echo '  Compose file: ${GREEN}$(COMPOSE_FILE)${NC}'
	@echo '  WSL detected: ${GREEN}$(IS_WSL)${NC}'
	@echo '  Docker: ${GREEN}'$$(docker --version)'${NC}'
	@echo '  Compose: ${GREEN}'$$(docker-compose --version)'${NC}'

# ============================================
# SETUP & INSTALLATION
# ============================================

install: ## 🔧 Install dependencies and setup project
	@echo "${BLUE}📦 Installing dependencies...${NC}"
	composer install
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "${GREEN}✓ .env file created${NC}"; \
	fi
	@if grep -q "APP_KEY=$" .env 2>/dev/null; then \
		php artisan key:generate; \
		echo "${GREEN}✓ Application key generated${NC}"; \
	fi
	@echo "${GREEN}✅ Installation complete!${NC}"

setup: install up migrate ## 🚀 Complete setup (install + up + migrate)
	@echo "${GREEN}✅ Setup complete! Access: http://localhost:8000${NC}"

# ============================================
# DOCKER CONTAINERS MANAGEMENT
# ============================================

up: ## ▶️  Start all containers
	@echo "${BLUE}🚀 Starting containers ($(ENV))...${NC}"
	$(DC) up -d
	@sleep 3
	@$(MAKE) ps
	@echo "${GREEN}✅ Containers started!${NC}"
	@echo "${CYAN}Access API: http://localhost:8000${NC}"
	@if [ "$(ENV)" = "local" ]; then \
		echo "${CYAN}pgAdmin: http://localhost:8080${NC}"; \
		echo "${CYAN}Mailhog: http://localhost:8025${NC}"; \
		echo "${CYAN}Redis Commander: http://localhost:8081${NC}"; \
	fi

up-build: ## 🔨 Start containers with rebuild
	@echo "${BLUE}🔨 Building and starting containers...${NC}"
	$(DC) up -d --build
	@$(MAKE) ps

down: ## ⏹️  Stop all containers
	@echo "${BLUE}⏹️  Stopping containers...${NC}"
	$(DC) down
	@echo "${GREEN}✅ Containers stopped!${NC}"

restart: ## 🔄 Restart all containers
	@echo "${BLUE}🔄 Restarting containers...${NC}"
	$(DC) restart
	@echo "${GREEN}✅ Containers restarted!${NC}"

restart-app: ## 🔄 Restart app container only
	$(DC) restart app

restart-queue: ## 🔄 Restart queue worker
	$(DC) restart queue

ps: ## 📊 Show container status
	@$(DC) ps

stats: ## 📈 Show container resource usage
	@docker stats --no-stream $$($(DC) ps -q)

# ============================================
# LOGS MANAGEMENT
# ============================================

logs: ## 📝 Show all container logs (follow)
	$(DC) logs -f

logs-app: ## 📝 Show app container logs
	$(DC) logs -f app

logs-queue: ## 📝 Show queue worker logs
	$(DC) logs -f queue

logs-postgres: ## 📝 Show PostgreSQL logs
	$(DC) logs -f postgres

logs-redis: ## 📝 Show Redis logs
	$(DC) logs -f redis

logs-nginx: ## 📝 Show Nginx logs (production only)
	@if [ "$(ENV)" = "prod" ]; then \
		$(DC) exec app tail -f /var/log/nginx/laravel-access.log; \
	else \
		echo "${RED}Nginx logs only available in production${NC}"; \
	fi

logs-laravel: ## 📝 Show Laravel application logs
	$(DC) exec app tail -f storage/logs/laravel.log

logs-worker: ## 📝 Show queue worker application logs
	@if [ -f storage/logs/queue-worker.log ]; then \
		$(DC) exec app tail -f storage/logs/queue-worker.log; \
	else \
		echo "${YELLOW}Queue worker log file not found yet${NC}"; \
	fi

# ============================================
# SHELL ACCESS
# ============================================

shell: ## 🐚 Access app container shell
	@echo "${BLUE}🐚 Accessing app container...${NC}"
	$(DC) exec app sh

shell-root: ## 🐚 Access app container as root
	$(DC) exec -u root app sh

shell-db: ## 🐚 Access PostgreSQL shell
	$(DC) exec postgres psql -U leyinvest -d leyinvest

shell-redis: ## 🐚 Access Redis CLI
	$(DC) exec redis redis-cli

tinker: ## 🔧 Open Laravel Tinker
	$(DC) exec app php artisan tinker

# ============================================
# DATABASE OPERATIONS
# ============================================

migrate: ## 🗄️  Run database migrations
	@echo "${BLUE}🗄️  Running migrations...${NC}"
	$(DC) exec app php artisan migrate
	@echo "${GREEN}✅ Migrations completed!${NC}"

migrate-fresh: ## ⚠️  Fresh migration (drops all tables)
	@echo "${RED}⚠️  WARNING: This will drop all tables!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		$(DC) exec app php artisan migrate:fresh; \
		echo "${GREEN}✅ Fresh migration completed!${NC}"; \
	fi

migrate-rollback: ## ↩️  Rollback last migration
	$(DC) exec app php artisan migrate:rollback

migrate-status: ## 📊 Show migration status
	$(DC) exec app php artisan migrate:status

seed: ## 🌱 Run database seeders
	@echo "${BLUE}🌱 Running seeders...${NC}"
	$(DC) exec app php artisan db:seed
	@echo "${GREEN}✅ Seeding completed!${NC}"

fresh: ## 🆕 Fresh migration with seeding
	@echo "${RED}⚠️  WARNING: This will drop all tables and reseed!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		$(DC) exec app php artisan migrate:fresh --seed; \
		echo "${GREEN}✅ Fresh setup completed!${NC}"; \
	fi

db-create-tables: ## 📦 Create cache, session, queue tables
	@echo "${BLUE}📦 Creating system tables...${NC}"
	-$(DC) exec app php artisan cache:table
	-$(DC) exec app php artisan session:table
	-$(DC) exec app php artisan queue:table
	-$(DC) exec app php artisan queue:failed-table
	$(DC) exec app php artisan migrate
	@echo "${GREEN}✅ System tables created!${NC}"

# ============================================
# QUEUE MANAGEMENT
# ============================================

queue-work: ## 🔄 Start queue worker manually
	$(DC) exec app php artisan queue:work --verbose

queue-work-once: ## 🔄 Process one job
	$(DC) exec app php artisan queue:work --once

queue-restart: ## 🔄 Restart queue workers
	@echo "${BLUE}🔄 Restarting queue workers...${NC}"
	$(DC) exec app php artisan queue:restart
	$(DC) restart queue
	@echo "${GREEN}✅ Queue workers restarted!${NC}"

queue-failed: ## ❌ List failed queue jobs
	$(DC) exec app php artisan queue:failed

queue-retry: ## 🔄 Retry failed jobs
	$(DC) exec app php artisan queue:retry all

queue-flush: ## 🗑️  Flush failed jobs
	$(DC) exec app php artisan queue:flush

queue-monitor: ## 👀 Monitor queue status
	$(DC) exec app php artisan queue:monitor redis:default

# ============================================
# CACHE & OPTIMIZATION
# ============================================

clear: ## 🧹 Clear all caches
	@echo "${BLUE}🧹 Clearing caches...${NC}"
	$(DC) exec app php artisan optimize:clear
	$(DC) exec app php artisan cache:clear
	$(DC) exec app php artisan config:clear
	$(DC) exec app php artisan route:clear
	$(DC) exec app php artisan view:clear
	@echo "${GREEN}✅ All caches cleared!${NC}"

optimize: ## ⚡ Optimize application for production
	@echo "${BLUE}⚡ Optimizing application...${NC}"
	$(DC) exec app php artisan config:cache
	$(DC) exec app php artisan route:cache
	$(DC) exec app php artisan view:cache
	$(DC) exec app php artisan event:cache
	$(DC) exec app composer dump-autoload --optimize
	@echo "${GREEN}✅ Optimization completed!${NC}"

cache-clear: ## 🧹 Clear application cache only
	$(DC) exec app php artisan cache:clear

config-clear: ## 🧹 Clear config cache
	$(DC) exec app php artisan config:clear

# ============================================
# TESTING
# ============================================

test: ## 🧪 Run tests
	@echo "${BLUE}🧪 Running tests...${NC}"
	$(DC) exec app php artisan test
	@echo "${GREEN}✅ Tests completed!${NC}"

test-coverage: ## 📊 Run tests with coverage
	@echo "${BLUE}📊 Running tests with coverage...${NC}"
	$(DC) exec app php artisan test --coverage
	@echo "${GREEN}✅ Tests with coverage completed!${NC}"

test-filter: ## 🧪 Run specific test (usage: make test-filter FILTER=TestName)
	$(DC) exec app php artisan test --filter=$(FILTER)

# ============================================
# COMPOSER & DEPENDENCIES
# ============================================

composer-install: ## 📦 Install composer dependencies
	$(DC) exec app composer install

composer-update: ## 🔄 Update composer dependencies
	@echo "${BLUE}🔄 Updating composer dependencies...${NC}"
	$(DC) exec app composer update
	@echo "${GREEN}✅ Dependencies updated!${NC}"

composer-dump: ## 🔄 Dump composer autoload
	$(DC) exec app composer dump-autoload

# ============================================
# BACKUP & RESTORE
# ============================================

backup-db: ## 💾 Backup database
	@echo "${BLUE}💾 Creating database backup...${NC}"
	@mkdir -p backups
	docker exec $$($(DC) ps -q postgres) pg_dump -U leyinvest -Fc leyinvest > backups/backup_$$(date +%Y%m%d_%H%M%S).dump
	@echo "${GREEN}✅ Database backup created in backups/!${NC}"

backup-storage: ## 💾 Backup storage files
	@echo "${BLUE}💾 Creating storage backup...${NC}"
	@mkdir -p backups
	tar -czf backups/storage_$$(date +%Y%m%d_%H%M%S).tar.gz storage/app
	@echo "${GREEN}✅ Storage backup created in backups/!${NC}"

backup: backup-db backup-storage ## 💾 Create full backup
	@echo "${GREEN}✅ Full backup completed!${NC}"

restore-db: ## 📥 Restore database (usage: make restore-db FILE=backup.dump)
	@if [ -z "$(FILE)" ]; then \
		echo "${RED}❌ ERROR: Please specify FILE=path/to/backup.dump${NC}"; \
		exit 1; \
	fi
	@echo "${BLUE}📥 Restoring database from $(FILE)...${NC}"
	docker exec -i $$($(DC) ps -q postgres) pg_restore -U leyinvest -d leyinvest --clean < $(FILE)
	@echo "${GREEN}✅ Database restored!${NC}"

# ============================================
# DOCKER MAINTENANCE
# ============================================

build: ## 🔨 Build Docker images
	@echo "${BLUE}🔨 Building Docker images...${NC}"
	$(DC) build --no-cache
	@echo "${GREEN}✅ Build completed!${NC}"

pull: ## ⬇️  Pull latest Docker images
	@echo "${BLUE}⬇️  Pulling latest images...${NC}"
	$(DC) pull
	@echo "${GREEN}✅ Pull completed!${NC}"

clean: ## 🧹 Clean unused Docker resources
	@echo "${BLUE}🧹 Cleaning Docker resources...${NC}"
	docker system prune -f
	@echo "${GREEN}✅ Cleanup completed!${NC}"

clean-all: ## ⚠️  Clean all Docker resources (including volumes)
	@echo "${RED}⚠️  WARNING: This will remove all unused Docker resources including volumes!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker system prune -a --volumes -f; \
		echo "${GREEN}✅ Deep cleanup completed!${NC}"; \
	fi

clean-volumes: ## 🗑️  Remove project volumes
	@echo "${RED}⚠️  WARNING: This will remove all project volumes (data will be lost)!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		$(DC) down -v; \
		echo "${GREEN}✅ Volumes removed!${NC}"; \
	fi

# ============================================
# HEALTH & MONITORING
# ============================================

health: ## 🏥 Check application health
	@echo "${BLUE}🏥 Checking application health...${NC}"
	@curl -s http://localhost:8000/health && echo "\n${GREEN}✅ Health check passed!${NC}" || echo "${RED}❌ Health check failed!${NC}"

ping-db: ## 🏓 Ping PostgreSQL
	@$(DC) exec postgres pg_isready -U leyinvest

ping-redis: ## 🏓 Ping Redis
	@$(DC) exec redis redis-cli ping

status: ps health ## 📊 Show complete status

# ============================================
# FILE PERMISSIONS
# ============================================

permissions: ## 🔐 Fix file permissions
	@echo "${BLUE}🔐 Fixing permissions...${NC}"
	$(DC) exec -u root app chown -R www-data:www-data storage bootstrap/cache
	$(DC) exec -u root app chmod -R 775 storage bootstrap/cache
	@echo "${GREEN}✅ Permissions fixed!${NC}"

# ============================================
# QUICK SHORTCUTS
# ============================================

art: ## ⚡ Run artisan command (usage: make art CMD="migrate")
	$(DC) exec app php artisan $(CMD)

exec: ## ⚡ Execute command in app container (usage: make exec CMD="ls -la")
	$(DC) exec app $(CMD)

# ============================================
# DEVELOPMENT HELPERS
# ============================================

watch: ## 👀 Watch application logs (app + queue)
	$(DC) logs -f app queue

dev: up logs ## 🚀 Quick start for development (up + logs)

stop-all: ## ⏹️  Stop all Docker containers (not just this project)
	docker stop $$(docker ps -q) 2>/dev/null || true

# ============================================
# PRODUCTION SPECIFIC
# ============================================

deploy-prod: ## 🚀 Deploy to production (ENV must be prod)
	@if [ "$(ENV)" != "prod" ]; then \
		echo "${RED}❌ ERROR: Use ENV=prod for production deployment${NC}"; \
		exit 1; \
	fi
	@echo "${RED}⚠️  WARNING: This will deploy to PRODUCTION!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		$(MAKE) pull ENV=prod; \
		$(MAKE) down ENV=prod; \
		$(MAKE) up-build ENV=prod; \
		$(MAKE) migrate ENV=prod; \
		$(MAKE) optimize ENV=prod; \
		echo "${GREEN}✅ Production deployment completed!${NC}"; \
	fi

supervisor-status: ## 📊 Show Supervisor status (production only)
	@if [ "$(ENV)" = "prod" ]; then \
		$(DC) exec app supervisorctl status; \
	else \
		echo "${RED}Supervisor only available in production${NC}"; \
	fi

supervisor-restart: ## 🔄 Restart Supervisor services
	@if [ "$(ENV)" = "prod" ]; then \
		$(DC) exec app supervisorctl restart all; \
	else \
		echo "${RED}Supervisor only available in production${NC}"; \
	fi

# ============================================
# SPECIAL COMMANDS
# ============================================

version: ## 📌 Show Laravel and PHP versions
	@echo "${BLUE}📌 Versions:${NC}"
	@$(DC) exec app php --version | head -n 1
	@$(DC) exec app php artisan --version

routes: ## 🗺️  Show application routes
	$(DC) exec app php artisan route:list

models: ## 📦 Show all models
	$(DC) exec app php artisan model:show

about: ## ℹ️  Show application information
	$(DC) exec app php artisan about

# ============================================
# WSL SPECIFIC (Windows Users)
# ============================================

fix-wsl-permissions: ## 🔧 Fix WSL file permissions issues
	@if [ "$(IS_WSL)" = "1" ]; then \
		echo "${BLUE}🔧 Fixing WSL permissions...${NC}"; \
		sudo chmod -R 777 storage bootstrap/cache; \
		echo "${GREEN}✅ WSL permissions fixed!${NC}"; \
	else \
		echo "${YELLOW}Not running in WSL${NC}"; \
	fi

wsl-info: ## ℹ️  Show WSL information
	@if [ "$(IS_WSL)" = "1" ]; then \
		echo "${BLUE}WSL Information:${NC}"; \
		cat /proc/version; \
		df -h | grep -E "^/dev/sd"; \
	else \
		echo "${YELLOW}Not running in WSL${NC}"; \
	fi
