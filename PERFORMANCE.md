# 🚀 Оптимизация производительности

## Текущее состояние

✅ **Уже настроено:**
- Laravel кеширование (config, routes, views, events)
- Redis контейнер
- OPcache (после следующего деплоя)

## 🔧 Обязательные настройки в .env

Добавьте эти строки в ваш `.env` файл на сервере:

```bash
# Включите production режим
APP_ENV=production
APP_DEBUG=false

# Используйте Redis для кеша и сессий
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis настройки
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Оптимизация
DEBUGBAR_ENABLED=false
```

## 📊 Почему страницы грузятся медленно?

### 1. **Сложные запросы без кеширования**
Найдено много страниц с тяжелыми запросами:
- `Dashboard/Index.php` - множественные JOIN и подзапросы
- `Packages.php` - 8+ withSum запросов на каждую загрузку

**Решение:**
```php
// ❌ ПЛОХО - запрос каждый раз
$packages = ItcPackage::query()
    ->withSum('profits', 'amount')
    ->withSum('reinvestProfits', 'amount')
    ->get();

// ✅ ХОРОШО - кеширование на 5 минут
$packages = Cache::remember(
    'user_packages_' . Auth::id(),
    now()->addMinutes(5),
    fn() => ItcPackage::query()
        ->withSum('profits', 'amount')
        ->withSum('reinvestProfits', 'amount')
        ->get()
);
```

### 2. **OPcache не был включен**
✅ **ИСПРАВЛЕНО** - добавлен в `php.ini`

После следующего деплоя PHP код будет кешироваться в памяти = **2-10x быстрее**!

### 3. **APP_DEBUG=true замедляет**
Если `APP_DEBUG=true` в production:
- Laravel собирает все SQL запросы в память
- Генерирует большие stack traces
- Показывает DebugBar

**Решение:** Убедитесь что `APP_DEBUG=false` в production `.env`

## 🎯 Быстрые исправления

### 1. Проверьте .env на сервере

```bash
# На сервере
ssh -i ~/.ssh/github_deploy_itc -p 49350 root@f7607ded73fb.vps.myjino.ru
cd /opt/itc/web

# Проверьте настройки
grep "APP_DEBUG" .env
grep "CACHE_DRIVER" .env
grep "REDIS_HOST" .env
```

### 2. Добавьте кеширование в тяжелые страницы

Файлы для оптимизации:
- `app/Livewire/Account/Dashboard/Index.php` - много запросов
- `app/Livewire/Account/Itc/Packages.php` - 8 withSum
- `app/Livewire/Account/CommonFund/PackagesList.php` - сложные JOIN

### 3. После деплоя сбросьте OPcache

```bash
# Пересоберите контейнер чтобы применить новый php.ini
docker-compose -f docker-compose.prod.yml build --no-cache app
docker-compose -f docker-compose.prod.yml up -d

# Проверьте что OPcache работает
docker exec itcapital-app php -i | grep opcache.enable
```

## 📈 Ожидаемый результат

После применения всех оптимизаций:
- **OPcache:** 2-5x быстрее загрузка PHP
- **Redis кеш:** 10-50x быстрее повторные запросы
- **APP_DEBUG=false:** 1.5-2x быстрее все операции
- **Кеширование запросов:** 5-10x быстрее дашборды

## 🔍 Диагностика

### Проверить медленные запросы:

```bash
# Включите query log временно
# config/database.php
'connections' => [
    'pgsql' => [
        // ...
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],
    ],
],

# Или используйте Laravel Telescope для мониторинга
composer require laravel/telescope --dev
php artisan telescope:install
```

### Проверить что Redis работает:

```bash
docker exec itcapital-app php artisan tinker

# В tinker:
Cache::put('test', 'works', 60);
Cache::get('test'); // должно вернуть 'works'
```

## 🚨 Частые проблемы

1. **Redis не подключен** → `CACHE_DRIVER=redis` в .env
2. **OPcache не работает** → пересоберите Docker образ
3. **Кеш не очищается** → `php artisan cache:clear` после изменений
4. **Сессии медленные** → `SESSION_DRIVER=redis`

## 📝 Чеклист оптимизации

- [ ] APP_DEBUG=false в .env
- [ ] APP_ENV=production в .env
- [ ] CACHE_DRIVER=redis
- [ ] SESSION_DRIVER=redis
- [ ] OPcache включен (пересобрать Docker)
- [ ] Кеширование в Dashboard/Index.php
- [ ] Кеширование в Packages.php
- [ ] Laravel кеши обновлены (config:cache, route:cache)

