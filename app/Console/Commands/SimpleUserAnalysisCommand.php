<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SimpleUserAnalysisCommand extends Command
{
    protected $signature = 'user:simple-analysis {user-ids* : ID пользователей для анализа}';

    protected $description = 'Простой анализ пользователей по ID';

    public function handle(): int
    {
        $userIds = $this->argument('user-ids');

        $this->info("=== ПРОСТОЙ АНАЛИЗ ПОЛЬЗОВАТЕЛЕЙ ===");

        foreach ($userIds as $userId) {
            $this->analyzeUser(intval($userId));
        }

        return self::SUCCESS;
    }

    private function analyzeUser(int $userId): void
    {
        $this->info("\n" . str_repeat("-", 50));
        $this->info("АНАЛИЗ ПОЛЬЗОВАТЕЛЯ ID: {$userId}");
        $this->info(str_repeat("-", 50));

        try {
            $user = User::find($userId);

            if (!$user) {
                $this->error("❌ Пользователь с ID {$userId} НЕ НАЙДЕН");
                return;
            }

            $this->info("✅ Пользователь найден:");
            $this->info("  - Username: {$user->username}");
            $this->info("  - Email: {$user->email}");
            $this->info("  - Ранг: {$user->rank}");
            $this->info("  - Статус: " . ($user->banned_at ? "ЗАБЛОКИРОВАН ({$user->banned_at})" : "АКТИВЕН"));
            $this->info("  - Дата регистрации: {$user->created_at->format('Y-m-d H:i:s')}");

            // Проверяем участие в регулярной премии
            $this->checkRegularPremium($user);

            // Проверяем партнерскую структуру
            $this->checkPartnerStructure($user);

        } catch (\Exception $e) {
            $this->error("❌ Ошибка при анализе пользователя ID {$userId}: " . $e->getMessage());
        }
    }

    private function checkRegularPremium(User $user): void
    {
        try {
            // Проверяем, получал ли регулярную премию
            $regularPremiumTransactions = \App\Models\Transaction::where('user_id', $user->id)
                ->where('trx_type', \App\Enums\Transactions\TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
                ->count();

            if ($regularPremiumTransactions > 0) {
                $totalAmount = \App\Models\Transaction::where('user_id', $user->id)
                    ->where('trx_type', \App\Enums\Transactions\TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
                    ->sum('amount');

                $this->info("  - ✅ Получал регулярную премию: {$totalAmount} ITC ({$regularPremiumTransactions} транзакций)");
            } else {
                $this->info("  - ❌ НЕ получал регулярную премию");
            }

        } catch (\Exception $e) {
            $this->warn("  - ⚠️ Не удалось проверить регулярную премию: " . $e->getMessage());
        }
    }

    private function checkPartnerStructure(User $user): void
    {
        try {
            // Проверяем, является ли предком
            $asAncestor = \App\Models\PartnerClosure::where('ancestor_id', $user->id)->exists();

            // Проверяем, является ли потомком
            $asDescendant = \App\Models\PartnerClosure::where('descendant_id', $user->id)->exists();

            if ($asAncestor) {
                $descendantsCount = \App\Models\PartnerClosure::where('ancestor_id', $user->id)->count();
                $this->info("  - ✅ Является предком ({$descendantsCount} потомков)");
            }

            if ($asDescendant) {
                $ancestorsCount = \App\Models\PartnerClosure::where('descendant_id', $user->id)->count();
                $this->info("  - ✅ Является потомком ({$ancestorsCount} предков)");
            }

            if (!$asAncestor && !$asDescendant) {
                $this->info("  - ❌ НЕ участвует в партнерской структуре");
            }

        } catch (\Exception $e) {
            $this->warn("  - ⚠️ Не удалось проверить партнерскую структуру: " . $e->getMessage());
        }
    }
}
