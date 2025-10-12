# 1. Просмотр расхождений
php artisan regular-premium:recalculation --dry-run

# 2. Исправление начислений
php artisan regular-premium:recalculation --force

# 3. Обновление рангов
php artisan users:recalc-rank

# 4. Проверка выводов (опционально)
php artisan withdrawals:handle-negative-balance --dry-run
php artisan withdrawals:handle-negative-balance --force
