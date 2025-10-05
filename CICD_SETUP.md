# 🚀 CI/CD Setup для IT Capital

## Обзор

Этот проект использует **GitHub Actions** для автоматического тестирования и деплоя на production сервер.

### Что делает CI/CD:

✅ **Автоматическое тестирование** при каждом push/PR
✅ **Сборка фронтенда** (npm build)
✅ **Проверка кода** (Laravel Pint, PHPStan)
✅ **Автоматический деплой** на production (при push в main/master)
✅ **Миграции БД**
✅ **Кэширование конфигурации**
✅ **Перезапуск сервисов**

---

## 📋 Шаг 1: Настройка SSH доступа

### На вашем локальном компьютере:

```bash
# Запустите скрипт генерации SSH ключа
chmod +x scripts/setup-ssh-key.sh
./scripts/setup-ssh-key.sh
```

Скрипт создаст SSH ключ и покажет вам:
1. **Private key** (приватный ключ) - для GitHub Secrets
2. **Public key** (публичный ключ) - для сервера

### На production сервере:

```bash
# Войдите на сервер
ssh your-user@your-server

# Добавьте public key в authorized_keys
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys
# Вставьте публичный ключ из шага выше
chmod 600 ~/.ssh/authorized_keys
```

---

## 📋 Шаг 2: Настройка GitHub Secrets

Перейдите в ваш репозиторий на GitHub:

**Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Добавьте следующие секреты:

| Секрет | Значение | Пример |
|--------|----------|--------|
| `SSH_PRIVATE_KEY` | Приватный SSH ключ (из скрипта) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | IP адрес или домен вашего сервера | `123.45.67.89` или `itcapital.top` |
| `SERVER_USER` | SSH пользователь на сервере | `root` или `deploy` |
| `SERVER_PATH` | Полный путь к проекту на сервере | `/var/www/itcapital.top` |

### Как добавить секрет:

1. Нажмите **"New repository secret"**
2. Name: `SSH_PRIVATE_KEY`
3. Secret: Вставьте приватный ключ **полностью** (включая `-----BEGIN` и `-----END`)
4. Нажмите **"Add secret"**
5. Повторите для остальных секретов

---

## 📋 Шаг 3: Подготовка сервера

### 3.1 Установите Docker и Docker Compose (если еще нет):

```bash
# Обновите систему
sudo apt update && sudo apt upgrade -y

# Установите Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Добавьте пользователя в группу docker
sudo usermod -aG docker $USER

# Установите Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Проверьте установку
docker --version
docker-compose --version
```

### 3.2 Создайте директорию проекта:

```bash
# Создайте директорию
sudo mkdir -p /var/www/itcapital.top
sudo chown -R $USER:$USER /var/www/itcapital.top
cd /var/www/itcapital.top
```

### 3.3 Создайте .env файл на сервере:

```bash
cd /var/www/itcapital.top
nano .env
```

Заполните переменные окружения (важно!):

```env
APP_NAME="IT Capital"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://itcapital.top

DB_CONNECTION=pgsql
DB_HOST=pgdb
DB_PORT=5432
DB_DATABASE=itcapital
DB_USERNAME=your_db_user
DB_PASSWORD=your_strong_password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=1
REVERB_APP_KEY=your_reverb_key
REVERB_APP_SECRET=your_reverb_secret
REVERB_HOST=itcapital.top
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# ... остальные переменные
```

### 3.4 Генерация ключей:

```bash
# Локально сгенерируйте ключи
php artisan key:generate --show
# Скопируйте APP_KEY в .env на сервере

# Для Reverb
echo "REVERB_APP_KEY=$(openssl rand -base64 32)"
echo "REVERB_APP_SECRET=$(openssl rand -base64 32)"
# Скопируйте в .env на сервере
```

---

## 📋 Шаг 4: Первый деплой (вручную)

```bash
# На сервере
cd /var/www/itcapital.top

# Клонируйте репозиторий (первый раз)
git clone https://github.com/YOUR_USERNAME/itcapital.top.git .

# Скопируйте .env (если еще не сделали)
cp .env.example .env
# Отредактируйте .env

# Запустите Docker контейнеры
docker-compose -f docker-compose.production.yml up -d --build

# Установите зависимости
docker exec itcapital-app composer install --no-dev --optimize-autoloader

# Запустите миграции
docker exec itcapital-app php artisan migrate --force

# Сгенерируйте ключ приложения (если нужно)
docker exec itcapital-app php artisan key:generate

# Оптимизация
docker exec itcapital-app php artisan config:cache
docker exec itcapital-app php artisan route:cache
docker exec itcapital-app php artisan view:cache

# Проверьте статус
docker-compose -f docker-compose.production.yml ps
```

