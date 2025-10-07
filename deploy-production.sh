#!/bin/bash

# Production deployment script for /opt/itc/web
# This script should be run ON THE SERVER

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
PROJECT_PATH="/opt/itc/web"
COMPOSE_FILE="docker-compose.prod.yml"
CONTAINER_NAME="itcapital-app"
DB_CONTAINER_NAME="itcapital-pgdb"
SQL_DUMP_FILE="itc.sql"

# Parse command line arguments
IMPORT_SQL=false
SKIP_MIGRATIONS=false
FORCE_REBUILD=false
CLEANUP_DOCKER=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --import-sql)
            IMPORT_SQL=true
            shift
            ;;
        --skip-migrations)
            SKIP_MIGRATIONS=true
            shift
            ;;
        --sql-file)
            SQL_DUMP_FILE="$2"
            shift 2
            ;;
        --rebuild)
            FORCE_REBUILD=true
            shift
            ;;
        --cleanup)
            CLEANUP_DOCKER=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --import-sql           Import SQL dump after deployment"
            echo "  --sql-file FILE        Specify SQL dump file (default: itc.sql)"
            echo "  --skip-migrations      Skip running migrations (use with --import-sql)"
            echo "  --rebuild              Force rebuild Docker images (ignores cache)"
            echo "  --cleanup              Clean up old Docker images and containers"
            echo "  --help                 Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${BLUE}   ITC Capital - Production Deployment${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo ""

# Check if running from correct directory
if [ ! -f "$COMPOSE_FILE" ]; then
    echo -e "${RED}❌ Error: docker-compose.prod.yml not found!${NC}"
    echo -e "${YELLOW}Please run this script from: $PROJECT_PATH${NC}"
    exit 1
fi

# Function to run commands in Docker container
docker_exec() {
    docker exec "$CONTAINER_NAME" "$@"
}

# Step 1: Check environment
echo -e "${YELLOW}📋 Step 1: Checking environment...${NC}"
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    echo -e "${YELLOW}Creating from .env.example...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}⚠️  Please edit .env file and set production values!${NC}"
    read -p "Press enter to continue after editing .env..."
fi
echo -e "${GREEN}✅ Environment configured${NC}"
echo ""

# Step 2: Create directories
echo -e "${YELLOW}📁 Step 2: Creating storage directories...${NC}"
mkdir -p storage/logs
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache
echo -e "${GREEN}✅ Directories created (permissions will be set by container)${NC}"
echo ""

# Step 3: Enable maintenance mode (if app is already running)
echo -e "${YELLOW}🔧 Step 3: Enabling maintenance mode...${NC}"
if docker ps | grep -q "$CONTAINER_NAME"; then
    docker_exec php artisan down --render="errors::503" --retry=60 || true
    echo -e "${GREEN}✅ Maintenance mode enabled (users see beautiful update page)${NC}"
else
    echo -e "${YELLOW}⚠️  Container not running, skipping maintenance mode${NC}"
fi
echo ""

