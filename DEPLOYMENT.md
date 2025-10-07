# Инструкция по развертыванию на production сервере

## Требования

- Docker и Docker Compose установлены на сервере
- Доступ к серверу по SSH
- Git установлен на сервере

## Путь на сервере

Приложение разворачивается в: `/opt/itc/web`

## Шаги развертывания

### 1. Подготовка сервера

```bash
# Подключиться к серверу
ssh user@your-server

# Создать директорию для проекта
sudo mkdir -p /opt/itc/web
sudo chown -R $USER:$USER /opt/itc/web

# Перейти в директорию
cd /opt/itc/web
```

### 2. Клонирование проекта

```bash
# Клонировать репозиторий
git clone <your-repository-url> .

# Или если репозиторий уже клонирован
git pull origin main
```

### 3. Настройка окружения

```bash
# Скопировать .env файл
cp .env.example .env

# Отредактировать .env для production
nano .env
```

Важные переменные в `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://itcapital.top

DB_CONNECTION=pgsql
DB_HOST=pgdb
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=redis
REDIS_PORT=6379

# Генерировать новый ключ
APP_KEY=
```

### 4. Создание необходимых директорий

```bash
# Создать директории storage и cache
mkdir -p storage/logs
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache

# Установить права
chmod -R 775 storage bootstrap/cache
```

### 5. Запуск Docker

```bash
# Пересобрать и запустить контейнеры
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d

# Проверить статус
docker-compose -f docker-compose.prod.yml ps
```

### 6. Инициализация приложения (автоматически)

```bash
# Базовое развертывание (с миграциями)
chmod +x deploy-production.sh
./deploy-production.sh

# ИЛИ развертывание с импортом SQL дампа
./deploy-production.sh --import-sql --skip-migrations

# ИЛИ использовать другой SQL файл
./deploy-production.sh --import-sql --sql-file dump.sql --skip-migrations

# Посмотреть все опции
./deploy-production.sh --help
```

#### Ручная инициализация (если нужно)

```bash
# Войти в контейнер
docker exec -it itcapital-app bash

# Внутри контейнера выполнить:
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Выйти из контейнера
exit
```

### 7. Проверка работоспособности

```bash
# Проверить логи
docker logs itcapital-app

# Проверить доступность приложения
curl http://localhost:8000

# Проверить работу Reverb
docker exec itcapital-app supervisorctl status reverb
```

## Обновление приложения

### Автоматическое обновление

```bash
cd /opt/itc/web
git pull origin main
./deploy-production.sh
```

### Ручное обновление

```bash
cd /opt/itc/web
git pull origin main
docker-compose -f docker-compose.prod.yml build app
docker-compose -f docker-compose.prod.yml up -d
docker exec itcapital-app php artisan migrate --force
docker exec itcapital-app php artisan config:cache
docker exec itcapital-app php artisan route:cache
docker exec itcapital-app php artisan view:cache
```

## Остановка контейнеров

```bash
# Остановить без удаления данных
docker-compose -f docker-compose.prod.yml stop

# Остановить и удалить контейнеры (данные БД сохранятся)
docker-compose -f docker-compose.prod.yml down

# Полная очистка (ВНИМАНИЕ: удалит данные БД!)
docker-compose -f docker-compose.prod.yml down -v
```

## Полезные команды

```bash
# Просмотр логов в реальном времени
docker logs -f itcapital-app

# Выполнить artisan команду
docker exec itcapital-app php artisan <command>

# Войти в контейнер
docker exec -it itcapital-app bash

# Перезапустить контейнер
docker-compose -f docker-compose.prod.yml restart app

# Очистить кэш Laravel
docker exec itcapital-app php artisan cache:clear
docker exec itcapital-app php artisan config:clear
docker exec itcapital-app php artisan view:clear
docker exec itcapital-app php artisan route:clear
```

## Резервное копирование

### Создание резервной копии

```bash
# Backup базы данных
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
docker exec itcapital-pgdb pg_dump -U "$DB_USER" "$DB_NAME" > backup_$(date +%Y%m%d_%H%M%S).sql

# Или использовать переменные напрямую
docker exec itcapital-pgdb pg_dump -U itcuser itcdb > itc_backup.sql
```

### Восстановление базы данных

```bash
# Восстановление через скрипт (рекомендуется)
./deploy-production.sh --import-sql --sql-file backup_20250107.sql --skip-migrations

# Или вручную
docker exec -i itcapital-pgdb psql -U your_username your_database < backup_file.sql
```

## Мониторинг

```bash
# Проверить использование ресурсов
docker stats

# Проверить здоровье контейнеров
docker-compose -f docker-compose.prod.yml ps
```

