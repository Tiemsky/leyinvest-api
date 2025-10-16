# Laravel Production-Ready CI/CD Setup

A complete, production-ready Laravel application with automated CI/CD pipeline using GitHub Actions, Docker, PostgreSQL, Redis, and Nginx.

## 🚀 Features

- **Automated CI/CD Pipeline** - GitHub Actions workflows for beta and production
- **Dockerized Application** - Multi-stage builds with optimized images
- **Automated Backups** - Database and storage backups before each deployment
- **Health Checks** - Comprehensive health monitoring endpoints
- **Zero-Downtime Deployment** - Graceful deployments with maintenance mode
- **Automatic Rollback** - Failed deployments automatically rollback
- **Queue Workers** - Multiple queue workers with priority support
- **Task Scheduling** - Laravel scheduler running via supervisor
- **Security Best Practices** - Rate limiting, security headers, SSL/TLS
- **Performance Optimized** - OPcache, Redis caching, Nginx optimizations
- **Monitoring Ready** - Comprehensive logging and health checks

## 📋 Tech Stack

- **Backend**: Laravel 12
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Web Server**: Nginx (Alpine)
- **PHP**: 8.3-FPM (Alpine)
- **Container Orchestration**: Docker Compose
- **CI/CD**: GitHub Actions
- **Process Manager**: Supervisor

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     GitHub Repository                        │
│  ┌──────────────┐              ┌──────────────┐            │
│  │   develop    │ ────────────▶│   beta.yml   │            │
│  └──────────────┘              └──────────────┘            │
│  ┌──────────────┐              ┌──────────────┐            │
│  │     main     │ ────────────▶│ production.yml│            │
│  └──────────────┘              └──────────────┘            │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              GitHub Container Registry (GHCR)                │
└─────────────────────────────────────────────────────────────┘
                          │
            ┌─────────────┴─────────────┐
            ▼                           ▼
