# Laravel Docker Makefile
.PHONY: help install up down restart logs shell test migrate fresh seed clear optimize deploy-beta deploy-prod backup restore

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo '${BLUE}Available commands:${NC}'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${GREEN}%-20s${NC} %s\n", $$1, $$2}'

install: ## Install dependencies and setup project
	@echo "${BLUE}Installing dependencies...${NC}"
	composer install
	cp .env.example .env
	php artisan key:generate
	@echo "${GREEN}Installation complete!${NC}"

up: ## Start all containers
	@echo "${BLUE}Starting containers...${NC}"
	docker-compose up -d
	@echo "${GREEN}Containers started!${NC}"

down: ## Stop all containers
	@echo "${BLUE}Stopping containers...${NC}"
	docker-compose down
	@echo "${GREEN}Containers stopped!${NC}"

restart: ## Restart all containers
	@echo "${BLUE}Restarting containers...${NC}"
	docker-compose restart
	@echo "${GREEN}Containers restarted!${NC}"

logs: ## Show container logs
	docker-compose logs -f

logs-app: ## Show application logs
	docker-compose exec app tail -f storage/logs/laravel.log

logs-nginx: ## Show nginx logs
	docker-compose exec nginx tail -f /var/log/nginx/access.log

shell: ## Access application container shell
	docker-compose exec app bash

shell-db: ## Access database container shell
	docker-compose exec postgres psql -U ${DB_USERNAME} -d ${DB_DATABASE}

shell-redis: ## Access redis container shell
	docker-compose exec redis redis-cli

test: ## Run tests
	@echo "${BLUE}Running tests...${NC}"
	docker-compose exec app php artisan test
	@echo "${GREEN}Tests completed!${NC}"

test-coverage: ## Run tests with coverage
	@echo "${BLUE}Running tests with coverage...${NC}"
	docker-compose exec app php artisan test --coverage
	@echo "${GREEN}Tests with coverage completed!${NC}"

migrate: ## Run database migrations
	@echo "${BLUE}Running migrations...${NC}"
	docker-compose exec app php artisan migrate
	@echo "${GREEN}Migrations completed!${NC}"

migrate-fresh: ## Fresh migration (drops all tables)
	@echo "${RED}WARNING: This will drop all tables!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec app php artisan migrate:fresh; \
		echo "${GREEN}Fresh migration completed!${NC}"; \
	fi

seed: ## Run database seeders
	@echo "${BLUE}Running seeders...${NC}"
	docker-compose exec app php artisan db:seed
	@echo "${GREEN}Seeding completed!${NC}"

fresh: ## Fresh migration with seeding
	@echo "${RED}WARNING: This will drop all tables and reseed!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec app php artisan migrate:fresh --seed; \
		echo "${GREEN}Fresh setup completed!${NC}"; \
	fi

clear: ## Clear all caches
	@echo "${BLUE}Clearing caches...${NC}"
	docker-compose exec app php artisan optimize:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	@echo "${GREEN}Caches cleared!${NC}"

optimize: ## Optimize application
	@echo "${BLUE}Optimizing application...${NC}"
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app php artisan event:cache
	@echo "${GREEN}Optimization completed!${NC}"

queue-work: ## Start queue worker
	docker-compose exec app php artisan queue:work

queue-restart: ## Restart queue workers
	docker-compose exec app php artisan queue:restart

queue-failed: ## List failed queue jobs
	docker-compose exec app php artisan queue:failed

backup-db: ## Backup database
	@echo "${BLUE}Creating database backup...${NC}"
	@mkdir -p backups
	docker exec $$(docker-compose ps -q postgres) pg_dump -U ${DB_USERNAME} -Fc ${DB_DATABASE} > backups/backup_$$(date +%Y%m%d_%H%M%S).dump
	@echo "${GREEN}Database backup created!${NC}"

backup-storage: ## Backup storage files
	@echo "${BLUE}Creating storage backup...${NC}"
	@mkdir -p backups
	tar -czf backups/storage_$$(date +%Y%m%d_%H%M%S).tar.gz storage/app
	@echo "${GREEN}Storage backup created!${NC}"

backup: backup-db backup-storage ## Create full backup (database + storage)
	@echo "${GREEN}Full backup completed!${NC}"

restore-db: ## Restore database from backup (usage: make restore-db FILE=backup.dump)
	@if [ -z "$(FILE)" ]; then \
		echo "${RED}ERROR: Please specify FILE=path/to/backup.dump${NC}"; \
		exit 1; \
	fi
	@echo "${BLUE}Restoring database from $(FILE)...${NC}"
	docker exec -i $$(docker-compose ps -q postgres) pg_restore -U ${DB_USERNAME} -d ${DB_DATABASE} --clean < $(FILE)
	@echo "${GREEN}Database restored!${NC}"

ps: ## Show container status
	docker-compose ps

stats: ## Show container resource usage
	docker stats --no-stream

clean: ## Clean unused Docker resources
	@echo "${BLUE}Cleaning Docker resources...${NC}"
	docker system prune -f
	@echo "${GREEN}Cleanup completed!${NC}"

clean-all: ## Clean all Docker resources (including volumes)
	@echo "${RED}WARNING: This will remove all unused Docker resources including volumes!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker system prune -a --volumes -f; \
		echo "${GREEN}Deep cleanup completed!${NC}"; \
	fi

build: ## Build Docker images
	@echo "${BLUE}Building Docker images...${NC}"
	docker-compose build --no-cache
	@echo "${GREEN}Build completed!${NC}"

pull: ## Pull latest Docker images
	@echo "${BLUE}Pulling latest images...${NC}"
	docker-compose pull
	@echo "${GREEN}Pull completed!${NC}"

deploy-beta: ## Deploy to beta environment
	@echo "${BLUE}Deploying to beta...${NC}"
	git push origin develop
	@echo "${GREEN}Beta deployment triggered!${NC}"

deploy-prod: ## Deploy to production environment
	@echo "${RED}WARNING: This will deploy to PRODUCTION!${NC}"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		git push origin main; \
		echo "${GREEN}Production deployment triggered!${NC}"; \
	fi

health: ## Check application health
	@echo "${BLUE}Checking application health...${NC}"
	@curl -s http://localhost/health || echo "${RED}Health check failed!${NC}"
	@echo ""

tinker: ## Open Laravel Tinker
	docker-compose exec app php artisan tinker

composer-update: ## Update composer dependencies
	@echo "${BLUE}Updating composer dependencies...${NC}"
	docker-compose exec app composer update
	@echo "${GREEN}Dependencies updated!${NC}"

npm-install: ## Install npm dependencies
	@echo "${BLUE}Installing npm dependencies...${NC}"
	docker-compose exec app npm install
	@echo "${GREEN}npm dependencies installed!${NC}"

npm-dev: ## Run npm development build
	docker-compose exec app npm run dev

npm-build: ## Run npm production build
	docker-compose exec app npm run build

permissions: ## Fix file permissions
	@echo "${BLUE}Fixing permissions...${NC}"
	docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec app chmod -R 775 storage bootstrap/cache
	@echo "${GREEN}Permissions fixed!${NC}"

horizon: ## Start Laravel Horizon
	docker-compose exec app php artisan horizon

horizon-terminate: ## Terminate Laravel Horizon
	docker-compose exec app php artisan horizon:terminate
