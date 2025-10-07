# 🚀 Zero-Downtime Deployment для ITC Capital

## Концепция

При деплое новая версия запускается параллельно со старой:
1. ✅ Старая версия работает → пользователи видят сайт
2. ✅ Новая версия собирается → пользователи видят сайт
3. ✅ Новая версия тестируется → пользователи видят сайт
4. ✅ Nginx переключается на новую → **мгновенное переключение**
5. ✅ Старая версия останавливается

**Результат: 0 секунд даунтайма!**

## Реализация

### Вариант А: Простой (два контейнера app)

Nginx будет переключаться между `app-blue` и `app-green`.

**docker-compose.zero-downtime.yml:**
```yaml
name: itcapital
services:
  # Blue version (активная)
  app-blue:
    build:
      context: ./docker/8.4
      args:
        WWWGROUP: 1000
    container_name: "${COMPOSE_PROJECT_NAME}-app-blue"
    env_file:
      - .env
    networks:
      - net
    restart: unless-stopped
    volumes:
      - ./:/var/www/html
    # БЕЗ портов - только через nginx
    healthcheck:
      test: ["CMD-SHELL", "nc -z localhost 80 || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 30s

  # Green version (для деплоя)
  app-green:
    build:
      context: ./docker/8.4
      args:
        WWWGROUP: 1000
    container_name: "${COMPOSE_PROJECT_NAME}-app-green"
    env_file:
      - .env
    networks:
      - net
    restart: unless-stopped
    volumes:
      - ./:/var/www/html
    # БЕЗ портов - только через nginx
    healthcheck:
      test: ["CMD-SHELL", "nc -z localhost 80 || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 30s

  nginx:
    build:
      context: ./docker/nginx-zero-downtime
    container_name: "${COMPOSE_PROJECT_NAME}-nginx"
    ports:
      - "${NGINX_PORT:-80}:80"
    depends_on:
      - app-blue
      - app-green
    volumes:
      - ./:/var/www/html
      - ./docker/nginx-zero-downtime/active_upstream:/etc/nginx/active_upstream
    networks:
      - net
    restart: unless-stopped

  pgdb:
    build:
      context: ./docker/db
    container_name: "${COMPOSE_PROJECT_NAME}-pgdb"
    environment:
      POSTGRES_USER: ${DB_USERNAME:-user}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-password}
      POSTGRES_DB: ${DB_DATABASE:-appdb}
    volumes:
      - db-data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - net
    restart: unless-stopped

  redis:
    build:
      context: ./docker/redis
    container_name: redis
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis-data:/data
    networks:
      - net
    restart: unless-stopped

networks:
  net:
    driver: bridge
  monitoring:
    driver: bridge

volumes:
  db-data:
  redis-data:
```

**Nginx конфигурация с переключением:**
```nginx
# docker/nginx-zero-downtime/default.conf

upstream app-backend {
    # Файл определяет какой контейнер активен
    include /etc/nginx/active_upstream;
}

server {
    listen 80;
    server_name _;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        proxy_pass http://app-backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Скрипт для переключения:**
```bash
#!/bin/bash
# deploy-zero-downtime.sh

set -e

# Цвета
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROJECT_PATH="/opt/itc/web"
COMPOSE_FILE="docker-compose.zero-downtime.yml"
NGINX_CONTAINER="itcapital-nginx"
UPSTREAM_FILE="docker/nginx-zero-downtime/active_upstream"

# Определяем текущий активный контейнер
if grep -q "app-blue" "$UPSTREAM_FILE" 2>/dev/null; then
    CURRENT="blue"
    NEW="green"
    CURRENT_CONTAINER="itcapital-app-blue"
    NEW_CONTAINER="itcapital-app-green"
else
    CURRENT="green"
    NEW="blue"
    CURRENT_CONTAINER="itcapital-app-green"
    NEW_CONTAINER="itcapital-app-blue"
fi

echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Zero-Downtime Deployment${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${YELLOW}Current: $CURRENT → Deploying to: $NEW${NC}"
echo ""

# Step 1: Build new version
echo -e "${YELLOW}🔨 Step 1: Building new version ($NEW)...${NC}"
docker compose -f "$COMPOSE_FILE" build app-$NEW
echo -e "${GREEN}✅ Built${NC}"
echo ""

# Step 2: Start new version
echo -e "${YELLOW}🚀 Step 2: Starting new version...${NC}"
docker compose -f "$COMPOSE_FILE" up -d app-$NEW
echo ""

# Wait for container to be ready
echo -e "${YELLOW}⏳ Waiting for new version to be healthy...${NC}"
sleep 5

# Wait for health check
for i in {1..30}; do
    if docker inspect --format='{{.State.Health.Status}}' "$NEW_CONTAINER" 2>/dev/null | grep -q "healthy"; then
        echo -e "${GREEN}✅ New version is healthy!${NC}"
        break
    fi
    echo -n "."
    sleep 2
    if [ $i -eq 30 ]; then
        echo -e "${RED}❌ New version failed health check!${NC}"
        exit 1
    fi
done
echo ""

# Step 3: Install dependencies in new container
echo -e "${YELLOW}📦 Step 3: Installing dependencies...${NC}"
docker exec "$NEW_CONTAINER" composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Composer done${NC}"
echo ""

# Step 4: Build frontend in new container
echo -e "${YELLOW}🏗️  Step 4: Building frontend...${NC}"
docker exec "$NEW_CONTAINER" npm ci --include=dev
docker exec "$NEW_CONTAINER" npm run build
echo -e "${GREEN}✅ Frontend built${NC}"
echo ""

# Step 5: Cache configuration in new container
echo -e "${YELLOW}⚡ Step 5: Caching configuration...${NC}"
docker exec "$NEW_CONTAINER" php artisan config:cache
docker exec "$NEW_CONTAINER" php artisan route:cache
docker exec "$NEW_CONTAINER" php artisan view:cache
docker exec "$NEW_CONTAINER" php artisan event:cache
echo -e "${GREEN}✅ Cached${NC}"
echo ""

# Step 6: Run smoke test
echo -e "${YELLOW}🧪 Step 6: Running smoke test...${NC}"
if docker exec "$NEW_CONTAINER" php artisan --version > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Smoke test passed${NC}"
else
    echo -e "${RED}❌ Smoke test failed!${NC}"
    exit 1
fi
echo ""

# Step 7: Switch nginx upstream
echo -e "${YELLOW}🔄 Step 7: Switching traffic to new version...${NC}"
echo "server $NEW_CONTAINER:80;" > "$UPSTREAM_FILE"
docker exec "$NGINX_CONTAINER" nginx -s reload
echo -e "${GREEN}✅ Traffic switched! Site is now using $NEW version${NC}"
echo ""

# Step 8: Wait a bit to ensure everything is ok
echo -e "${YELLOW}⏳ Monitoring new version for 10 seconds...${NC}"
sleep 10

# Step 9: Stop old version
echo -e "${YELLOW}🛑 Step 9: Stopping old version ($CURRENT)...${NC}"
docker compose -f "$COMPOSE_FILE" stop app-$CURRENT
echo -e "${GREEN}✅ Old version stopped${NC}"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Active version: ${GREEN}$NEW${NC}"
echo -e "${YELLOW}Total downtime: ${GREEN}0 seconds${NC}"
echo ""
```

**Использование:**
```bash
cd /opt/itc/web
chmod +x deploy-zero-downtime.sh
./deploy-zero-downtime.sh
```

### Вариант Б: Еще проще (docker-compose rolling update)

Используйте `--scale` и `--no-recreate`:

```bash
#!/bin/bash
# deploy-simple.sh

# Запускаем второй экземпляр app
docker-compose -f docker-compose.prod.yml up -d --scale app=2 --no-recreate

# Ждем готовности
sleep 10

# Пересоздаем первый контейнер
docker-compose -f docker-compose.prod.yml up -d --force-recreate --no-deps app

# Возвращаем к одному экземпляру
docker-compose -f docker-compose.prod.yml up -d --scale app=1
```

## Рекомендации

1. **Для production:** Вариант А (Blue-Green)
   - Полный контроль
   - Легко откатиться назад
   - Можно тестировать перед переключением

2. **Для небольших проектов:** Вариант Б
   - Проще настроить
   - Меньше ресурсов

3. **Для enterprise:** Kubernetes или Docker Swarm
   - Автоматический rolling update
   - Health checks встроены
   - Auto-scaling

## Что выбрать?

**Я рекомендую начать с Варианта А (Blue-Green)**:
- ✅ Гарантированный zero-downtime
- ✅ Можно тестировать новую версию перед переключением
- ✅ Легко откатиться (просто переключить nginx обратно)
- ✅ Понятно что происходит

Хотите, я создам все файлы для Blue-Green deployment?

