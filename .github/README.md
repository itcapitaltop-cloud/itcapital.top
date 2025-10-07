# 🚀 GitHub Actions CI/CD

Этот проект использует GitHub Actions для автоматического тестирования и развертывания.

## 📋 Workflows

### 1. **Deploy to Production** (`deploy.yml`)
- **Триггер:** Push в `main` или `master`
- **Действия:**
  - Синхронизирует код на сервер
  - Запускает `deploy-production.sh`
  - Автоматически разворачивает приложение

### 2. **Tests** (`tests.yml`)
- **Триггер:** Pull Request, Push в `develop`
- **Действия:**
  - Запускает PHPUnit тесты (PHP 8.3 и 8.4)
  - Проверяет code style (Laravel Pint)
  - Анализирует код (PHPStan)
  - Собирает frontend (npm build)

### 3. **Deploy with SQL Import** (`deploy-with-sql.yml`)
- **Триггер:** Ручной запуск (workflow_dispatch)
- **Параметры:**
  - `sql_file` - имя SQL файла (по умолчанию: itc.sql)
  - `skip_migrations` - пропустить миграции (по умолчанию: true)
- **Действия:**
  - Синхронизирует код
  - Запускает деплой с импортом SQL

## ⚙️ Настройка

### Требуемые GitHub Secrets:

| Секрет | Описание | Пример |
|--------|----------|--------|
| `SSH_PRIVATE_KEY` | Приватный SSH ключ | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | IP или домен сервера | `123.45.67.89` |
| `SERVER_USER` | SSH пользователь | `root` или `deploy` |
| `SERVER_PATH` | Путь к проекту | `/opt/itc/web` |

### Как добавить секреты:

1. GitHub → Settings → Secrets and variables → Actions
2. New repository secret
3. Добавьте каждый секрет из таблицы выше

## 🎯 Использование

### Автоматический деплой

```bash
# Просто push в main
git add .
git commit -m "feat: новая функциональность"
git push origin main
# ✅ Автоматически задеплоится
```

### Ручной деплой с SQL импортом

1. GitHub → Actions
2. Deploy with SQL Import → Run workflow
3. Введите параметры:
   - SQL file: `itc.sql`
   - Skip migrations: `true`
4. Run workflow

### Тестирование перед деплоем

```bash
# Создайте PR
git checkout -b feature/my-feature
git push origin feature/my-feature
# Создайте Pull Request на GitHub
# ✅ Автоматически запустятся тесты
```

## 📝 Полная документация

См. [../CICD_SETUP.md](../CICD_SETUP.md)

