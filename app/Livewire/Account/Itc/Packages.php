<?php

namespace App\Livewire\Account\Itc;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\NotificationProfitReaded;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitWithdraw;
use App\Models\PackageProfitWithReinvestLink;
use App\Models\ReinvestToPackageBody;
use App\Models\Transaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Support\Str;
use RuntimeException;

class Packages extends Component
{
    #[Validate(['required', 'numeric', 'min:100'])]
    public string $amount = '';
    public PackageTypeEnum $packageType = PackageTypeEnum::STANDARD;
    public int             $duration    = 1;
    public float           $percent     = 8.2;

    public string $withdrawPackageAmount = '';

    public function boot(): void {
        Validator::extend('max_package_sum', function ($attribute, $value, $params) {
            $uuid = $params[0] ?? null;

            if (! $uuid) {
                return false;
            }

            $package = ItcPackage::where('uuid', $uuid)->first();

            if (! $package) {
                return false;
            }

            $val = (float) str_replace([' ', ','], ['', '.'], $value);

            $withdrawn = $package->balanceWithdraws()->sum('amount');

            $maxAvailable = $package->transaction->amount - $withdrawn;

            return $val <= $maxAvailable;
        });
    }

    protected function rules(): array
    {
        return [
            'packageType' => ['required', Rule::enum(PackageTypeEnum::class)],
            'duration'    => ['required_if:packageType,present', 'in:1,3,6,12'],
//            'amount'      => ['required_if:packageType,standard,privilege,vip', 'numeric', 'min:1'],
            'percent'     => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function withdrawPackageBalance(
        string $uuid,
        TransactionRepositoryContract $transactionRepo
    ): void
    {
        $this->validateOnly('withdrawPackageAmount', [
            'withdrawPackageAmount' => 'required|numeric|min:1|max_package_sum:' . $uuid,
        ]);

        $transaction = $transactionRepo->commonStore(
            new CreateTransactionDto(
                userId:      Auth::id(),
                trxType:     TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
                balanceType: BalanceTypeEnum::MAIN,
                amount:      $this->withdrawPackageAmount,
                acceptedAt:  now(),
                prefix:      'WPB-',
            )
        );

        PackageBalanceWithdraw::query()->create([
            'uuid'         => $transaction->uuid,
            'package_uuid' => $uuid,
        ]);

        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->withSum('partnerTransfers', 'amount')
            ->withSum('balanceWithdraws', 'amount')
            ->withSum('reinvestToBody', 'amount')
            ->first();

        if ($package && $package->transaction->amount
                + ($package->partner_transfers_sum_amount ?? 0)
                + ($package->reinvest_to_body_sum_amount ?? 0)
                - ($package->balance_withdraws_sum_amount ?? 0)
            < 100) {
            $package->month_profit_percent = 0;

            Log::channel('source')->debug($package->month_profit_percent);

            $package->save();
        }

        $this->reset('withdrawPackageAmount');
        $this->dispatch('balance-edited');

        $this->dispatch(
            'new-system-notification',
            type   : 'success',
            message: __('livewire_itc_packages_withdraw_success')
        );
    }

    public function continuePackageWork(string $uuid): void
    {
        $package = ItcPackage::query()
            ->where('uuid', $uuid)
            ->whereRelation('transaction', 'user_id', Auth::id())
            ->firstOrFail();

        if (! $package->work_to->isPast()) {
            return;
        }

        DB::transaction(function () use ($package) {
            // 1) Все активные реинвесты (без withdraw) — их будем удалять и переносить сумму в тело пакета
            $activeReinvests = PackageProfitReinvest::query()
                ->where('package_uuid', $package->uuid)
                ->whereDoesntHave('withdraw')
                ->with('profitLink')
                ->orderBy('created_at')
                ->orderBy('uuid')
                ->get(['id', 'uuid', 'amount', 'created_at']);

            if ($activeReinvests->isEmpty()) {

                if (
                    !PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->exists() &&
                    !PackageProfitWithdraw::query()->where('package_uuid', $package->uuid)->exists()
                ) {
                    $allProfitUuids = PackageProfit::query()
                        ->where('package_uuid', $package->uuid)
                        ->pluck('uuid')
                        ->all();

                    if (!empty($allProfitUuids)) {
                        $sumAll = BigDecimal::of((string) PackageProfit::query()
                            ->whereIn('uuid', $allProfitUuids)
                            ->selectRaw('COALESCE(SUM(amount),0) AS total')
                            ->value('total'));

                        PackageProfit::query()
                            ->whereIn('uuid', $allProfitUuids)
                            ->get()
                            ->each
                            ->delete();

                        ReinvestToPackageBody::create([
                            'uuid'         => (string) Str::uuid(),
                            'package_uuid' => $package->uuid,
                            'amount'       => (string) $sumAll,
                        ]);
                    }
                }

                $package->update([
                    'work_to' => now()->addMonths(6),
                    'month_profit_percent' => 8.2,
                ]);

                $this->dispatch(
                    'new-system-notification',
                    type   : 'success',
                    message: __('livewire_itc_packages_package_extended')
                );

                return;
            }

            $activeUuids = $activeReinvests->pluck('uuid')->all();
            $totalReinvested = $activeReinvests->reduce(
                fn($c, $r) => BigDecimal::of((string)$c)->plus((string)$r->amount),
                BigDecimal::of('0')
            );

            // 2) Лента событий (все «живые» реинвесты + выводы дивидендов), слева-направо
            $reinvestEvents = PackageProfitReinvest::query()
                ->where('package_uuid', $package->uuid)
                ->orderBy('created_at')
                ->orderBy('uuid')
                ->get(['uuid', 'amount', 'created_at']);

            $withdrawEvents = PackageProfitWithdraw::query()
                ->where('package_uuid', $package->uuid)
                ->join('transactions as t', 'package_profit_withdraws.uuid', '=', 't.uuid')
                ->orderBy('t.created_at')
                ->orderBy('t.uuid')
                ->get(['t.uuid', 't.amount', 't.created_at']);

            $events = collect();
            foreach ($reinvestEvents as $e) {
                $events->push([
                    'type'   => 'reinvest',
                    'uuid'   => $e->uuid,
                    'at'     => $e->created_at,
                    'amount' => BigDecimal::of((string)$e->amount),
                ]);
            }
            foreach ($withdrawEvents as $e) {
                $events->push([
                    'type'   => 'withdraw',
                    'uuid'   => $e->uuid,
                    'at'     => $e->created_at,
                    'amount' => BigDecimal::of((string)$e->amount),
                ]);
            }
            $events = $events->sortBy([['at', 'asc'], ['uuid', 'asc']])->values();

            // 3) Восстанавливаем корзины: события слева->направо, профиты берём хвостом справа->налево
            $consumed = [];                 // UUID профитов, уже «съеденные» прошлыми событиями
            $removeProfitUuids = [];        // UUID профитов, которые относятся к активным реинвестам (их удалим)

            $pickTail = function (BigDecimal $need, $eventAt, array $exclude) use ($package) {
                $picked = [];
                $sum = BigDecimal::of('0');

                $profits = PackageProfit::query()
                    ->where('package_uuid', $package->uuid)
                    ->where('created_at', '<=', $eventAt)
                    ->when(!empty($exclude), fn ($q) => $q->whereNotIn('uuid', $exclude))
                    ->orderBy('created_at', 'desc') // хвост справа-налево (ближайшие к событию)
                    ->orderBy('uuid', 'desc')
                    ->get(['uuid', 'amount', 'created_at']);

                foreach ($profits as $p) {
                    $a = BigDecimal::of((string)$p->amount);

                    // Непрерывный хвост: если следующий элемент перепрыгивает сумму — хвост не складывается
                    if ($sum->plus($a)->compareTo($need) > 0) {
                        break;
                    }

                    $sum = $sum->plus($a);
                    $picked[] = $p->uuid;

                    if ($sum->compareTo($need) === 0) {
                        return $picked; // точное совпадение
                    }
                }

                return null; // не сложилось ровно
            };

            $profitsByActiveReinvest = [];

            foreach ($events as $ev) {
                $picked = $pickTail($ev['amount'], $ev['at'], $consumed);

                if ($picked === null) {
                    throw new RuntimeException(
                        "Не удалось сопоставить событие {$ev['uuid']} с дивидендами (package {$package->uuid})"
                    );
                }

                // Если это активный реинвест — на удаление именно его дивиденды
                if ($ev['type'] === 'reinvest' && in_array($ev['uuid'], $activeUuids, true)) {
                    $removeProfitUuids = array_merge($removeProfitUuids, $picked);
                }

                if ($ev['type'] === 'reinvest' && in_array($ev['uuid'], $activeUuids, true)) {
                    $profitsByActiveReinvest[$ev['uuid']] = $picked;
                }

                // В любом случае помечаем эти дивиденды как «съеденные»
                $consumed = array_merge($consumed, $picked);
            }

//            $report = [];
//            foreach ($profitsByActiveReinvest as $rid => $uuids) {
//                $report[$rid] = [
//                    'sum'     => PackageProfit::query()
//                        ->whereIn('uuid', $uuids)
//                        ->selectRaw('COALESCE(SUM(amount),0) AS total')
//                        ->value('total'),
//                    'profits' => $uuids,
//                ];
//            }
//            Log::channel('source')->debug($report);

            // 4) Удаляем ровно эти дивиденды и активные реинвесты
            if (! empty($removeProfitUuids)) {
                PackageProfit::query()
                    ->whereIn('uuid', $removeProfitUuids)
                    ->get()
                    ->each
                    ->delete();
            }

            $activeReinvests->each(function ($reinvest) {

                if ($reinvest->profitLink) {
                    $reinvest->profitLink()->delete();
                }
                // затем удалить сам реинвест
//                Log::channel('source')->debug($reinvest);
                $reinvest->delete();
//                Log::channel('source')->debug('deleted');
            });



            $freeProfitUuids = PackageProfit::query()
                ->where('package_uuid', $package->uuid)
                ->whereNotIn('uuid', $consumed)
                ->pluck('uuid')
                ->all();

            if (!empty($freeProfitUuids)) {
                $freeSum = BigDecimal::of((string) PackageProfit::query()
                    ->whereIn('uuid', $freeProfitUuids)
                    ->selectRaw('COALESCE(SUM(amount),0) AS total')
                    ->value('total'));

//                Log::channel('source')->debug($freeSum);
                PackageProfit::query()
                    ->whereIn('uuid', $freeProfitUuids)
                    ->get()
                    ->each
                    ->delete();

                // прибавляем к сумме, которая пойдёт в тело пакета
                $totalReinvested = $totalReinvested->plus($freeSum);
            }

//            // 5) Фиксируем пополнение тела пакета на сумму активных реинвестов (модель без транзакций)
            if ($totalReinvested->isPositive()) {
                ReinvestToPackageBody::create([
                    'uuid'         => (string) Str::uuid(),
                    'package_uuid' => $package->uuid,
                    'amount'       => (string) $totalReinvested,
                ]);
            }

            // 6) Продлеваем пакет
            $package->update([
                'work_to' => now()->addMonths(6),
                'month_profit_percent' => 8.2,
            ]);
        });

        $this->markReinvestNotificationsAsRead($uuid);

        $this->dispatch(
            'new-system-notification',
            type   : 'success',
            message: __('livewire_itc_packages_package_extended_with_reinvest')
        );
    }

    public function buyPackage(TransactionRepositoryContract $transactionRepo): void
    {
        $this->validate();

        $transactionRepo->checkBalanceAndStore(new CreateTransactionDto(
            userId: Auth::id(),
            trxType: TrxTypeEnum::BUY_PACKAGE,
            balanceType: BalanceTypeEnum::MAIN,
            amount: $this->amount,
            acceptedAt: Carbon::now(),
            prefix: 'ITC-',
        ), function (Transaction $trx) {
            ItcPackage::query()->create([
                'uuid' => $trx->uuid,
                'work_to' => Carbon::now()->addWeeks(30),
                'type' => PackageTypeEnum::STANDARD,
                'month_profit_percent' => '8.2'
            ]);

            app(StartBonusAccrualContract::class)
                ->accrue($trx->user_id, (float) $trx->amount);

            Artisan::call('users:recalc-rank');
        });

        $u = User::where("id", Auth::id())->first();

        Notify::packageBought($u, 'STANDARD', $this->amount);

        $this->dispatch('bought');
    }

    public function createPackage(TransactionRepositoryContract $transactionRepo): void
    {
        $this->validate();

        if ($this->packageType === PackageTypeEnum::PRESENT) {
            $transactionRepo->store(
                new CreateTransactionDto(
                    userId:      Auth::id(),
                    trxType:     TrxTypeEnum::PRESENT_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount:      $this->amount,
                    acceptedAt:  now(),
                    prefix:      'ITC-',
                ),
                function (Transaction $trx) {
                    return ItcPackage::query()->create([
                        'uuid'                 => $trx->uuid,
                        'work_to'              => now()->addMonths($this->duration),
                        'duration_months'      => $this->duration,
                        'type'                 => PackageTypeEnum::PRESENT,
                        'month_profit_percent' => $this->percent,
                    ]);
                }
            );
        } else {
            $transactionRepo->checkBalanceAndStore(
                new CreateTransactionDto(
                    userId:      Auth::id(),
                    trxType:     TrxTypeEnum::BUY_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount:      $this->amount,
                    acceptedAt:  now(),
                    prefix:      'ITC-',
                ),
                function (Transaction $trx) {
                    return ItcPackage::query()->create([
                        'uuid'                 => $trx->uuid,
                        'work_to'              => now()->addWeeks(30),
                        'type'                 => $this->packageType,
                        'month_profit_percent' => $this->percent,
                    ]);
                }
            );
        }

        $this->reset(['packageType', 'duration', 'amount', 'percent']);
        $this->dispatch('package-created');
    }

    public function exception($e, $stopPropagation): void
    {
        if ($e instanceof ValidationException) {
            return;
        }
        if ($e instanceof InvalidAmountException) {
            $this->dispatch(
                'new-system-notification',
                type   : 'error',
                message: $e->getMessage()
            );
            $stopPropagation();
        }
    }

    public function profitReinvest(string $uuid, ItcPackageRepositoryContract $itcPackageRepo): void
    {
        $toReinvestAmount = $itcPackageRepo->getCurrentProfitAmountByPackageUuid($uuid);

        if ($toReinvestAmount->isNegativeOrZero()) {
            throw new InvalidAmountException(__('livewire_itc_packages_insufficient_dividends_reinvest'));
        }

        PackageProfitReinvest::query()->create([
            'uuid' => 'PPR-' . Str::random(10),
            'package_uuid' => $uuid,
            'amount' => $toReinvestAmount,
            'matured_at'    => Carbon::now()->addDays(180),
        ]);

        Artisan::call('users:recalc-rank');

        $this->markReinvestNotificationsAsRead($uuid);
    }

    private function markReinvestNotificationsAsRead(string $packageUuid): void
    {
        // Определяем идентификатор пользователя-владельца пакета.
        // Если в репозитории есть явный метод — используйте его.
        // Ниже — безопасная стратегия через прямое чтение из БД.
        $package = ItcPackage::query()
            ->with('transaction')
            ->where('uuid', $packageUuid)
            ->first();


        if (!$package || !$package->transaction) {
            return;
        }

        $userId = $package->transaction->user_id;
        $notifiableType = (string) config('auth.providers.users.model', User::class);

        // 2) Все profit_uuid, принадлежащие ТОЛЬКО этому пакету
        $profitUuids = $package->profits()->pluck('uuid')->all();
        if (empty($profitUuids)) {
            return;
        }

        // Берём ВСЕ уведомления пользователя, где action.type=call и action.name=reinvest
        // Столбец notifications.data — JSON; в Eloquent доступ к полям через синтаксис ->.
        /** @var \Illuminate\Support\Collection<int, \Illuminate\Notifications\DatabaseNotification> $notifications */

        $notifications = DB::table('notifications as n')
            ->leftJoin('notification_profit_readeds as npr', 'npr.notification_id', '=', 'n.id')
            ->whereNull('npr.notification_id') // ещё не отмечены
            ->where('n.notifiable_id', $userId)
            ->where('n.notifiable_type', config('auth.providers.users.model'))
            ->whereRaw("jsonb_extract_path_text(n.data::jsonb, 'action','type') = ?", ['call'])
            ->whereRaw("jsonb_extract_path_text(n.data::jsonb, 'action','name') = ?", ['reinvest'])
            ->whereIn(
                DB::raw("jsonb_extract_path_text(n.data::jsonb, 'action','params','uuid')"),
                $profitUuids
            )
            ->select([
                'n.id',
                DB::raw("jsonb_extract_path_text(n.data::jsonb, 'action','params','uuid') as profit_uuid"),
            ])
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        foreach ($notifications as $row) {
            $profitUuid = $row->profit_uuid;
            if (!$profitUuid) {
                continue;
            }

            // Пропускаем, если уже есть связь реинвеста по этому profit_uuid
            $linkExists = PackageProfitWithReinvestLink::query()
                ->where('profit_uuid', $profitUuid)
                ->exists();

            if ($linkExists) {
                continue;
            }

            // Создаём отметку (уникальный индекс по notification_id защитит от дублей)
            NotificationProfitReaded::query()->firstOrCreate([
                'notification_id' => $row->id,
            ]);
        }
    }

    #[On('packages:reinvest-one')]
    public function reinvestOneProfit(string $profitUuid)
    {
        Log::channel('source')->debug('reinvest');
        Log::channel('source')->debug($profitUuid);
        DB::transaction(function () use ($profitUuid) {
            $profit = PackageProfit::query()
                ->where('uuid', $profitUuid)
                ->lockForUpdate()
                ->firstOrFail();

            if ($profit->reinvestLink()->exists()) {
                throw new RuntimeException(__('livewire_itc_packages_profit_already_reinvested'));
            }

            $reinvest = PackageProfitReinvest::query()->create([
                'uuid'         => 'PPR-' . Str::random(10),
                'package_uuid' => $profit->package_uuid,
                'amount'       => $profit->amount,
                'matured_at'   => now()->addDays(180),
            ]);

            PackageProfitWithReinvestLink::query()->create([
                'reinvest_uuid' => $reinvest->uuid,
                'profit_uuid'   => $profit->uuid,
            ]);
        });

        Artisan::call('users:recalc-rank');
    }

    public function withdrawProfit(string $uuid, ItcPackageRepositoryContract $itcPackageRepo, TransactionRepositoryContract $transactionRepo): void
    {
        DB::transaction(function () use ($uuid, $itcPackageRepo, $transactionRepo) {
            DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $toWithdrawAmount = $itcPackageRepo->getCurrentProfitAmountByPackageUuid($uuid);

            if ($toWithdrawAmount->isNegativeOrZero()) {
                throw new InvalidAmountException(__('livewire_itc_packages_insufficient_dividends_withdraw'));
                return;
            }

            $transaction = $transactionRepo->commonStore(new CreateTransactionDto(
                userId: Auth::id(),
                trxType: TrxTypeEnum::WITHDRAW_PACKAGE_PROFIT,
                balanceType: BalanceTypeEnum::MAIN,
                amount: $toWithdrawAmount,
                acceptedAt: Carbon::now(),
                prefix: 'WPP-',
            ));

            PackageProfitWithdraw::query()->create([
                'uuid' => $transaction->uuid,
                'package_uuid' => $uuid
            ]);
        });
        $this->markReinvestNotificationsAsRead($uuid);
    }

    public function render()
    {
        $trxRepo = app(TransactionRepositoryContract::class);

        return view('livewire.account.itc.packages', [
            'packages' => ItcPackage::query()
                ->whereHas('transaction', fn ($q) => $q->where('user_id', Auth::id()))
                ->whereNotIn('type', [PackageTypeEnum::ARCHIVE])
                ->with(['transaction', 'zeroing'])
                ->withSum(['profits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['reinvestProfitsAll' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['withdrawProfitsTransactions' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['reinvestProfitWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
                ->get(),
            'logRows'  => $trxRepo->packageLog(),
        ]);
    }
}
