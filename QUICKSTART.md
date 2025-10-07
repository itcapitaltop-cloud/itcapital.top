# 🚀 Быстрый старт - ITC Capital Production

## Первое развертывание на сервере

```bash
# 1. Создать директорию и клонировать проект
sudo mkdir -p /opt/itc/web
sudo chown -R $USER:$USER /opt/itc/web
cd /opt/itc/web
git clone <your-repo-url> .

# 2. Настроить .env
cp .env.example .env
nano .env  # Установить production значения

# 3. Развернуть приложение
chmod +x deploy-production.sh

# Вариант A: С миграциями (чистая установка)
./deploy-production.sh

# Вариант B: С импортом существующей БД
./deploy-production.sh --import-sql --skip-migrations
```

## Варианты запуска

```bash
# 📋 Базовое развертывание
./deploy-production.sh

# 📦 С импортом SQL дампа (itc.sql)
./deploy-production.sh --import-sql --skip-migrations

# 🗄️ С другим SQL файлом
./deploy-production.sh --import-sql --sql-file backup.sql --skip-migrations

# ❓ Помощь
./deploy-production.sh --help
```

## Частые команды

```bash
# Посмотреть логи
docker logs -f itcapital-app

# Войти в контейнер
docker exec -it itcapital-app bash

# Перезапустить
docker-compose -f docker-compose.prod.yml restart

# Остановить
docker-compose -f docker-compose.prod.yml stop

# Запустить
docker-compose -f docker-compose.prod.yml up -d

# Очистить кэш Laravel
docker exec itcapital-app php artisan cache:clear
docker exec itcapital-app php artisan config:clear

# Выполнить artisan команду
docker exec itcapital-app php artisan <команда>
```

## Обновление приложения

```bash
cd /opt/itc/web
git pull origin main
./deploy-production.sh
```

## Резервное копирование

```bash
# Создать backup
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
docker exec itcapital-pgdb pg_dump -U "$DB_USER" "$DB_NAME" > backup_$(date +%Y%m%d_%H%M%S).sql

# Восстановить backup
./deploy-production.sh --import-sql --sql-file backup_20250107.sql --skip-migrations
```

## Решение проблем

### Проблема с правами на storage/logs

```bash
cd /opt/itc/web
docker-compose -f docker-compose.prod.yml down -v
rm -rf bootstrap/cache/*.php
chmod -R 775 storage bootstrap/cache
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

### Проверить статус контейнеров

```bash
docker-compose -f docker-compose.prod.yml ps
docker stats
```

### Посмотреть ошибки

```bash
# Логи приложения
docker logs --tail=100 itcapital-app

# Логи Laravel
docker exec itcapital-app cat storage/logs/laravel.log

# Логи Reverb
docker exec itcapital-app cat storage/logs/reverb.log
```

## Структура проекта

```
/opt/itc/web/               # Корень проекта
├── docker-compose.prod.yml # Production конфигурация
├── deploy-production.sh    # Скрипт развертывания
├── itc.sql                 # SQL дамп
├── storage/                # Логи и кэш
└── .env                    # Конфигурация окружения
```

## Переменные окружения (.env)

Ключевые настройки для production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://itcapital.top

DB_CONNECTION=pgsql
DB_HOST=pgdb
DB_PORT=5432
DB_DATABASE=itcdb
DB_USERNAME=itcuser
DB_PASSWORD=<secure-password>

REDIS_HOST=redis
REDIS_PORT=6379
```

## Порты

- `8000` - Приложение Laravel
- `80` - Nginx
- `8080` - Reverb WebSocket
- `5432` - PostgreSQL
- `6379` - Redis

## Полная документация

Смотрите [DEPLOYMENT.md](./DEPLOYMENT.md) для подробной информации.

