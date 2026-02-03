<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\PartnerClosure;
use App\Models\PartnerReward;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StartBonusAccrualService implements StartBonusAccrualContract
{
    private const int MAX_DEPTH = 20;

    private const int CHUNK_SIZE = 100;

    private const int EXTENDED_LINE_START = 6;

    /**
     * @throws \Throwable
     */
    public function accrue(int $buyerId, float $packageAmount): void
    {
        // Получаем всех предков за один запрос с нужными связями
        $ancestors = $this->getAncestorsWithPercents($buyerId);

        if ($ancestors->isEmpty()) {
            return;
        }

        $this->processAccruals($ancestors, $buyerId, $packageAmount);
    }

    private function getAncestorsWithPercents(int $buyerId): Collection
    {
        return PartnerClosure::query()
            ->select([
                'partner_closures.ancestor_id',
                'partner_closures.depth as line',
                'users.rank',
                'users.extended_lines',
            ])
            ->join('users', 'users.id', '=', 'partner_closures.ancestor_id')
            ->where('partner_closures.descendant_id', $buyerId)
            ->whereBetween('partner_closures.depth', [1, self::MAX_DEPTH])
            ->orderBy('partner_closures.depth')
            ->get()
            ->map(function ($row) {
                // Предзагружаем процент для каждого предка
                $row->percent = $this->calculatePercent(
                    $row->ancestor_id,
                    $row->rank,
                    $row->line,
                    $row->extended_lines
                );

                return $row;
            })
            ->filter(fn ($row) => $row->percent > 0);
    }

    private function calculatePercent(
        int $ancestorId,
        ?int $level,
        int $line,
        bool $hasExtendedLines
    ): float {
        // Проверка расширенных линий
        if ($line >= self::EXTENDED_LINE_START && ! $hasExtendedLines) {
            return 0;
        }

        // Кешируем запросы процентов
        static $percentCache = [];
        static $overrideCache = [];

        // Проверяем override
        $overrideKey = "{$ancestorId}:{$level}:{$line}";

        if (! array_key_exists($overrideKey, $overrideCache)) {
            $overrideCache[$overrideKey] = DB::table('user_level_percent_overrides')
                ->where('user_id', $ancestorId)
                ->where('partner_level_id', $level)
                ->where('bonus_type', 'start')
                ->where('line', $line)
                ->value('percent');
        }

        if (! is_null($overrideCache[$overrideKey])) {
            return (float) $overrideCache[$overrideKey];
        }

        // Стандартный процент
        $percentKey = "{$level}:{$line}";

        if (! array_key_exists($percentKey, $percentCache)) {
            $percentCache[$percentKey] = DB::table('partner_level_percents')
                ->where('partner_level_id', $level)
                ->where('bonus_type', 'start')
                ->where('line', $line)
                ->value('percent') ?? 0;
        }

        return (float) $percentCache[$percentKey];
    }

    /**
     * @throws \Throwable
     */
    private function processAccruals(
        Collection $ancestors,
        int $buyerId,
        float $packageAmount
    ): void {
        // Группируем для bulk insert
        $transactions = [];
        $rewards = [];
        $notifications = [];

        foreach ($ancestors as $ancestor) {
            $reward = round($packageAmount * $ancestor->percent / 100, 2);

            if ($reward <= 0) {
                continue;
            }

            $uuid = 'SB-' . Str::random(10);
            $now = now();

            $transactions[] = [
                'uuid' => $uuid,
                'user_id' => $ancestor->ancestor_id,
                'amount' => $reward,
                'trx_type' => TrxTypeEnum::START_BONUS_ACCRUAL,
                'balance_type' => BalanceTypeEnum::PARTNER,
                'accepted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $rewards[] = [
                'uuid' => $uuid,
                'from_user_id' => $buyerId,
                'reward_type' => PartnerRewardTypeEnum::START->value,
                'line' => $ancestor->line,
                'amount' => $reward,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $notifications[] = [
                'user_id' => $ancestor->ancestor_id,
                'amount' => $reward,
            ];
        }

        if (empty($transactions)) {
            return;
        }

        // Одна транзакция для всех вставок
        DB::transaction(function () use ($transactions, $rewards, $notifications) {
            // Bulk insert для производительности
            foreach (array_chunk($transactions, self::CHUNK_SIZE) as $chunk) {
                Transaction::insert($chunk);
            }

            foreach (array_chunk($rewards, self::CHUNK_SIZE) as $chunk) {
                PartnerReward::insert($chunk);
            }

            // Отправляем уведомления после успешной транзакции
            $this->sendNotifications($notifications);
        });
    }

    private function sendNotifications(array $notifications): void
    {
        // Загружаем всех пользователей одним запросом
        $userIds = array_column($notifications, 'user_id');
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($notifications as $notification) {
            if ($user = $users->get($notification['user_id'])) {
                Notify::bonusStart($user, $notification['amount']);
            }
        }
    }
}
