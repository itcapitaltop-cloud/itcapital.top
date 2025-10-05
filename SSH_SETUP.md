# 🔐 Настройка SSH для GitHub Actions (Упрощенная версия)

## У вас уже есть SSH доступ к серверу

Отлично! Мы будем использовать ваши существующие учетные данные.

---

## 📋 Шаг 1: Получите приватный SSH ключ

### Вариант А: Если ключ на вашем локальном компьютере

```bash
# Найдите ваш приватный ключ (обычно это один из этих файлов)
ls -la ~/.ssh/

# Обычно это:
# - ~/.ssh/id_rsa (RSA ключ)
# - ~/.ssh/id_ed25519 (ED25519 ключ, более современный)
# - ~/.ssh/id_ecdsa (ECDSA ключ)

# Посмотрите содержимое (НЕ делитесь этим с другими!)
cat ~/.ssh/id_rsa
# или
cat ~/.ssh/id_ed25519
```

Скопируйте **весь вывод** включая строки:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...содержимое...
-----END OPENSSH PRIVATE KEY-----
```

### Вариант Б: Создать новый ключ специально для GitHub Actions

```bash
# Создайте новый ключ (более безопасно)
ssh-keygen -t ed25519 -C "github-actions@itcapital.top" -f ~/.ssh/github_actions_deploy

# Добавьте публичный ключ на сервер
ssh-copy-id -i ~/.ssh/github_actions_deploy.pub YOUR_USER@YOUR_SERVER

# Или вручную:
cat ~/.ssh/github_actions_deploy.pub | ssh YOUR_USER@YOUR_SERVER 'cat >> ~/.ssh/authorized_keys'

# Скопируйте приватный ключ
cat ~/.ssh/github_actions_deploy
```

---

## 📋 Шаг 2: Добавьте секреты в GitHub

Перейдите на GitHub:

**Ваш репозиторий** → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

### Добавьте 4 секрета:

#### 1. SSH_PRIVATE_KEY
```
Вставьте ПОЛНОСТЬЮ содержимое приватного ключа из Шага 1
Включая -----BEGIN и -----END строки
```

#### 2. SERVER_HOST
```
IP адрес вашего сервера или домен
Например: 123.45.67.89
Или: itcapital.top
```

#### 3. SERVER_USER
```
Имя пользователя SSH (тот, под которым вы подключаетесь)
Например: root
Или: deploy
Или: ubuntu
```

#### 4. SERVER_PATH
```
Полный путь к проекту на сервере
Например: /var/www/itcapital.top
Или: /home/deploy/itcapital.top
```

### Пример добавления секрета:

1. Нажмите **"New repository secret"**
2. **Name**: `SSH_PRIVATE_KEY`
3. **Secret**: Вставьте весь приватный ключ
4. Нажмите **"Add secret"**
5. Повторите для SERVER_HOST, SERVER_USER, SERVER_PATH

---

## 📋 Шаг 3: Проверьте SSH доступ

На вашем локальном компьютере:

```bash
# Проверьте что вы можете подключиться к серверу
ssh YOUR_USER@YOUR_SERVER

# Если подключение успешно, проверьте путь к проекту
ls -la /var/www/itcapital.top

# Проверьте что Docker работает
docker ps

# Проверьте что docker-compose работает
cd /var/www/itcapital.top
docker-compose -f docker-compose.production.yml ps
```

Если все команды работают - отлично! GitHub Actions сможет подключиться.

---

## 📋 Шаг 4: Первый деплой

### Подготовьте сервер (если еще не сделали):

```bash
# Подключитесь к серверу
ssh YOUR_USER@YOUR_SERVER

# Создайте директорию для проекта
sudo mkdir -p /var/www/itcapital.top
sudo chown -R $USER:$USER /var/www/itcapital.top

# Клонируйте репозиторий (первый раз)
cd /var/www/itcapital.top
git clone https://github.com/YOUR_USERNAME/itcapital.top.git .

# Создайте .env файл
cp .env.example .env
nano .env
# Заполните все переменные

# Первый запуск
docker-compose -f docker-compose.production.yml up -d --build
docker exec itcapital-app composer install --no-dev --optimize-autoloader
docker exec itcapital-app php artisan key:generate
docker exec itcapital-app php artisan migrate --force
```

---

## 🧪 Шаг 5: Протестируйте CI/CD

```bash
# На вашем локальном компьютере
git add .
git commit -m "feat: setup CI/CD"
git push origin main
```

Перейдите на **GitHub → Actions** и смотрите как работает деплой!

---

## ✅ Краткий чеклист

- [ ] Нашел приватный SSH ключ (`cat ~/.ssh/id_rsa`)
- [ ] Добавил `SSH_PRIVATE_KEY` в GitHub Secrets
- [ ] Добавил `SERVER_HOST` в GitHub Secrets (IP или домен)
- [ ] Добавил `SERVER_USER` в GitHub Secrets (ваш SSH юзер)
- [ ] Добавил `SERVER_PATH` в GitHub Secrets (`/var/www/itcapital.top`)
- [ ] Проверил что могу подключиться: `ssh USER@SERVER`
- [ ] На сервере есть Docker и docker-compose
- [ ] На сервере создана директория проекта
- [ ] На сервере есть .env файл с правильными настройками
- [ ] Выполнил первый ручной деплой
- [ ] Сделал push в main и проверил GitHub Actions

---

## 🆘 Возможные проблемы

### "Permission denied (publickey)"

```bash
# На сервере проверьте:
ls -la ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Убедитесь что ваш публичный ключ там есть:
cat ~/.ssh/authorized_keys
```

### "Host key verification failed"

Это нормально при первом подключении. GitHub Actions автоматически добавит хост в known_hosts.

### Ошибка "docker: command not found"

```bash
# Установите Docker на сервере
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

### Тест деплоя без GitHub Actions

```bash
# Локально протестируйте команды деплоя:
ssh YOUR_USER@YOUR_SERVER << 'EOF'
cd /var/www/itcapital.top
docker exec itcapital-app php artisan --version
docker exec itcapital-app supervisorctl status
EOF
```

---

## 🎉 Готово!

Теперь при каждом push в `main` код автоматически задеплоится на сервер!

**Нужна помощь?** Смотрите полную документацию в `CICD_SETUP.md`