---

## 📋 Шаг 5: Тестирование CI/CD

### 5.1 Push в ветку develop (только тесты):

```bash
git checkout -b develop
git add .
git commit -m "test: CI/CD setup"
git push origin develop
```

Перейдите в **GitHub → Actions** и проверьте что тесты запустились.

### 5.2 Push в main (деплой):

```bash
git checkout main
git merge develop
git push origin main
```

**GitHub Actions автоматически:**
1. Запустит тесты ✅
2. Соберет фронтенд ✅
3. Задеплоит на сервер ✅
4. Запустит миграции ✅
5. Перезапустит сервисы ✅

---

## 🔍 Мониторинг деплоя

### Смотрим логи GitHub Actions:

1. Перейдите в ваш репозиторий на GitHub
2. Вкладка **Actions**
3. Выберите последний workflow
4. Смотрите логи каждого шага

### Смотрим логи на сервере:

```bash
# Логи Docker
docker-compose -f docker-compose.production.yml logs -f

# Логи приложения
docker exec itcapital-app tail -f storage/logs/laravel.log

# Логи Reverb
docker exec itcapital-app tail -f storage/logs/reverb.log

# Статус сервисов
docker exec itcapital-app supervisorctl status
```

---

## 🛠️ Workflows

### `.github/workflows/deploy.yml`
Запускается при push в `main`/`master`:
1. Тесты
2. Сборка фронтенда
3. **Автоматический деплой на production**

### `.github/workflows/tests.yml`
Запускается при pull request:
1. Тесты на PHP 8.3 и 8.4
2. Laravel Pint (code style)
3. PHPStan (static analysis)
4. Сборка фронтенда

---

## 🔧 Ручной деплой (если нужен)

Если нужно запустить деплой вручную:

1. Перейдите в **GitHub → Actions**
2. Выберите **Deploy to Production**
3. Нажмите **Run workflow**
4. Выберите ветку (обычно `main`)
5. Нажмите **Run workflow**

---

## 🐛 Troubleshooting

### Ошибка: "Permission denied (publickey)"

**Решение:**
```bash
# На сервере проверьте права
ls -la ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Проверьте что ключ добавлен
cat ~/.ssh/authorized_keys
```

### Ошибка: "rsync: command not found"

**Решение:**
```bash
# Установите rsync на сервере
sudo apt install rsync -y
```

### Ошибка: Docker контейнеры не запускаются

**Решение:**
```bash
# На сервере
cd /var/www/itcapital.top
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml up -d --build
docker-compose -f docker-compose.production.yml logs
```

### Деплой успешен, но сайт не работает

**Решение:**
```bash
# Проверьте логи
docker exec itcapital-app tail -100 storage/logs/laravel.log

# Проверьте права на папки
docker exec itcapital-app chown -R sail:sail storage bootstrap/cache

# Очистите кэши
docker exec itcapital-app php artisan cache:clear
docker exec itcapital-app php artisan config:clear
```

---

## 📚 Дополнительные команды

```bash
# Откатить деплой (если что-то сломалось)
cd /var/www/itcapital.top
git reset --hard HEAD~1
docker-compose -f docker-compose.production.yml restart app

# Посмотреть историю деплоев
git log --oneline

# Остановить все сервисы
docker-compose -f docker-compose.production.yml down

# Обновить только код (без сборки)
git pull origin main
docker exec itcapital-app php artisan config:cache
docker-compose -f docker-compose.production.yml restart app
```

---

## ✅ Checklist перед первым деплоем

- [ ] SSH ключ сгенерирован и добавлен на сервер
- [ ] Все GitHub Secrets настроены
- [ ] Docker и Docker Compose установлены на сервере
- [ ] .env файл создан на сервере с правильными значениями
- [ ] Директория `/var/www/itcapital.top` создана
- [ ] SSL сертификаты настроены (если используете HTTPS)
- [ ] Firewall настроен (порты 80, 443 открыты)
- [ ] База данных создана
- [ ] Первый ручной деплой выполнен успешно
- [ ] Сайт открывается в браузере

---

## 🎉 Готово!

Теперь при каждом push в `main` ваш код автоматически:
- Протестируется
- Соберется
- Задеплоится на production

**Приятной разработки!** 🚀
