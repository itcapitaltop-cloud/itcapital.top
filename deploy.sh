#!/bin/bash

# Deployment script for itcapital.top
# This script is executed on the server by GitHub Actions

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}🚀 Starting deployment...${NC}"

# Configuration
PROJECT_PATH="${SERVER_PATH:-/var/www/itcapital.top}"
COMPOSE_FILE="docker-compose.production.yml"

cd "$PROJECT_PATH"

# Function to run commands in Docker container
docker_exec() {
    docker exec itcapital-app "$@"
}

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
docker_exec composer install --no-dev --optimize-autoloader --no-interaction

echo -e "${YELLOW}🗄️  Running database migrations...${NC}"
docker_exec php artisan migrate --force

echo -e "${YELLOW}🔧 Optimizing application...${NC}"
docker_exec php artisan config:cache
docker_exec php artisan route:cache
docker_exec php artisan view:cache
docker_exec php artisan event:cache

echo -e "${YELLOW}🧹 Clearing caches...${NC}"
docker_exec php artisan cache:clear

echo -e "${YELLOW}🔄 Restarting services...${NC}"
docker-compose -f "$COMPOSE_FILE" restart app

echo -e "${YELLOW}🔄 Restarting Reverb...${NC}"
docker_exec supervisorctl restart reverb

# Wait for services to be ready
sleep 3

echo -e "${YELLOW}🏥 Health check...${NC}"
docker-compose -f "$COMPOSE_FILE" ps

# Check if app is healthy
if docker_exec php artisan --version > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Application is healthy${NC}"
else
    echo -e "${RED}❌ Application health check failed${NC}"
    exit 1
fi

# Check if Reverb is running
if docker_exec supervisorctl status reverb | grep -q RUNNING; then
    echo -e "${GREEN}✅ Reverb is running${NC}"
else
    echo -e "${RED}⚠️  Reverb is not running${NC}"
fi

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"

# Show recent logs
echo -e "${YELLOW}📝 Recent logs:${NC}"
docker logs --tail=20 itcapital-app
