# Setup Checklist

Use this checklist to ensure your Laravel Docker environment is properly configured.

## ✅ Local Development Setup

### Prerequisites
- [ ] Windows with WSL2 installed
- [ ] Ubuntu 20.04+ running in WSL2
- [ ] Docker Desktop installed and running
- [ ] Docker Desktop WSL2 integration enabled
- [ ] Git installed in WSL

### File Structure
- [ ] `docker-compose.yml` exists in project root
- [ ] `docker/Dockerfile.local` exists
- [ ] `docker/Dockerfile` exists (for production builds)
- [ ] `docker/nginx/nginx.conf` exists
- [ ] `docker/nginx/default.conf` exists
- [ ] `docker/php/php.ini` exists
- [ ] `docker/php/php-fpm.conf` exists
- [ ] `docker/supervisor/supervisord.conf` exists
- [ ] `.dockerignore` exists
- [ ] `.env.example` exists
- [ ] `Makefile` exists
- [ ] All required directories created (run `./setup.sh`)

### Configuration Files Created
- [ ] `.env` file created and configured
- [ ] `app/Http/Controllers/HealthController.php` created
- [ ] `routes/health.php` created
- [ ] Health routes registered in `routes/web.php` or service provider

### Permissions
- [ ] `storage/` directory has 775 permissions
- [ ] `bootstrap/cache/` directory has 775 permissions
- [ ] `setup.sh` has execute permissions (`chmod +x setup.sh`)

### Initial Build
- [ ] Run `./setup.sh` successfully
- [ ] Run `docker-compose up -d --build` successfully
- [ ] All containers are running (`docker-compose ps`)
- [ ] PostgreSQL is healthy
- [ ] Redis is healthy
- [ ] No error logs (`docker-compose logs`)

### Application Setup
- [ ] Generate app key: `docker-compose exec app php artisan key:generate`
- [ ] Run migrations: `docker-compose exec app php artisan migrate`
- [ ] Application accessible at http://localhost
- [ ] Health check works: http://localhost/health
- [ ] Mailhog accessible at http://localhost:8025

## ✅ GitHub Repository Setup

### Repository Configuration
- [ ] Repository created on GitHub
- [ ] GitHub Container Registry enabled
- [ ] Personal Access Token created with `write:packages` permission

### Workflow Files
- [ ] `.github/workflows/beta.yml` committed
- [ ] `.github/workflows/production.yml` committed
- [ ] Workflows syntax is valid (check GitHub Actions tab)

### GitHub Secrets - Beta Environment
- [ ] `BETA_HOST` - Beta server IP/hostname
- [ ] `BETA_USER` - SSH username (deploy)
- [ ] `BETA_SSH_PRIVATE_KEY` - Private SSH key
- [ ] `BETA_DB_NAME` - Database name
- [ ] `BETA_DB_USER` - Database username
- [ ] `BETA_DB_PASSWORD` - Database password
- [ ] `BETA_DOMAIN` - Beta domain (beta.yourdomain.com)

### GitHub Secrets - Production Environment
- [ ] `PROD_HOST` - Production server IP/hostname
- [ ] `PROD_USER` - SSH username (deploy)
- [ ] `PROD_SSH_PRIVATE_KEY` - Private SSH key
- [ ] `PROD_DB_NAME` - Database name
- [ ] `PROD_DB_USER` - Database username
- [ ] `PROD_DB_PASSWORD` - Database password
- [ ] `PROD_DOMAIN` - Production domain
- [ ] `REDIS_PASSWORD` - Redis password
- [ ] `MAINTENANCE_SECRET` - Maintenance mode bypass secret
- [ ] `SLACK_WEBHOOK_URL` - (Optional) for notifications

## ✅ VPS Server Setup (Beta)

### Server Access
- [ ] Can SSH into beta server
- [ ] Deploy user created
- [ ] Deploy user has sudo privileges
- [ ] Deploy user in docker group
- [ ] SSH key authentication working

### Software Installation
- [ ] Docker installed on beta server
- [ ] Docker Compose installed on beta server
- [ ] Docker daemon running

### Directory Structure
- [ ] `/var/www/beta` directory created
- [ ] `/var/backups/laravel/beta` directory created
- [ ] Correct ownership (deploy:deploy)
- [ ] `docker-compose.beta.yml` uploaded as `docker-compose.yml`
- [ ] `.env` file created with beta configuration

