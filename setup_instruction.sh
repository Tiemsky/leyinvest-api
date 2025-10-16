#!/bin/bash

# Laravel Docker Setup Script
# This script creates all necessary directories and sets up the project

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Laravel Docker Environment Setup        ║${NC}"
echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo ""

# Check if running in project root
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: composer.json not found. Please run this script from your Laravel project root.${NC}"
    exit 1
fi

echo -e "${YELLOW}Creating directory structure...${NC}"

# Create storage directories
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Create bootstrap cache
mkdir -p bootstrap/cache

# Create docker directories
mkdir -p docker/nginx
mkdir -p docker/php
mkdir -p docker/postgres
mkdir -p docker/supervisor

# Create backups directory
mkdir -p backups

echo -e "${GREEN}✓ Directories created${NC}"

# Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

# Check for .env file
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}Creating .env file from .env.example...${NC}"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ .env file created${NC}"
    else
        echo -e "${RED}Warning: .env.example not found${NC}"
    fi
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Check for required Docker files
echo -e "${YELLOW}Checking required files...${NC}"

REQUIRED_FILES=(
    "docker-compose.yml"
    "docker/Dockerfile.local"
    "docker/nginx/nginx.conf"
    "docker/nginx/default.conf"
    "docker/php/php.ini"
    "docker/php/php-fpm.conf"
    "docker/supervisor/supervisord.conf"
    ".dockerignore"
)

MISSING_FILES=()

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        MISSING_FILES+=("$file")
        echo -e "${RED}✗ Missing: $file${NC}"
    else
        echo -e "${GREEN}✓ Found: $file${NC}"
    fi
done

if [ ${#MISSING_FILES[@]} -ne 0 ]; then
    echo ""
    echo -e "${RED}Error: Missing required files. Please create these files:${NC}"
    printf "${RED}  - %s${NC}\n" "${MISSING_FILES[@]}"
    echo ""
    echo -e "${YELLOW}Refer to the artifacts provided or DIRECTORY_STRUCTURE.md${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✓ All required files present${NC}"
echo ""

# Check if Docker is running
echo -e "${YELLOW}Checking Docker...${NC}"
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running. Please start Docker Desktop.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker is running${NC}"

# Check if Docker Compose is available
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Error: docker-compose not found. Please install Docker Compose.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker Compose is available${NC}"

echo ""
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Review your ${BLUE}.env${NC} file and update as needed"
echo -e "  2. Run: ${GREEN}make up${NC} or ${GREEN}docker-compose up -d --build${NC}"
echo -e "  3. Generate app key: ${GREEN}docker-compose exec app php artisan key:generate${NC}"
echo -e "  4. Run migrations: ${GREEN}docker-compose exec app php artisan migrate${NC}"
echo -e "  5. Access your app: ${BLUE}http://localhost${NC}"
echo ""
echo -e "${YELLOW}For more information, see:${NC}"
echo -e "  - ${BLUE}LOCAL_SETUP.md${NC} - Detailed local setup guide"
echo -e "  - ${BLUE}README.md${NC} - Project documentation"
echo -e "  - ${BLUE}Makefile${NC} - Available commands (run 'make help')"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
echo ""
