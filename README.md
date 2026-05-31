# ⚠️ Важное замечание

Текущая реализация проекта содержит технический долг и примеры неудачных архитектурных решений.
Код оставлен в таком виде намеренно.

При работе с проектом **обязательно придерживайтесь следующих правил**:
- Написание тестов (Pest)
- Придерживаться PHPStan


## ⚠️ Рефакторинг

### Журнал событий

Так как в проекте нет как нормально журнала событий, было решено внедрение пакета **laravel activity log**. Все новые 
журналы и функциональность мы записываем через этот пакет.

На данный момент он внедрен:
1. В раздел **ITC стейкинг**

## Dev Container

Проект можно открыть в Dev Container, чтобы работать в едином Docker-окружении с PHP 8.4, Composer, Node.js, PostgreSQL, Redis, Nginx и Laravel Reverb.

### Требования

- Docker и Docker Compose на хосте.
- IDE с поддержкой спецификации Dev Containers.
- Локальный `.env`; если файла нет, `.devcontainer/post-create.sh` создаст его из `.env.example` без вывода значений переменных в лог.

### Запуск

1. Откройте репозиторий в IDE.
2. Выберите команду открытия проекта в контейнере.
3. Dev Container использует сервис `app` из `docker-compose.yml` и дополнительный override `.devcontainer/docker-compose.devcontainer.yml`.
4. Рабочая директория внутри контейнера: `/var/www/html`.

### Порты

- `80` — Nginx HTTP.
- `8080` — Laravel Reverb.
- `5173` — Vite dev server.
- `5432` — PostgreSQL.
- `6379` — Redis.

Если порт занят на хосте, задайте соответствующую переменную окружения перед запуском Docker Compose, например `NGINX_PORT`, `REVERB_SERVER_PORT`, `VITE_PORT` или `REDIS_PORT`.

### Команды внутри контейнера

```bash
php artisan migrate
npm run dev -- --host 0.0.0.0
php artisan test
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
npm run build
```

### Тестовая база данных

`php artisan test` принудительно использует PostgreSQL-базу `itc_testing`, заданную в `phpunit.xml`. Для тестов не используйте значения `DB_*` из локального `.env`.

По умолчанию тестовый хост в `phpunit.xml` — `pgsql`, поэтому `php artisan test` нужно запускать внутри Docker-контейнера приложения или через `docker compose exec app php artisan test`.

Если Docker-volume PostgreSQL уже был создан до появления `itc_testing`, создайте базу один раз вручную:

```bash
docker compose exec pgdb createdb -U postgres itc_testing
```

Bootstrap-скрипт выводит сообщения с префиксом `[devcontainer]`. Предупреждения с `WARN` не всегда являются фатальными: например, база данных может быть еще не готова, и миграции можно запустить вручную после старта сервисов.