┌──────────────────────┐    ┌──────────────────────┐
│   Beta Server (VPS)  │    │ Production Server    │
│  ┌────────────────┐  │    │  ┌────────────────┐ │
│  │  Laravel App   │  │    │  │  Laravel App   │ │
│  ├────────────────┤  │    │  ├────────────────┤ │
│  │     Nginx      │  │    │  │     Nginx      │ │
│  ├────────────────┤  │    │  ├────────────────┤ │
│  │  PostgreSQL    │  │    │  │  PostgreSQL    │ │
│  ├────────────────┤  │    │  ├────────────────┤ │
│  │     Redis      │  │    │  │     Redis      │ │
│  ├────────────────┤  │    │  ├────────────────┤ │
│  │  Queue Workers │  │    │  │  Queue Workers │ │
│  └────────────────┘  │    │  └────────────────┘ │
└──────────────────────┘    └──────────────────────┘
```

## 🚦 Quick Start

### Prerequisites

- WSL2 with Ubuntu 20.04+
- Docker Desktop for Windows (with WSL2 backend)
- Git
- Make (optional, but recommended)

### Local Development Setup

1. **Clone the repository**
```bash
git clone git@github.com:your-username/your-repo.git
cd your-repo
```

2. **Copy environment file**
```bash
cp .env.example .env
```

3. **Install dependencies**
```bash
make install
# OR
composer install
```

4. **Start Docker containers**
```bash
make up
# OR
docker-compose up -d
```

5. **Run migrations**
```bash
make migrate
# OR
docker-compose exec app php artisan migrate
```

6. **Access application**
- Application: http://localhost
- PostgreSQL: localhost:5432
- Redis: localhost:6379

## 📦 Available Commands

The project includes a comprehensive Makefile for easy management:

### Container Management
```bash
make up              # Start all containers
make down            # Stop all containers
make restart         # Restart all containers
make ps              # Show container status
make logs            # Show all logs
make logs-app        # Show application logs
make logs-nginx      # Show nginx logs
```

### Application Management
```bash
make shell           # Access app container shell
make shell-db        # Access database shell
make shell-redis     # Access Redis CLI
make tinker          # Open Laravel Tinker
```

### Database Operations
```bash
make migrate         # Run migrations
make migrate-fresh   # Fresh migration (drops all tables)
make seed            # Run database seeders
make fresh           # Fresh migration with seeding
```

### Testing & Quality
```bash
make test            # Run tests
make test-coverage   # Run tests with coverage
```

### Cache Management
```bash
make clear           # Clear all caches
make optimize        # Optimize application
```

### Backup & Restore
```bash
make backup          # Create full backup (DB + storage)
make backup-db       # Backup database only
make backup-storage  # Backup storage only
make restore-db FILE=backup.dump  # Restore database
```

### Deployment
```bash
make deploy-beta     # Deploy to beta environment
make deploy-prod     # Deploy to production environment
```

### Maintenance
```bash
make clean           # Clean unused Docker resources
make clean-all       # Deep clean (including volumes)
make permissions     # Fix file permissions
make health          # Check application health
```

## 🔧 Configuration

### GitHub Secrets

Configure these secrets in your GitHub repository (Settings → Secrets and variables → Actions):

#### Beta Environment
```
BETA_HOST                 # Beta server hostname/IP
BETA_USER                 # SSH username (deploy)
BETA_SSH_PRIVATE_KEY      # SSH private key
BETA_DB_NAME              # Database name
BETA_DB_USER              # Database username
BETA_DB_PASSWORD          # Database password
BETA_DOMAIN               # Beta domain (beta.yourdomain.com)
```

#### Production Environment
```
PROD_HOST                 # Production server hostname/IP
PROD_USER                 # SSH username (deploy)
PROD_SSH_PRIVATE_KEY      # SSH private key
PROD_DB_NAME              # Database name
PROD_DB_USER              # Database username
PROD_DB_PASSWORD          # Database password
PROD_DOMAIN               # Production domain (yourdomain.com)
REDIS_PASSWORD            # Redis password
MAINTENANCE_SECRET        # Maintenance mode bypass secret
SLACK_WEBHOOK_URL         # (Optional) Slack notifications
```

### Environment Variables

Key environment variables in `.env`:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secure_password

REDIS_HOST=redis
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🚀 Deployment Workflow

### Beta Deployment (Automatic)

1. **Develop and commit changes**
```bash
git checkout develop
git add .
git commit -m "Feature: Add new functionality"
git push origin develop
```

2. **GitHub Actions automatically**:
   - ✅ Runs tests
   - ✅ Builds Docker image
   - ✅ Pushes to GitHub Container Registry
   - ✅ Creates backup on beta server
   - ✅ Deploys to beta server
   - ✅ Runs migrations
   - ✅ Clears caches
   - ✅ Performs health check

### Production Deployment (Automatic)

1. **Merge to main branch**
```bash
git checkout main
git merge develop
git push origin main
```

2. **GitHub Actions automatically**:
   - ✅ Runs comprehensive tests
   - ✅ Builds production Docker image
   - ✅ Pushes to GitHub Container Registry
   - ✅ Enables maintenance mode
   - ✅ Creates full backup
   - ✅ Deploys to production server
   - ✅ Runs migrations
   - ✅ Optimizes application
   - ✅ Performs smoke tests
   - ✅ Disables maintenance mode
   - ✅ Automatic rollback on failure

### Manual Deployment

If you need to deploy manually:

```bash
# SSH to server
ssh deploy@your-server

# Navigate to application
cd /var/www/production

# Pull latest image
docker-compose pull

# Create backup
make backup

# Deploy
docker-compose down
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Optimize
docker-compose exec app php artisan optimize
```

## 🔍 Monitoring

### Health Check Endpoints

- **Basic Health**: `GET /health` - Fast check, no dependencies
- **Full Health Check**: `GET /health/check` - Comprehensive check of all services
- **Readiness**: `GET /health/ready` - Kubernetes/Docker readiness probe
- **Liveness**: `GET /health/alive` - Kubernetes/Docker liveness probe

### Example Health Check Response

```json
{
  "status": "healthy",
  "timestamp": "2025-10-15T10:30:00.000000Z",
  "checks": {
    "app": {
      "status": "healthy",
      "details": {
        "version": "1.0.0",
        "disk_usage": "45.23%",
        "memory_usage": "128.5 MB"
      }
    },
    "database": {
      "status": "healthy",
      "details": {
        "connection": "laravel",
        "latency": "2.34 ms"
      }
    },
    "cache": {
      "status": "healthy",
      "details": {
        "driver": "redis"
      }
    },
    "redis": {
      "status": "healthy",
      "details": {
        "latency": "1.12 ms",
        "version": "7.2.0",
        "connected_clients": "5"
      }
    }
  }
}
```

### Logging

View logs in real-time:

```bash
# Application logs
docker-compose logs -f app

