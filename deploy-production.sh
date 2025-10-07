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
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --import-sql           Import SQL dump after deployment"
            echo "  --sql-file FILE        Specify SQL dump file (default: itc.sql)"
            echo "  --skip-migrations      Skip running migrations (use with --import-sql)"
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
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✅ Directories created${NC}"
echo ""

# Step 3: Clear old caches on host
echo -e "${YELLOW}🧹 Step 3: Clearing old cache files...${NC}"
rm -f bootstrap/cache/*.php
echo -e "${GREEN}✅ Cache cleared${NC}"
echo ""

# Step 4: Build Docker images
echo -e "${YELLOW}🔨 Step 4: Building Docker images...${NC}"
docker-compose -f "$COMPOSE_FILE" build --no-cache
echo -e "${GREEN}✅ Images built${NC}"
echo ""

# Step 5: Start containers
echo -e "${YELLOW}🚀 Step 5: Starting containers...${NC}"
docker-compose -f "$COMPOSE_FILE" up -d
echo -e "${GREEN}✅ Containers started${NC}"
echo ""

# Wait for containers to be ready
echo -e "${YELLOW}⏳ Waiting for containers to be ready...${NC}"
sleep 5

# Step 6: Install dependencies
echo -e "${YELLOW}📦 Step 6: Installing Composer dependencies...${NC}"
docker_exec composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

# Step 7: Generate app key if needed
echo -e "${YELLOW}🔑 Step 7: Checking application key...${NC}"
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generating application key...${NC}"
    docker_exec php artisan key:generate --force
    echo -e "${GREEN}✅ Key generated${NC}"
else
    echo -e "${GREEN}✅ Key already exists${NC}"
fi
echo ""

# Step 8: Database setup
if [ "$IMPORT_SQL" = true ]; then
    echo -e "${YELLOW}🗄️  Step 8: Importing SQL dump...${NC}"

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
    echo -e "${YELLOW}🗄️  Step 8: Running database migrations...${NC}"
    if [ "$SKIP_MIGRATIONS" = true ]; then
        echo -e "${YELLOW}⚠️  Skipping migrations as requested${NC}"
    else
        docker_exec php artisan migrate --force
        echo -e "${GREEN}✅ Migrations completed${NC}"
    fi
fi
echo ""

# Step 9: Create storage link
echo -e "${YELLOW}🔗 Step 9: Creating storage symlink...${NC}"
docker_exec php artisan storage:link || echo -e "${YELLOW}⚠️  Link already exists${NC}"
echo -e "${GREEN}✅ Storage link verified${NC}"
echo ""

# Step 10: Cache configuration
echo -e "${YELLOW}⚡ Step 10: Caching configuration...${NC}"
docker_exec php artisan config:cache
docker_exec php artisan route:cache
docker_exec php artisan view:cache
docker_exec php artisan event:cache
echo -e "${GREEN}✅ Configuration cached${NC}"
echo ""

# Step 11: Health check
echo -e "${YELLOW}🏥 Step 11: Running health checks...${NC}"
echo ""
docker-compose -f "$COMPOSE_FILE" ps
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

# Step 12: Final info
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
echo -e "${YELLOW}🔄 To import SQL dump later:${NC}"
echo -e "   ${BLUE}./deploy-production.sh --import-sql --skip-migrations${NC}"
echo ""
echo -e "${YELLOW}📝 Recent logs:${NC}"
docker logs --tail=20 "$CONTAINER_NAME"
echo ""
echo -e "${GREEN}🎉 Ready for production!${NC}"

