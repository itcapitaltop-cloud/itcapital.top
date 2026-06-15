<?php

namespace App\Livewire\Account\Itc;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Exceptions\Domain\InvalidAmountException;
use App\Helpers\Notify;
use App\Livewire\Concerns\WithInfiniteFeed;
use App\Models\ItcPackage;
use App\Models\NotificationProfitReaded;
use App\Models\Package\PackageDefinition;
use App\Models\PackageBalanceWithdraw;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitWithdraw;
use App\Models\PackageProfitWithReinvestLink;
use App\Models\PromoCode;
use App\Models\ReinvestToPackageBody;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use App\Services\Package\PackageDefinitionResolver;
use App\Services\PromoCodes\PackagePromoCodeService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class Packages extends Component
{
    use WithInfiniteFeed;

    private const PURCHASABLE_PACKAGE_TYPES = [
        PackageTypeEnum::STANDARD->value,
        PackageTypeEnum::PRIVILEGE->value,
        PackageTypeEnum::VIP->value,
    ];

    #[Validate(['required', 'numeric'])]
    public string $amount = '';

    public string $promoCode = '';

    public ?int $selectedPackageDefinitionId = null;

    public ?int $appliedPromoCodeId = null;

    public string $appliedPromoCodeDiscount = '';

    public function minimumAmountPlaceholder(): string
    {
        if ($this->appliedPromoCodeId !== null) {
            return __('livewire_itc_minimum_itc_with_promo', [
                'amount' => $this->appliedPromoCodeDiscount,
            ]);
        }

        try {
            $packageDefinition = $this->selectedPackageDefinition();

            if (! $packageDefinition instanceof PackageDefinition) {
                return __('livewire_itc_select_package_first');
            }

            return __('livewire_itc_minimum_itc_with_promo', [
                'amount' => BigDecimal::of($packageDefinition->min_start_amount)->toScale(2, RoundingMode::HALF_UP) . ' ITC',
            ]);
        } catch (\Throwable $throwable) {
            Log::warning('[Packages.minimumAmountPlaceholder] failed to resolve package definition minimum', [
                'package_type' => PackageTypeEnum::STANDARD->value,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            return __('livewire_itc_minimum_itc_placeholder');
        }
    }

    #[Locked]
    public string $mainBalance = '1000';

    public PackageTypeEnum $packageType = PackageTypeEnum::STANDARD;

    public int $duration = 1;

    public string $percent = '8.2';

    public string $withdrawPackageAmount = '';

    public function boot(TransactionRepositoryContract $transactionRepositoryContract): void
    {
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

        $this->mainBalance = $transactionRepositoryContract->getBalanceAmountByUserIdAndType(auth()->user()->id, BalanceTypeEnum::MAIN);
    }

    protected function rules(): array
    {
        return [
            'packageType' => ['required', Rule::enum(PackageTypeEnum::class)],
            'duration' => ['required_if:packageType,present', 'in:1,3,6,12'],
            //            'amount'      => ['required_if:packageType,standard,privilege,vip', 'numeric', 'min:1'],
            'percent' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function withdrawPackageBalance(
        string $uuid,
        TransactionRepositoryContract $transactionRepo
    ): void {
        $this->validateOnly('withdrawPackageAmount', [
            'withdrawPackageAmount' => 'required|numeric|min:1|max_package_sum:' . $uuid,
        ]);

        $transaction = $transactionRepo->commonStore(
            new CreateTransactionDto(
                userId: Auth::id(),
                trxType: TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE,
                balanceType: BalanceTypeEnum::MAIN,
                amount: $this->withdrawPackageAmount,
                acceptedAt: now(),
                prefix: 'WPB-',
            )
        );

        PackageBalanceWithdraw::query()->create([
            'uuid' => $transaction->uuid,
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
            message: __('admin_controller_package_updated')
        );
    }

    /**
     * @throws ValidationException
     */
    public function topUpNeeded(
        string $uuid,
    ): Redirector|RedirectResponse {
        $this->validateOnly('withdrawPackageAmount', [
            'withdrawPackageAmount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($value > $this->mainBalance) {
                        $fail('Недостаточно средств на балансе.');
                    }
                },
            ],
        ]);

        $package = Transaction::query()
            ->select(['id', 'uuid', 'amount', 'user_id'])
            ->with([
                'itcPackage' => function ($query) {
                    $query->select(['id', 'uuid', 'month_profit_percent']);
                },
                'user' => function ($query) {
                    $query->select(['id']);
                },
            ])
            ->whereHas('itcPackage', function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            })
            ->first();

        $package->increment('amount', $this->withdrawPackageAmount);

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PackageToppedUp,
            userId: Auth::id(),
            subject: $package->itcPackage,
            feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => $this->withdrawPackageAmount,
                'package_uuid' => $uuid,
                'source_balance' => 'main',
            ],
            causer: Auth::user(),
            logName: 'packages',
            context: 'account',
        ));

        app(StartBonusAccrualContract::class)->accrue(auth()->id(), (float) $this->withdrawPackageAmount);

        $this->reset('withdrawPackageAmount');
        $this->dispatch('balance-edited');

        return redirect(request()->header('Referer'));
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
                    ! PackageProfitReinvest::query()->where('package_uuid', $package->uuid)->exists() &&
                    ! PackageProfitWithdraw::query()->where('package_uuid', $package->uuid)->exists()
                ) {
                    $allProfitUuids = PackageProfit::query()
                        ->where('package_uuid', $package->uuid)
                        ->pluck('uuid')
                        ->all();

                    if (! empty($allProfitUuids)) {
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
                            'uuid' => (string) Str::uuid(),
                            'package_uuid' => $package->uuid,
                            'amount' => (string) $sumAll,
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
                fn ($c, $r) => BigDecimal::of((string) $c)->plus((string) $r->amount),
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
                    'type' => 'reinvest',
                    'uuid' => $e->uuid,
                    'at' => $e->created_at,
                    'amount' => BigDecimal::of((string) $e->amount),
                ]);
            }

            foreach ($withdrawEvents as $e) {
                $events->push([
                    'type' => 'withdraw',
                    'uuid' => $e->uuid,
                    'at' => $e->created_at,
                    'amount' => BigDecimal::of((string) $e->amount),
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
                    ->when(! empty($exclude), fn ($q) => $q->whereNotIn('uuid', $exclude))
                    ->orderBy('created_at', 'desc') // хвост справа-налево (ближайшие к событию)
                    ->orderBy('uuid', 'desc')
                    ->get(['uuid', 'amount', 'created_at']);

                foreach ($profits as $p) {
                    $a = BigDecimal::of((string) $p->amount);

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

            if (! empty($freeProfitUuids)) {
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
                    'uuid' => (string) Str::uuid(),
                    'package_uuid' => $package->uuid,
                    'amount' => (string) $totalReinvested,
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

    public function updatedPromoCode(string $value): void
    {
        $this->appliedPromoCodeId = null;
        $this->appliedPromoCodeDiscount = '';
        $this->resetValidation('promoCode');
    }

    public function updatedSelectedPackageDefinitionId(): void
    {
        $this->reset('promoCode', 'appliedPromoCodeId', 'appliedPromoCodeDiscount');
        $this->resetValidation(['selectedPackageDefinitionId', 'promoCode', 'amount']);
    }

    public function applyPromoCode(
        PackagePromoCodeService $promoCodeService,
    ): void {
        $this->appliedPromoCodeId = null;
        $this->appliedPromoCodeDiscount = '';
        $this->resetValidation('promoCode');

        if (trim($this->promoCode) === '') {
            return;
        }

        if ($this->selectedPackageDefinitionId === null) {
            $this->addError('selectedPackageDefinitionId', __('livewire_itc_select_package_first'));

            return;
        }

        $user = User::query()->findOrFail(Auth::id());
        $packageDefinition = $this->selectedPackageDefinition();

        if (! $packageDefinition instanceof PackageDefinition) {
            $this->addError('selectedPackageDefinitionId', __('livewire_itc_package_unavailable'));

            return;
        }

        $result = $promoCodeService->validateForPurchase(
            user: $user,
            packageDefinition: $packageDefinition,
            amount: (string) ($this->amount ?: $packageDefinition->min_start_amount),
            code: $this->promoCode,
            defaultMinimumAmount: $packageDefinition->min_start_amount,
        );

        if (! $result->isValid()) {
            $this->addError(
                'promoCode',
                $this->promoCodeValidationMessage($result->errorCode, $result->effectiveMinimumAmount)
            );

            return;
        }

        $this->appliedPromoCodeId = $result->promoCode->id;
        $this->appliedPromoCodeDiscount = round((float) $result->effectiveMinimumAmount, 2) . ' ITC';
    }

    public function buyPackage(
        TransactionRepositoryContract $transactionRepo,
        PackagePromoCodeService $promoCodeService,
    ): void {
        $this->validate([
            'selectedPackageDefinitionId' => [
                'required',
                'integer',
                Rule::exists('package_definitions', 'id')
                    ->where('is_active', true)
                    ->whereIn('type', self::PURCHASABLE_PACKAGE_TYPES)
                    ->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'numeric'],
            'promoCode' => ['nullable', 'string', 'max:32'],
        ]);

        $user = User::query()->findOrFail(Auth::id());
        $packageDefinition = $this->selectedPackageDefinition();

        if (! $packageDefinition instanceof PackageDefinition) {
            $this->addError('selectedPackageDefinitionId', __('livewire_itc_package_unavailable'));

            return;
        }

        Log::debug('[Packages.buyPackage] start', [
            'user_id' => $user->id,
            'amount' => $this->amount,
            'package_type' => $packageDefinition->type->value,
            'package_definition_id' => $packageDefinition->id,
            'default_profit_percent' => $packageDefinition->default_profit_percent,
            'min_start_amount' => $packageDefinition->min_start_amount,
            'duration_months' => $packageDefinition->duration_months,
            'has_promo_code' => trim($this->promoCode) !== '',
        ]);

        $appliedPromoCode = $this->appliedPromoCodeId === null
            ? null
            : PromoCode::query()->find($this->appliedPromoCodeId);

        $promoCodeValidation = $promoCodeService->validateForPurchase(
            user: $user,
            packageDefinition: $packageDefinition,
            amount: $this->amount,
            code: $appliedPromoCode?->code,
            defaultMinimumAmount: $packageDefinition->min_start_amount,
        );

        if ($promoCodeValidation === null || ! $promoCodeValidation->isValid()) {
            if ($promoCodeValidation !== null) {
                Log::warning('[Packages.buyPackage] promo code validation failed', [
                    'user_id' => $user->id,
                    'promo_code_id' => $promoCodeValidation->promoCode?->id,
                    'error_code' => $promoCodeValidation->errorCode,
                    'effective_minimum_amount' => $promoCodeValidation->effectiveMinimumAmount,
                ]);

                $this->addError(
                    $promoCodeValidation->errorCode === PackagePromoCodeService::ERROR_AMOUNT_BELOW_THRESHOLD ? 'amount' : 'promoCode',
                    $this->promoCodeValidationMessage($promoCodeValidation->errorCode, $promoCodeValidation->effectiveMinimumAmount)
                );
            }

            return;
        }

        $redeemedPromoCodeId = null;

        try {
            $transactionRepo->checkBalanceAndStore(new CreateTransactionDto(
                userId: $user->id,
                trxType: TrxTypeEnum::BUY_PACKAGE,
                balanceType: BalanceTypeEnum::MAIN,
                amount: $this->amount,
                acceptedAt: Carbon::now(),
                prefix: 'ITC-',
            ), function (Transaction $trx) use ($promoCodeService, $promoCodeValidation, $user, $packageDefinition, &$redeemedPromoCodeId) {
                $package = ItcPackage::query()->create([
                    'uuid' => $trx->uuid,
                    'package_definition_id' => $packageDefinition->id,
                    'work_to' => Carbon::now()->addMonths($packageDefinition->duration_months ?? 0),
                    'duration_months' => $packageDefinition->duration_months,
                    'type' => $packageDefinition->type,
                    'month_profit_percent' => $packageDefinition->default_profit_percent,
                ]);

                Log::debug('[Packages.buyPackage] package created from definition', [
                    'user_id' => $user->id,
                    'package_uuid' => $package->uuid,
                    'package_definition_id' => $packageDefinition->id,
                    'month_profit_percent' => $package->month_profit_percent,
                    'duration_months' => $package->duration_months,
                    'work_to' => $package->work_to?->toDateTimeString(),
                ]);

                if ($promoCodeValidation->promoCode !== null) {
                    $redeemedPromoCode = $promoCodeService->redeem(
                        $promoCodeValidation->promoCode,
                        $user,
                        $packageDefinition,
                        $packageDefinition->min_start_amount,
                    );
                    $redeemedPromoCodeId = $redeemedPromoCode->promo_code_id;

                    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                        type: ActivityEventTypeEnum::PromoCodeApplied,
                        userId: $trx->user_id,
                        subject: $redeemedPromoCode,
                        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'promo_code_id' => $redeemedPromoCode->promo_code_id,
                            'promo_code' => $promoCodeValidation->promoCode->code,
                            'original_threshold' => $packageDefinition->min_start_amount,
                            'effective_threshold' => $promoCodeValidation->promoCode->reduced_minimum_amount,
                        ],
                        causer: Auth::user(),
                        logName: 'packages',
                        context: 'account',
                    ));
                }

                app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                    type: ActivityEventTypeEnum::PackagePurchased,
                    userId: $trx->user_id,
                    subject: $package,
                    feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                    properties: [
                        'amount' => (string) $trx->amount,
                        'package_uuid' => $package->uuid,
                        'package_type' => $package->type->value,
                        'package_definition_id' => $packageDefinition->id,
                        'promo_code_id' => $redeemedPromoCodeId,
                    ],
                    causer: Auth::user(),
                    logName: 'packages',
                    context: 'account',
                ));

                app(StartBonusAccrualContract::class)
                    ->accrue($trx->user_id, (float) $trx->amount);

                Artisan::call('user:use-rank');
            });
        } catch (InvalidAmountException $exception) {
            Log::warning('[Packages.buyPackage] insufficient balance', [
                'user_id' => $user->id,
                'amount' => $this->amount,
                'promo_code_id' => $promoCodeValidation->promoCode?->id,
            ]);

            throw $exception;
        } catch (\Throwable $throwable) {
            Log::error('[Packages.buyPackage] package purchase failed', [
                'user_id' => $user->id,
                'amount' => $this->amount,
                'promo_code_id' => $promoCodeValidation->promoCode?->id,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        Notify::packageBought($user, $packageDefinition->name, $this->amount);

        Log::info('[Packages.buyPackage] purchased', [
            'user_id' => $user->id,
            'amount' => $this->amount,
            'package_type' => $packageDefinition->type->value,
            'package_definition_id' => $packageDefinition->id,
            'promo_code_id' => $redeemedPromoCodeId,
        ]);

        $this->dispatch('bought');

        $this->reset('promoCode', 'appliedPromoCodeId', 'appliedPromoCodeDiscount', 'selectedPackageDefinitionId');
    }

    /**
     * @return array<int, string>
     */
    private function activePackageDefinitionOptions(): array
    {
        return PackageDefinition::query()
            ->active()
            ->whereIn('type', self::PURCHASABLE_PACKAGE_TYPES)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }

    private function selectedPackageDefinition(): ?PackageDefinition
    {
        if ($this->selectedPackageDefinitionId === null) {
            return null;
        }

        return PackageDefinition::query()
            ->active()
            ->whereKey($this->selectedPackageDefinitionId)
            ->whereIn('type', self::PURCHASABLE_PACKAGE_TYPES)
            ->first();
    }

    private function promoCodeValidationMessage(?string $errorCode, string $effectiveMinimumAmount): string
    {
        return match ($errorCode) {
            PackagePromoCodeService::ERROR_USED => __('livewire_itc_promo_code_used'),
            PackagePromoCodeService::ERROR_PACKAGE_TYPE_MISMATCH => __('livewire_itc_promo_code_package_type_mismatch'),
            PackagePromoCodeService::ERROR_AMOUNT_BELOW_THRESHOLD => __('livewire_itc_promo_code_amount_below_threshold', [
                'amount' => round((float) $effectiveMinimumAmount, 2),
            ]),
            PackagePromoCodeService::ERROR_UNSUPPORTED_PACKAGE_TYPE => __('livewire_itc_promo_code_unsupported_package_type'),
            default => __('livewire_itc_promo_code_invalid'),
        };
    }

    public function createPackage(
        TransactionRepositoryContract $transactionRepo,
        PackageDefinitionResolver $packageDefinitionResolver,
    ): void {
        $this->validate();

        Log::debug('[Packages.createPackage] start', [
            'user_id' => Auth::id(),
            'package_type' => $this->packageType->value,
            'amount' => $this->amount,
        ]);

        if ($this->packageType === PackageTypeEnum::PRESENT) {
            $packageDefinition = $packageDefinitionResolver->resolve(PackageTypeEnum::PRESENT);

            $transactionRepo->store(
                new CreateTransactionDto(
                    userId: Auth::id(),
                    trxType: TrxTypeEnum::PRESENT_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount: $this->amount,
                    acceptedAt: now(),
                    prefix: 'ITC-',
                ),
                function (Transaction $trx) use ($packageDefinition) {
                    $package = ItcPackage::query()->create([
                        'uuid' => $trx->uuid,
                        'package_definition_id' => $packageDefinition->id,
                        'work_to' => now()->addMonths($this->duration),
                        'duration_months' => $this->duration,
                        'type' => PackageTypeEnum::PRESENT,
                        'month_profit_percent' => $this->percent,
                    ]);

                    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                        type: ActivityEventTypeEnum::PackagePurchased,
                        userId: $trx->user_id,
                        subject: $package,
                        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $trx->amount,
                            'package_uuid' => $package->uuid,
                            'package_type' => $package->type->value,
                            'package_definition_id' => $packageDefinition->id,
                        ],
                        causer: Auth::user(),
                        logName: 'packages',
                        context: 'account',
                    ));

                    return $package;
                }
            );
        } else {
            $packageDefinition = $packageDefinitionResolver->resolve($this->packageType);

            $transactionRepo->checkBalanceAndStore(
                new CreateTransactionDto(
                    userId: Auth::id(),
                    trxType: TrxTypeEnum::BUY_PACKAGE,
                    balanceType: BalanceTypeEnum::MAIN,
                    amount: $this->amount,
                    acceptedAt: now(),
                    prefix: 'ITC-',
                ),
                function (Transaction $trx) use ($packageDefinition) {
                    $package = ItcPackage::query()->create([
                        'uuid' => $trx->uuid,
                        'package_definition_id' => $packageDefinition->id,
                        'work_to' => now()->addMonths($packageDefinition->duration_months ?? 0),
                        'duration_months' => $packageDefinition->duration_months,
                        'type' => $this->packageType,
                        'month_profit_percent' => $packageDefinition->default_profit_percent,
                    ]);

                    Log::debug('[Packages.createPackage] package created from definition', [
                        'user_id' => $trx->user_id,
                        'package_uuid' => $package->uuid,
                        'package_definition_id' => $packageDefinition->id,
                        'package_type' => $package->type->value,
                        'month_profit_percent' => $package->month_profit_percent,
                        'duration_months' => $package->duration_months,
                    ]);

                    app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                        type: ActivityEventTypeEnum::PackagePurchased,
                        userId: $trx->user_id,
                        subject: $package,
                        feeds: [ActivityFeedTypeEnum::Packages, ActivityFeedTypeEnum::UserDetailUser],
                        properties: [
                            'amount' => (string) $trx->amount,
                            'package_uuid' => $package->uuid,
                            'package_type' => $package->type->value,
                            'package_definition_id' => $packageDefinition->id,
                        ],
                        causer: Auth::user(),
                        logName: 'packages',
                        context: 'account',
                    ));

                    return $package;
                }
            );
        }

        $this->reset(['packageType', 'duration', 'amount', 'percent']);
        $this->percent = '8.2';
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
        DB::transaction(function () use ($uuid, $itcPackageRepo) {
            ItcPackage::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            $toReinvestAmount = $itcPackageRepo->getCurrentProfitAmountByPackageUuid($uuid);

            if ($toReinvestAmount->isNegativeOrZero()) {
                throw new InvalidAmountException(__('livewire_itc_packages_insufficient_dividends_reinvest'));
            }

            PackageProfitReinvest::query()->create([
                'uuid' => 'PPR-' . Str::random(10),
                'package_uuid' => $uuid,
                'amount' => $toReinvestAmount,
                'matured_at' => Carbon::now()->addDays(180),
            ]);
        });

        Artisan::call('user:use-rank');

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

        if (! $package || ! $package->transaction) {
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
        /** @var Collection<int, DatabaseNotification> $notifications */
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

            if (! $profitUuid) {
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
                'uuid' => 'PPR-' . Str::random(10),
                'package_uuid' => $profit->package_uuid,
                'amount' => $profit->amount,
                'matured_at' => now()->addDays(180),
            ]);

            PackageProfitWithReinvestLink::query()->create([
                'reinvest_uuid' => $reinvest->uuid,
                'profit_uuid' => $profit->uuid,
            ]);
        });

        Artisan::call('user:use-rank');
    }

    public function withdrawProfit(string $uuid, ItcPackageRepositoryContract $itcPackageRepo, TransactionRepositoryContract $transactionRepo): void
    {
        DB::transaction(function () use ($uuid, $itcPackageRepo, $transactionRepo) {
            ItcPackage::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            $toWithdrawAmount = $itcPackageRepo->getCurrentProfitAmountByPackageUuid($uuid);

            if ($toWithdrawAmount->isNegativeOrZero()) {
                throw new InvalidAmountException(__('livewire_itc_packages_insufficient_dividends_withdraw'));
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
                'package_uuid' => $uuid,
            ]);
        });
        $this->markReinvestNotificationsAsRead($uuid);
    }

    public function render()
    {
        $trxRepo = app(TransactionRepositoryContract::class);

        [$logRows, $logHasMore] = $this->paginateFeed(
            $trxRepo->packageLog($this->feedFetchLimit())
        );

        return view('livewire.account.itc.packages', [
            'packages' => ItcPackage::query()
                ->notActive()
                ->userPackagesWithFinancials(auth()->user()->id)
                ->get(),
            'logRows' => $logRows,
            'logHasMore' => $logHasMore,
            'packageDefinitionOptions' => $this->activePackageDefinitionOptions(),
        ]);
    }
}