# Nginx access logs
docker-compose exec nginx tail -f /var/log/nginx/access.log

# Nginx error logs
docker-compose exec nginx tail -f /var/log/nginx/error.log

# PostgreSQL logs
docker-compose logs postgres

# Queue worker logs
docker-compose logs queue-default
```

## 🔒 Security Best Practices

### Implemented Security Features

- ✅ **Rate Limiting**: API and general request rate limits
- ✅ **Security Headers**: X-Frame-Options, X-Content-Type-Options, CSP, etc.
- ✅ **SSL/TLS**: HTTPS with modern cipher suites
- ✅ **HSTS**: HTTP Strict Transport Security
- ✅ **Secrets Management**: All sensitive data in GitHub Secrets
- ✅ **Database Security**: Strong passwords, limited connections
- ✅ **Redis Authentication**: Password-protected Redis
- ✅ **Nginx Hardening**: Disabled server tokens, denied sensitive files
- ✅ **Docker Security**: Non-root user, minimal images
- ✅ **OPcache**: Disabled validate_timestamps in production

### Additional Security Recommendations

1. **Enable Fail2ban** on your VPS
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

2. **Setup UFW Firewall**
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

3. **Regular Updates**
```bash
# Update Docker images monthly
docker-compose pull
docker-compose up -d

# Update system packages
sudo apt update && sudo apt upgrade -y
```

## 🎯 Performance Optimization

### Implemented Optimizations

- ✅ **OPcache**: PHP bytecode caching
- ✅ **Redis**: Fast in-memory caching and sessions
- ✅ **Nginx Caching**: Static file caching with proper headers
- ✅ **Gzip Compression**: Reduces response sizes
- ✅ **Database Connection Pooling**: Efficient database connections
- ✅ **Laravel Optimization**: Config, route, and view caching
- ✅ **Queue Workers**: Asynchronous job processing
- ✅ **CDN Ready**: Static assets can be served via CDN

### Performance Monitoring

```bash
# Check container resources
make stats

# Monitor queue jobs
make queue-failed

# Check database performance
docker-compose exec postgres pg_stat_statements
```

## 🔄 Backup & Recovery

### Automated Backups

- **Database**: Daily automated backups (retention: 30 days)
- **Storage**: Backed up before each deployment
- **Configuration**: Environment files backed up

### Manual Backup

```bash
# Full backup (database + storage)
make backup

# Database only
make backup-db

# Storage only
make backup-storage
```

### Restore from Backup

```bash
# Restore database
make restore-db FILE=backups/backup_20241015.dump

# Restore storage
tar -xzf backups/storage_20241015.tar.gz -C ./
```

### Offsite Backups (Recommended)

Configure AWS S3 or similar for offsite backups:

```bash
# In your .env
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-backup-bucket
```

## 🐛 Troubleshooting

### Common Issues

#### Container won't start
```bash
# Check logs
docker-compose logs app

# Rebuild containers
docker-compose down
docker-compose up -d --build --force-recreate
```

#### Permission errors
```bash
make permissions
# OR
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

#### Database connection failed
```bash
# Check PostgreSQL
docker-compose exec postgres pg_isready

# Restart database
docker-compose restart postgres
```

#### Redis connection failed
```bash
# Check Redis
docker-compose exec redis redis-cli ping

# Restart Redis
docker-compose restart redis
```

## 📚 Documentation

- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Complete deployment instructions
- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## 🤝 Contributing

1. Create a feature branch from `develop`
2. Make your changes
3. Write tests
4. Submit a pull request
5. Wait for CI/CD to pass
6. Get approval and merge

## 📝 License

This project is licensed under the MIT License.

## 👥 Support

For issues and questions:
- Open an issue on GitHub
- Contact: devops@yourdomain.com
- Documentation: https://docs.yourdomain.com

## 🎉 Acknowledgments

Built with best practices from:
- Laravel Community
- Docker Best Practices
- DevOps Handbook
- The Twelve-Factor App

---

**Version**: 1.0.0
**Last Updated**: October 2025
**Maintained By**: DevOps Team