# Step 4: Clear old caches on host
echo -e "${YELLOW}🧹 Step 4: Clearing old cache files...${NC}"
rm -f bootstrap/cache/*.php
echo -e "${GREEN}✅ Cache cleared${NC}"
echo ""

# Step 5: Build Docker images (smart caching)
echo -e "${YELLOW}🔨 Step 5: Checking Docker images...${NC}"

# Check if we need to rebuild from scratch
NEED_REBUILD=false
if [ "$FORCE_REBUILD" = true ]; then
    echo -e "${YELLOW}Force rebuild requested...${NC}"
    NEED_REBUILD=true
elif ! docker images | grep -q "itcapital-app"; then
    echo -e "${YELLOW}No image found, building...${NC}"
    NEED_REBUILD=true
elif [ -f ".dockerfile_changed" ]; then
    echo -e "${YELLOW}Dockerfile changed, rebuilding...${NC}"
    NEED_REBUILD=true
    rm -f ".dockerfile_changed"
fi

if [ "$NEED_REBUILD" = true ]; then
    docker compose -f "$COMPOSE_FILE" build --no-cache
    echo -e "${GREEN}✅ Images built from scratch${NC}"
else
    # Use cache for much faster builds (30 seconds vs 10 minutes)
    docker compose -f "$COMPOSE_FILE" build
    echo -e "${GREEN}✅ Images checked (using cache)${NC}"
fi
echo ""

# Step 6: Start containers
echo -e "${YELLOW}🚀 Step 6: Starting containers...${NC}"
docker compose -f "$COMPOSE_FILE" up -d
echo -e "${GREEN}✅ Containers started${NC}"
echo ""

# Wait for containers to be ready
echo -e "${YELLOW}⏳ Waiting for containers to be ready...${NC}"
sleep 5

# Fix permissions inside container (in case files were created with wrong permissions)
echo -e "${YELLOW}🔧 Fixing file permissions...${NC}"
docker_exec chown -R sail:sail storage bootstrap/cache 2>/dev/null || true
docker_exec chmod -R 775 storage bootstrap/cache 2>/dev/null || true
echo -e "${GREEN}✅ Permissions fixed${NC}"
echo ""

# Step 7: Install dependencies
echo -e "${YELLOW}📦 Step 7: Installing Composer dependencies...${NC}"
docker_exec composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

# Step 8: Generate app key if needed
echo -e "${YELLOW}🔑 Step 8: Checking application key...${NC}"
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generating application key...${NC}"
    docker_exec php artisan key:generate --force
    echo -e "${GREEN}✅ Key generated${NC}"
else
    echo -e "${GREEN}✅ Key already exists${NC}"
fi
echo ""

# Step 9: Database setup
if [ "$IMPORT_SQL" = true ]; then
    echo -e "${YELLOW}🗄️  Step 9: Importing SQL dump...${NC}"

    if [ ! -f "$SQL_DUMP_FILE" ]; then
        echo -e "${RED}❌ SQL dump file not found: $SQL_DUMP_FILE${NC}"
        echo -e "${YELLOW}Available SQL files:${NC}"
        ls -lh *.sql 2>/dev/null || echo "No .sql files found"
        exit 1
    fi

    # Get database credentials from .env
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)

    echo -e "${YELLOW}Importing $SQL_DUMP_FILE into database $DB_NAME...${NC}"

    # Wait for PostgreSQL to be ready
    echo -e "${YELLOW}Waiting for PostgreSQL to be ready...${NC}"
    sleep 3

    # Import SQL dump
    docker exec -i "$DB_CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" < "$SQL_DUMP_FILE"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ SQL dump imported successfully${NC}"
    else
        echo -e "${RED}❌ Failed to import SQL dump${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}🗄️  Step 9: Running database migrations...${NC}"
    if [ "$SKIP_MIGRATIONS" = true ]; then
        echo -e "${YELLOW}⚠️  Skipping migrations as requested${NC}"
    else
        docker_exec php artisan migrate --force
        echo -e "${GREEN}✅ Migrations completed${NC}"
    fi
fi
echo ""

# Step 10: Create storage link
echo -e "${YELLOW}🔗 Step 10: Creating storage symlink...${NC}"
docker_exec php artisan storage:link || echo -e "${YELLOW}⚠️  Link already exists${NC}"
echo -e "${GREEN}✅ Storage link verified${NC}"
echo ""

# Step 11: Build frontend assets
echo -e "${YELLOW}🏗️  Step 11: Building frontend assets...${NC}"
if docker_exec test -f "package.json"; then
    docker_exec npm ci --include=dev
    docker_exec npm run build
    echo -e "${GREEN}✅ Frontend assets built${NC}"
else
    echo -e "${YELLOW}⚠️  No package.json found, skipping frontend build${NC}"
fi
echo ""

# Step 12: Cache configuration
echo -e "${YELLOW}⚡ Step 12: Caching configuration...${NC}"
docker_exec php artisan config:cache
docker_exec php artisan route:cache
docker_exec php artisan view:cache
docker_exec php artisan event:cache

# Clear OPcache if enabled
if docker_exec php -r "echo ini_get('opcache.enable');" 2>/dev/null | grep -q "1"; then
    echo -e "${YELLOW}Clearing OPcache...${NC}"
    docker_exec php artisan optimize:clear || true
    # Restart PHP-FPM to clear opcache
    docker_exec supervisorctl restart php-fpm || true
fi

echo -e "${GREEN}✅ Configuration cached${NC}"
echo ""

# Step 13: Disable maintenance mode
echo -e "${YELLOW}🎉 Step 13: Disabling maintenance mode...${NC}"
docker_exec php artisan up || true
echo -e "${GREEN}✅ Site is now live!${NC}"
echo ""

# Step 14: Health check
echo -e "${YELLOW}🏥 Step 14: Running health checks...${NC}"
echo ""
docker compose -f "$COMPOSE_FILE" ps
echo ""

if docker_exec php artisan --version > /dev/null 2>&1; then
    VERSION=$(docker_exec php artisan --version)
    echo -e "${GREEN}✅ Application is healthy: $VERSION${NC}"
else
    echo -e "${RED}❌ Application health check failed!${NC}"
    echo -e "${YELLOW}Checking logs...${NC}"
    docker logs --tail=50 "$CONTAINER_NAME"
    exit 1
fi

# Check Reverb
if docker_exec supervisorctl status reverb | grep -q RUNNING; then
    echo -e "${GREEN}✅ Reverb is running${NC}"
else
    echo -e "${YELLOW}⚠️  Reverb is not running${NC}"
fi
echo ""

# Step 15: Docker cleanup (if requested)
if [ "$CLEANUP_DOCKER" = true ]; then
    echo -e "${YELLOW}🧹 Step 15: Cleaning up old Docker resources...${NC}"
    echo -e "${YELLOW}This will remove:${NC}"
    echo -e "  - Stopped containers"
    echo -e "  - Unused images"
    echo -e "  - Unused networks"
    echo -e "  - Build cache"
    echo ""

    # Show disk usage before cleanup
    echo -e "${YELLOW}Disk usage before cleanup:${NC}"
    docker system df
    echo ""

    # Clean up
    docker container prune -f
    docker image prune -a -f --filter "until=24h"
    docker network prune -f
    docker builder prune -f

    echo ""
    echo -e "${YELLOW}Disk usage after cleanup:${NC}"
    docker system df
    echo ""
    echo -e "${GREEN}✅ Cleanup completed${NC}"
    echo ""
fi

# Step 16: Final info
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📝 Important information:${NC}"
echo -e "   Project path: ${BLUE}$PROJECT_PATH${NC}"
echo -e "   Container: ${BLUE}$CONTAINER_NAME${NC}"
echo -e "   App URL: ${BLUE}http://localhost:8000${NC}"
echo ""
echo -e "${YELLOW}📋 Useful commands:${NC}"
echo -e "   View logs: ${BLUE}docker logs -f $CONTAINER_NAME${NC}"
echo -e "   Enter container: ${BLUE}docker exec -it $CONTAINER_NAME bash${NC}"
echo -e "   Restart: ${BLUE}docker-compose -f $COMPOSE_FILE restart${NC}"
echo -e "   Stop: ${BLUE}docker-compose -f $COMPOSE_FILE stop${NC}"
echo ""
echo -e "${YELLOW}🔄 Other useful commands:${NC}"
echo -e "   Import SQL: ${BLUE}./deploy-production.sh --import-sql --skip-migrations${NC}"
echo -e "   Clean Docker: ${BLUE}./deploy-production.sh --cleanup${NC}"
echo -e "   Full rebuild: ${BLUE}./deploy-production.sh --rebuild --cleanup${NC}"
echo ""
echo -e "${YELLOW}📝 Recent logs:${NC}"
docker logs --tail=20 "$CONTAINER_NAME"
echo ""
echo -e "${GREEN}🎉 Ready for production!${NC}"