### First Deployment
- [ ] Logged into GitHub Container Registry on server
- [ ] Can pull images from GHCR
- [ ] Containers start successfully
- [ ] Database migrations run
- [ ] Application accessible via beta domain
- [ ] Health checks passing

## ✅ VPS Server Setup (Production)

### Server Access
- [ ] Can SSH into production server
- [ ] Deploy user created
- [ ] Deploy user has sudo privileges
- [ ] Deploy user in docker group
- [ ] SSH key authentication working

### Software Installation
- [ ] Docker installed on production server
- [ ] Docker Compose installed on production server
- [ ] Docker daemon running

### Directory Structure
- [ ] `/var/www/production` directory created
- [ ] `/var/backups/laravel/production` directory created
- [ ] Correct ownership (deploy:deploy)
- [ ] `docker-compose.prodroction.yml` uploaded as `docker-compose.yml`
- [ ] `.env` file created with production configuration

### SSL/TLS
- [ ] Domain pointing to server IP
- [ ] SSL certificate obtained (Certbot)
- [ ] SSL certificate auto-renewal configured
- [ ] HTTPS working
- [ ] HTTP redirects to HTTPS

### Security
- [ ] UFW firewall enabled
- [ ] Only ports 22, 80, 443 open
- [ ] Fail2ban installed and configured
- [ ] Strong passwords used everywhere
- [ ] SSH password authentication disabled

### First Deployment
- [ ] Logged into GitHub Container Registry on server
- [ ] Can pull images from GHCR
- [ ] Containers start successfully
- [ ] Database migrations run
- [ ] Application accessible via production domain
- [ ] Health checks passing
- [ ] SSL certificate valid

## ✅ CI/CD Testing

### Beta Pipeline
- [ ] Push to `develop` branch triggers workflow
- [ ] Tests pass
- [ ] Docker image builds successfully
- [ ] Image pushed to GHCR
- [ ] Backup created on server
- [ ] Deployment succeeds
- [ ] Migrations run automatically
- [ ] Health check passes
- [ ] Application updated on beta server

### Production Pipeline
- [ ] Push to `main` branch triggers workflow
- [ ] Tests pass (including coverage check)
- [ ] Security audit passes
- [ ] Docker image builds successfully
- [ ] Image pushed to GHCR
- [ ] Maintenance mode enabled
- [ ] Backup created on server
- [ ] Deployment succeeds
- [ ] Migrations run automatically
- [ ] Smoke tests pass
- [ ] Maintenance mode disabled
- [ ] Application updated on production server

### Rollback Testing
- [ ] Introduce a failing test
- [ ] Push to see if rollback works
- [ ] Verify application remains operational
- [ ] Fix the test and redeploy

## ✅ Monitoring & Maintenance

### Health Checks
- [ ] `/health` endpoint returns 200
- [ ] `/health/check` shows all services healthy
- [ ] `/health/ready` returns 200
- [ ] `/health/alive` returns 200

### Logging
- [ ] Application logs accessible
- [ ] Nginx logs accessible
- [ ] Database logs accessible
- [ ] Log rotation configured

### Backups
- [ ] Automated daily database backups working
- [ ] Backup retention policy set (30 days)
- [ ] Can restore from backup successfully
- [ ] Offsite backups configured (optional but recommended)

### Performance
- [ ] Response times acceptable
- [ ] Database queries optimized
- [ ] OPcache working
- [ ] Redis cache working
- [ ] Queue workers processing jobs

## ✅ Documentation

### Project Documentation
- [ ] README.md updated with project details
- [ ] LOCAL_SETUP.md reviewed
- [ ] DEPLOYMENT_GUIDE.md reviewed
- [ ] Team members have access to documentation

### Runbooks
- [ ] Emergency rollback procedure documented
- [ ] Common troubleshooting steps documented
- [ ] On-call contact information available
- [ ] Incident response plan in place

## ✅ Team Handoff

### Knowledge Transfer
- [ ] Team trained on deployment process
- [ ] Access credentials shared securely
- [ ] Monitoring dashboards accessible
- [ ] Support channels established


**Last Updated**: [Your Date]
**Completed By**: [Your Name]
