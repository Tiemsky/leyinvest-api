# Laravel CI/CD Deployment Guide

Complete guide for setting up automated deployment with GitHub Actions, Docker, and VPS servers.

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [GitHub Secrets Configuration](#github-secrets-configuration)
4. [VPS Server Setup](#vps-server-setup)
5. [SSL/TLS Configuration](#ssltls-configuration)
6. [Deployment Process](#deployment-process)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Troubleshooting](#troubleshooting)

## Prerequisites

### Local Environment (WSL)
- WSL2 with Ubuntu 20.04+
- Docker Desktop for Windows with WSL2 backend
- Git
- SSH key pair for server access

### VPS Requirements
- Ubuntu 22.04 LTS (recommended)
- Minimum 2GB RAM (4GB+ for production)
- Docker & Docker Compose installed
- Root or sudo access

### GitHub
- GitHub account with repository
- GitHub Container Registry enabled

## Initial Setup

### 1. Clone Repository in WSL

```bash
# In WSL terminal
cd ~
git clone git@github.com:your-username/your-repo.git
cd your-repo
```

### 2. Local Development Setup

```bash
# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Generate application key
php artisan key:generate

# Start local Docker containers
docker-compose up -d
```

### 3. Enable GitHub Container Registry

1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate new token (classic) with these permissions:
   - `write:packages`
   - `read:packages`
   - `delete:packages`
3. Save token securely

## GitHub Secrets Configuration

Add the following secrets in GitHub repository settings (Settings → Secrets and variables → Actions):

### Beta Environment Secrets

```
BETA_HOST=beta.yourdomain.com
BETA_USER=deploy
BETA_SSH_PRIVATE_KEY=<your-private-key>
BETA_DB_NAME=laravel_beta
BETA_DB_USER=laravel
BETA_DB_PASSWORD=<secure-password>
BETA_DOMAIN=beta.yourdomain.com
GITHUB_TOKEN=<already available>
```

### Production Environment Secrets

```
PROD_HOST=yourdomain.com
PROD_USER=deploy
PROD_SSH_PRIVATE_KEY=<your-private-key>
PROD_DB_NAME=laravel_prod
PROD_DB_USER=laravel
PROD_DB_PASSWORD=<secure-password>
PROD_DOMAIN=yourdomain.com
REDIS_PASSWORD=<secure-redis-password>
MAINTENANCE_SECRET=<random-string>
SLACK_WEBHOOK_URL=<optional-for-notifications>
```

## VPS Server Setup

### 1. Initial Server Configuration

SSH into your VPS:

```bash
ssh root@your-server-ip
```

Create deploy user:

```bash
# Create deploy user
adduser deploy
usermod -aG sudo deploy
usermod -aG docker deploy

# Setup SSH for deploy user
mkdir -p /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
chmod 600 /home/deploy/.ssh/authorized_keys

# Add your public SSH key
echo "your-public-key-here" >> /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```

### 2. Install Docker & Docker Compose

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### 3. Setup Application Directories

For **Beta Environment**:

```bash
# Create directory structure
sudo mkdir -p /var/www/beta
sudo mkdir -p /var/backups/laravel/beta
sudo chown -R deploy:deploy /var/www/beta
sudo chown -R deploy:deploy /var/backups/laravel

# Switch to deploy user
su - deploy
cd /var/www/beta

# Create necessary directories
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

For **Production Environment**:

```bash
# Create directory structure
sudo mkdir -p /var/www/production
sudo mkdir -p /var/backups/laravel/production
sudo chown -R deploy:deploy /var/www/production
sudo chown -R deploy:deploy /var/backups/laravel

# Switch to deploy user
su - deploy
cd /var/www/production

# Create necessary directories
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 4. Setup Environment Files

Create `.env` files on each server:

**Beta Server** (`/var/www/beta/.env`):
```bash
APP_NAME="Laravel Beta"
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://beta.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_beta
DB_USERNAME=laravel
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Production Server** (`/var/www/production/.env`):
```bash
APP_NAME="Laravel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_prod
DB_USERNAME=laravel
DB_PASSWORD=your_very_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 5. Copy Docker Compose Files to Servers

On **Beta Server**:
```bash
cd /var/www/beta
# Copy docker-compose.beta.yml as docker-compose.yml
# You'll need to transfer this file via scp or create it manually
```

On **Production Server**:
```bash
cd /var/www/production
# Copy docker-compose.prod.yml as docker-compose.yml
```

### 6. Setup Docker Login

On both servers, login to GitHub Container Registry:

```bash
echo "YOUR_GITHUB_TOKEN" | docker login ghcr.io -u YOUR_GITHUB_USERNAME --password-stdin
```

## SSL/TLS Configuration

### Option 1: Using Certbot (Let's Encrypt) - Recommended

```bash
# Install Certbot
sudo apt update
sudo apt install certbot

# Stop nginx if running
docker-compose down

# Obtain SSL certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Certificates will be in:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem

# Setup auto-renewal
sudo crontab -e
# Add this line:
0 0 * * * certbot renew --quiet --deploy-hook "docker-compose -f /var/www/production/docker-compose.prod.yml restart nginx"
```

### Option 2: Manual SSL Setup

Place your SSL certificates in:
```bash
mkdir -p /var/www/production/docker/nginx/ssl
# Copy your cert.pem and key.pem files there
```

Update nginx configuration to enable SSL (uncomment SSL block in default.conf)

## Deployment Process

### First Deployment

#### Beta Environment

1. **Commit and push to develop branch**:
```bash
git checkout develop
git add .
git commit -m "Initial setup"
git push origin develop
```

2. **GitHub Actions will**:
   - Run tests
   - Build Docker image
   - Push to GitHub Container Registry
   - Deploy to beta server
   - Run migrations
   - Clear caches

3. **Monitor deployment**:
   - Go to GitHub Actions tab
   - Watch the workflow progress

#### Production Environment

1. **Merge to main branch**:
```bash
git checkout main
git merge develop
git push origin main
```

2. **GitHub Actions will**:
   - Run comprehensive tests
   - Build production image
   - Create backups
   - Enable maintenance mode
   - Deploy to production
   - Run migrations
   - Perform smoke tests
   - Disable maintenance mode

### Manual Deployment

If you need to deploy manually:

```bash
# SSH to server
ssh deploy@your-server

# Navigate to app directory
cd /var/www/production  # or /var/www/beta

# Pull latest changes
docker-compose pull

# Stop containers
docker-compose down

# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## Monitoring & Maintenance

### Health Checks

Test application health:
```bash
# Check application
curl https://yourdomain.com/health

# Check containers
docker-compose ps

# Check logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
```

### Database Backups

**Manual backup**:
```bash
# Create backup
docker exec production_postgres pg_dump -U laravel -Fc laravel_prod > backup_$(date +%Y%m%d).dump

# Restore backup
docker exec -i production_postgres pg_restore -U laravel -d laravel_prod --clean < backup_20241015.dump
```

**Automated backups** are configured in docker-compose to run daily.

View backups:
```bash
ls -lh /var/backups/laravel/production/
```

### Log Management

View application logs:
```bash
# Application logs
docker-compose exec app tail -f storage/logs/laravel.log

# Nginx access logs
docker-compose exec nginx tail -f /var/log/nginx/access.log

# Nginx error logs
docker-compose exec nginx tail -f /var/log/nginx/error.log

# PHP-FPM logs
docker-compose exec app tail -f storage/logs/php-fpm-error.log
```

### Performance Monitoring

```bash
# Check container resources
docker stats

# Check disk usage
df -h
docker system df

# Clean unused Docker resources
docker system prune -a --volumes
```

### Queue Workers

Monitor queue workers:
```bash
# Check queue status
docker-compose exec app php artisan queue:work --once

# Restart queue workers
docker-compose exec app php artisan queue:restart

# View failed jobs
docker-compose exec app php artisan queue:failed
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

#### 2. Database Connection Failed

```bash
# Check PostgreSQL status
docker-compose exec postgres pg_isready

# Check database logs
docker-compose logs postgres

# Restart database
docker-compose restart postgres
```

#### 3. Redis Connection Failed

```bash
# Check Redis
docker-compose exec redis redis-cli ping

# Should return PONG

# Check Redis logs
docker-compose logs redis
```

#### 4. Container Won't Start

```bash
# Check container logs
docker-compose logs app

# Rebuild containers
docker-compose down
docker-compose up -d --build --force-recreate
```

#### 5. GitHub Actions Deployment Failed

1. Check GitHub Actions logs
2. Verify all secrets are correctly set
3. Test SSH connection manually:
```bash
ssh -i ~/.ssh/id_rsa deploy@your-server
```

#### 6. SSL Certificate Issues

```bash
# Renew certificate manually
sudo certbot renew

# Test SSL configuration
openssl s_client -connect yourdomain.com:443
```

### Emergency Rollback

If deployment fails, rollback is automatic. To manually rollback:

```bash
# SSH to server
ssh deploy@your-server
cd /var/www/production

# Find latest backup
LATEST_DB=$(ls -t /var/backups/laravel/production/db_*.dump | head -n1)

# Restore database
docker exec -i production_postgres pg_restore -U laravel -d laravel_prod --clean < $LATEST_DB

# Restore storage
LATEST_STORAGE=$(ls -t /var/backups/laravel/production/storage_*.tar.gz | head -n1)
tar -xzf $LATEST_STORAGE -C /var/www/production

# Use previous Docker image
docker-compose down
docker pull ghcr.io/your-username/your-repo:previous-tag
docker-compose up -d
```

## Best Practices

### Security
- ✅ Use strong passwords for database and Redis
- ✅ Keep secrets in GitHub Secrets, never in code
- ✅ Enable SSL/TLS for all environments
- ✅ Regularly update Docker images and dependencies
- ✅ Use fail2ban to prevent brute force attacks
- ✅ Implement rate limiting (already configured in nginx)

### Performance
- ✅ Enable OPcache in production (already configured)
- ✅ Use Redis for caching and sessions
- ✅ Configure proper queue workers
- ✅ Optimize database queries
- ✅ Use CDN for static assets
- ✅ Monitor resource usage regularly

### Maintenance
- ✅ Set up automated backups (daily minimum)
- ✅ Test restore procedures regularly
- ✅ Monitor disk space
- ✅ Clean old Docker images: `docker image prune -a`
- ✅ Review logs weekly
- ✅ Keep documentation updated

### Testing
- ✅ Always test in beta before production
- ✅ Run full test suite before deployment
- ✅ Perform smoke tests after deployment
- ✅ Have rollback plan ready
- ✅ Monitor application after deployment

## Useful Commands

```bash
# View all containers
docker ps -a

# View container resource usage
docker stats

# Execute command in container
docker-compose exec app php artisan tinker

# Database migrations
docker-compose exec app php artisan migrate:status
docker-compose exec app php artisan migrate:rollback

# Clear all caches
docker-compose exec app php artisan optimize:clear

# Generate app key
docker-compose exec app php artisan key:generate

# Create symbolic link for storage
docker-compose exec app php artisan storage:link

# Run seeders
docker-compose exec app php artisan db:seed

# Create admin user (if you have seeder)
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

## Support & Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)
- [Nginx Documentation](https://nginx.org/en/docs/)

## Contributing

When making changes to the CI/CD pipeline:

1. Test in beta first
2. Document all changes
3. Update this guide if needed
4. Get approval before production deployment

---

**Last Updated**: October 2025
**Maintained By**: DevOps Team
