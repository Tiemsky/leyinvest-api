# Local Development Setup Guide

Quick guide to get your Laravel application running on your local machine using WSL and Docker.

## Prerequisites

- ✅ Windows with WSL2 enabled
- ✅ Ubuntu 20.04+ on WSL2
- ✅ Docker Desktop for Windows (with WSL2 integration enabled)
- ✅ Git

## Step-by-Step Setup

### 1. Install Docker Desktop for Windows

1. Download [Docker Desktop](https://www.docker.com/products/docker-desktop/)
2. Install and enable WSL2 integration
3. In Docker Desktop settings:
   - Go to **Settings** → **Resources** → **WSL Integration**
   - Enable integration with your Ubuntu distro

### 2. Verify Docker in WSL

Open WSL terminal and verify:

```bash
# Check Docker
docker --version
# Output: Docker version 24.x.x

# Check Docker Compose
docker-compose --version
# Output: Docker Compose version v2.x.x
```

### 3. Clone Repository

```bash
# Navigate to your projects directory
cd ~
mkdir -p projects
cd projects

# Clone the repository
git clone git@github.com:your-username/your-repo.git
cd your-repo
```

### 4. Initial Setup

```bash
# Copy environment file
cp .env.example .env

# Install Composer dependencies (if you have Composer locally)
composer install

# OR if you don't have Composer, you can skip this
# Docker will install dependencies during build
```

### 5. Build and Start Containers

```bash
# Build and start all containers
docker-compose up -d --build

# This will:
# - Build the Laravel app container
# - Start PostgreSQL
# - Start Redis
# - Start Nginx
# - Start Queue worker
# - Start Mailhog (for email testing)
```

Wait for all containers to be healthy (approximately 1-2 minutes).

### 6. Check Container Status

```bash
# View all containers
docker-compose ps

# Should show:
# laravel_app      - running
# laravel_nginx    - running
# laravel_postgres - running (healthy)
# laravel_redis    - running (healthy)
# laravel_queue    - running
# laravel_mailhog  - running
```

### 7. Generate Application Key

```bash
# Generate Laravel application key
docker-compose exec app php artisan key:generate
```

### 8. Run Migrations

```bash
# Run database migrations
docker-compose exec app php artisan migrate

# (Optional) Seed database with sample data
docker-compose exec app php artisan db:seed
```

### 9. Set Proper Permissions

```bash
# Fix storage and cache permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 10. Access Your Application

Your Laravel application is now running! Access it at:

- **Application**: http://localhost
- **Mailhog UI** (email testing): http://localhost:8025
- **PostgreSQL**: localhost:5432
  - Database: `laravel`
  - Username: `laravel`
  - Password: `secret`
- **Redis**: localhost:6379

## Using Makefile Commands

If you installed `make`, you can use convenient shortcuts:

```bash
# Start containers
make up

# Stop containers
make down

# Restart containers
make restart

# View logs
make logs

# Access app shell
make shell

# Run migrations
make migrate

# Run tests
make test

# Clear caches
make clear

# Create backup
make backup

# See all available commands
make help
```

## Common Development Tasks

### Access Container Shell

```bash
# Access app container
docker-compose exec app bash

# Once inside, you can run any artisan command
php artisan route:list
php artisan tinker
```

### Access Database

```bash
# Method 1: Using docker-compose
docker-compose exec postgres psql -U laravel -d laravel

# Method 2: Using local PostgreSQL client
psql -h localhost -p 5432 -U laravel -d laravel
# Password: secret
```

### Access Redis CLI

```bash
docker-compose exec redis redis-cli

# Inside Redis CLI
PING
# Should return: PONG

KEYS *
# Shows all keys
```

### View Logs

```bash
# All logs
docker-compose logs -f

# Specific service logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
docker-compose logs -f redis
docker-compose logs -f queue

# Laravel application logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Run Artisan Commands

```bash
# General format
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan route:list
docker-compose exec app php artisan migrate:status
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan tinker
```

### Install New Composer Package

```bash
# Install package
docker-compose exec app composer require vendor/package

# Update dependencies
docker-compose exec app composer update

# Dump autoload
docker-compose exec app composer dump-autoload
```

### Run Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test file
docker-compose exec app php artisan test tests/Feature/ExampleTest.php

# Run with coverage
docker-compose exec app php artisan test --coverage
```

### Queue Worker Management

```bash
# Start queue worker (if not running)
docker-compose exec app php artisan queue:work

# Restart queue workers
docker-compose exec app php artisan queue:restart

# View failed jobs
docker-compose exec app php artisan queue:failed

# Retry failed job
docker-compose exec app php artisan queue:retry [job-id]

# Retry all failed jobs
docker-compose exec app php artisan queue:retry all
```

### Database Operations

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback last migration
docker-compose exec app php artisan migrate:rollback

# Fresh migration (drops all tables)
docker-compose exec app php artisan migrate:fresh

# Fresh migration with seeding
docker-compose exec app php artisan migrate:fresh --seed

# Create new migration
docker-compose exec app php artisan make:migration create_users_table

# Create seeder
docker-compose exec app php artisan make:seeder UserSeeder

# Run specific seeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

## Testing Email

Emails sent from your application will be caught by Mailhog:

1. Trigger an email in your app (e.g., password reset)
2. Open http://localhost:8025
3. View the email in Mailhog's web interface

## Stopping Your Development Environment

```bash
# Stop all containers (keeps data)
docker-compose down

# Stop and remove volumes (removes database data)
docker-compose down -v
```

## Troubleshooting

### Containers won't start

```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose down
docker-compose up -d --build --force-recreate
```

### Permission errors

```bash
# Fix permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Port already in use

If port 80 is already in use, edit `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8080:80"  # Change to 8080 or any available port
```

Then access app at http://localhost:8080

### Database connection refused

```bash
# Check if PostgreSQL is healthy
docker-compose ps

# Check PostgreSQL logs
docker-compose logs postgres

# Restart PostgreSQL
docker-compose restart postgres
```

### Redis connection failed

```bash
# Check Redis status
docker-compose exec redis redis-cli ping

# Restart Redis
docker-compose restart redis
```

### Clear everything and start fresh

```bash
# Stop all containers
docker-compose down -v

# Remove all Docker resources
docker system prune -a --volumes

# Rebuild from scratch
docker-compose up -d --build
```

## IDE Configuration

### VS Code

Recommended extensions:
- PHP Intelephense
- Laravel Blade Snippets
- Docker
- GitLens

### PhpStorm

1. Settings → PHP → CLI Interpreter
2. Add Docker Compose interpreter
3. Select the `app` service
4. Configure path mappings:
   - Host: `./` → Container: `/var/www`

## Performance Tips

### Enable BuildKit for faster builds

Add to `~/.bashrc` or `~/.zshrc`:

```bash
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1
```

### Allocate more resources to Docker

In Docker Desktop:
- Settings → Resources
- Increase CPUs: 4-6
- Increase Memory: 4-8 GB
- Increase Swap: 2 GB

### Use Docker Compose watch (experimental)

```bash
docker-compose watch
```

This will automatically sync file changes.

## Next Steps

1. ✅ Set up your IDE
2. ✅ Configure Git hooks (optional)
3. ✅ Install Laravel Debugbar for development:
   ```bash
   docker-compose exec app composer require barryvdh/laravel-debugbar --dev
   ```
4. ✅ Configure your database GUI tool (TablePlus, DBeaver, etc.)
5. ✅ Start coding!

## Useful Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

## Getting Help

If you encounter issues:
1. Check the logs: `docker-compose logs`
2. Search GitHub issues
3. Ask the team in Slack/Discord
4. Create an issue with full error details

---

Happy coding! 🚀
