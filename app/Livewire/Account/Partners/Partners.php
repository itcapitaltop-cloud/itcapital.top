<?php

declare(strict_types=1);

namespace App\Livewire\Account\Partners;

use App\Contracts\Accruals\StartBonusAccrualContract;
use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Dto\Activity\WriteBusinessActivityData;
use App\Enums\Activity\ActivityEventTypeEnum;
use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Livewire\Concerns\WithInfiniteFeed;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerRank;
use App\Models\PartnerRankRequirement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\BusinessActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

class Partners extends Component
{
    use WithInfiniteFeed;

    #[Validate(['required', 'numeric', 'min:1', 'balance'])]
    public string $toPartnerAmount = '';

    #[Validate(['required', 'string', 'exists:users,username', 'not_self'])]
    public string $toUsername = '';

    public array $availableLines = [];

    public int $line = 1;

    public bool $isModalToPackage = false;

    #[Validate(['required', 'numeric', 'min:1', 'balance'])]
    public string $toPackageAmount = '';

    #[Validate(['required', 'exists:itc_packages,uuid'])]
    public ?string $selectedPackageUuid = null;

    protected TransactionRepositoryContract $transactionRepo;

    public function updatedToPackageAmount(string $value): void
    {
        $this->toPackageAmount = str_replace(',', '.', $value);
    }

    public function boot(): void
    {
        Validator::extend('not_self', function ($attribute, $value, $params, $validator) {
            return $value !== auth()->user()?->username;
        });

        Validator::extend('balance', function ($attribute, $value): bool {

            $val = (float) str_replace([' ', ','], ['', '.'], $value);

            $debit = auth()->user()->transactions()
                ->where('balance_type', BalanceTypeEnum::PARTNER)
                ->whereIn('trx_type', TrxTypeEnum::getDebits())
                ->whereNull('rejected_at')
                ->sum('amount');

            $credit = auth()->user()->transactions()
                ->where('balance_type', BalanceTypeEnum::PARTNER)
                ->whereIn('trx_type', TrxTypeEnum::getCredits())
                ->whereNull('rejected_at')
                ->sum('amount');

            $balance = $debit - $credit;

            return $val <= $balance;
        });
    }

    public function mount(): void
    {
        $userId = Auth::id();
        $user = User::find($userId);

        // Лимит линий зависит от extended_lines
        $maxLines = ($user && $user->extended_lines) ? 20 : 5;

        // Считаем, сколько вообще есть партнёров на каждой линии для текущего пользователя
        $lines = PartnerClosure::query()
            ->where('ancestor_id', $userId)
            ->whereBetween('depth', [1, $maxLines])
            ->pluck('depth')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->availableLines = $lines;

        if (! in_array($this->line, $this->availableLines, true)) {
            $this->line = $this->availableLines[0] ?? 1;
        }
    }

    public function getRegularBalancesProperty(): array
    {
        $debit = Transaction::where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getDebits())
            ->sum('amount');

        $credit = Transaction::where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::REGULAR_PREMIUM)
            ->whereIn('trx_type', TrxTypeEnum::getCredits())
            ->sum('amount');

        $available = $debit - $credit;

        $total = Transaction::where('user_id', Auth::id())
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->sum('amount');

        $week = Transaction::where('user_id', Auth::id())
            ->where('trx_type', TrxTypeEnum::REGULAR_PREMIUM_ACCRUAL)
            ->where('created_at', '>=', now()->subWeek())
            ->sum('amount');

        return compact('available', 'total', 'week');
    }

    public function sendToMainSelf(): void
    {
        $amount = $this->partnerBalance;
        $baseUuid = null;

        if ($amount <= 0) {
            $this->addError('toPartnerAmount', __('livewire_partners_partner_balance_empty'));

            return;
        }

        DB::transaction(function () use ($amount, &$baseUuid) {
            Transaction::create([
                'uuid' => $uuid = 'PSM-' . Str::random(10),
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::PARTNER_TO_MAIN_SELF,
                'balance_type' => BalanceTypeEnum::MAIN,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);

            $baseUuid = $uuid;

            Transaction::create([
                'uuid' => $uuid . '-M',
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::PARTNER_TO_MAIN_SELF_MIRROR,
                'balance_type' => BalanceTypeEnum::PARTNER,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);
        });

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::PartnerToMainTransferred,
            userId: Auth::id(),
            subject: Auth::user(),
            feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $amount,
                'transaction_uuid' => $baseUuid,
            ],
            causer: Auth::user(),
            logName: 'partners',
            context: 'account',
        ));

        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_partners_transfer_to_main_success'),
        );
    }

    public function sendToPartner(): void
    {
        try {
            $this->validateOnly('toPartnerAmount');
            $this->validateOnly('toUsername');
            $receiver = User::where('username', $this->toUsername)->firstOrFail();

            $amount = (float) str_replace(',', '.', $this->toPartnerAmount);
            $uuid = null;

            DB::transaction(function () use ($receiver, $amount, &$uuid) {
                $uuid = 'PT-' . Str::random(10);

                Transaction::create([
                    'uuid' => $uuid,
                    'amount' => $amount,
                    'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_IN,
                    'balance_type' => BalanceTypeEnum::MAIN,
                    'user_id' => $receiver->id,
                    'accepted_at' => now(),
                ]);

                Transaction::create([
                    'uuid' => $uuid . '-O',
                    'amount' => $amount,
                    'trx_type' => TrxTypeEnum::PARTNER_TRANSFER_OUT,
                    'balance_type' => BalanceTypeEnum::PARTNER,
                    'user_id' => Auth::id(),
                ]);
            });

            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PartnerTransferSent,
                userId: Auth::id(),
                subject: $receiver,
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => (string) $amount,
                    'username' => $receiver->username,
                    'transaction_uuid' => $uuid,
                ],
                causer: Auth::user(),
                logName: 'partners',
                context: 'account',
            ));

            app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
                type: ActivityEventTypeEnum::PartnerTransferReceived,
                userId: $receiver->id,
                subject: Auth::user(),
                feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
                properties: [
                    'amount' => (string) $amount,
                    'username' => Auth::user()->username,
                    'transaction_uuid' => $uuid,
                ],
                causer: Auth::user(),
                logName: 'partners',
                context: 'account',
            ));
        } catch (Throwable $e) {
            Log::channel('source')->debug($e->getMessage());

            return;
        }

        $this->reset('toPartnerAmount', 'toUsername');

        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_partners_transfer_success'),
        );
    }

    public function getPartnerBalanceProperty(): float
    {
        $debit = Transaction::query()
            ->where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereIn('trx_type', TrxTypeEnum::getDebits())
            ->whereNull('rejected_at')
            ->sum('amount');

        $credit = Transaction::query()
            ->where('user_id', Auth::id())
            ->where('balance_type', BalanceTypeEnum::PARTNER)
            ->whereIn('trx_type', TrxTypeEnum::getCredits())
            ->whereNull('rejected_at')
            ->sum('amount');

        return $debit - $credit;
    }

    public function getPartnerDynamicsProperty(): array
    {
        $rangeCredit = function (Carbon $since): float {
            return (float) Transaction::query()
                ->where('user_id', Auth::id())
                ->where('balance_type', BalanceTypeEnum::PARTNER)
                ->whereIn('trx_type', TrxTypeEnum::getDebits())
                ->whereNull('rejected_at')
                ->where('created_at', '>=', $since)
                ->sum('amount');
        };

        return [
            'week' => $rangeCredit(now()->subWeek()),
            'month' => $rangeCredit(now()->subMonth()),
        ];
    }

    public function getPartnersProperty(): Collection
    {
        $userId = Auth::id();
        $line = $this->line;
        $partnerIds = PartnerClosure::where('ancestor_id', $userId)
            ->where('depth', $line)
            ->pluck('descendant_id');

        if ($partnerIds->isEmpty()) {
            return collect();
        }

        $partners = User::whereIn('id', $partnerIds)
            ->whereNull('banned_at')
            ->withSum(['partnerRewards as start_bonus' => function ($q) use ($line) {
                $q->where('reward_type', PartnerRewardTypeEnum::START->value)
                    ->where('line', $line);
            }], 'amount')
            ->withSum(['partnerRewards as regular_bonus' => function ($q) use ($line) {
                $q->where('reward_type', PartnerRewardTypeEnum::REGULAR->value)
                    ->where('line', $line);
            }], 'amount')
            ->get();

        $partners->each(function ($user) {
            $user->total_profit = floatval($user->start_bonus) + floatval($user->regular_bonus);
        });

        return $partners;
    }

    protected function calcTurnoverByLine(int $line, ?string $fromDate = null): float
    {
        $ids = PartnerClosure::where('ancestor_id', Auth::id())
            ->where('depth', $line)
            ->pluck('descendant_id');

        if ($ids->isEmpty()) {
            return 0.0;
        }

        $buyQuery = DB::table('transactions')
            ->whereIn('user_id', $ids)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE->value)
            ->whereNotNull('accepted_at');

        if ($fromDate) {
            $buyQuery->where('accepted_at', '>=', $fromDate);
        }

        $buySum = $buyQuery->sum('amount');

        $reinvestQuery = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->whereIn('transactions.user_id', $ids);

        $reinvestSum = (float) $reinvestQuery
            ->withSum([
                'reinvestProfitsAll' => function ($q) use ($fromDate) {
                    if ($fromDate) {
                        $q->where('created_at', '>=', $fromDate);
                    }
                },
            ], 'amount')
            ->get()
            ->sum('reinvest_profits_all_sum_amount');

        return $buySum + $reinvestSum;
    }

    public function getProgressBarsProperty(): array
    {
        $user = Auth::user();
        $maxRank = (int) PartnerRank::query()->max('rank');
        $next = max(1, $user->rank + 1);
        $targetRank = $maxRank > 0 ? min($next, $maxRank) : $next;
        $manualFillLines = [];

        // Для максимального ранга показываем текущую статистику по линиям на шкале максимального ранга.
        $reqs = PartnerRankRequirement::whereHas('rank', fn ($q) => $q->where('rank', $targetRank))->get();

        if ($user->overridden_rank) {
            $manualFillLines = PartnerRankRequirement::query()
                ->whereNotNull('line')
                ->whereHas('rank', fn ($q) => $q->where('rank', '<', $user->rank))
                ->pluck('line')
                ->unique()
                ->map(fn ($line) => (int) $line)
                ->all();
        }

        $bars = [];

        $fromDate = null;
        $personalBase = 0;
        $lineBases = [];

        if ($user->overridden_rank && $user->overridden_rank_from) {
            $fromDate = $user->overridden_rank_from;

            // стартовые базы для ранга, с которого начали считать вручную
            $baseReqs = PartnerRankRequirement::whereHas('rank',
                fn ($q) => $q->where('rank', $user->rank))->get();

            $personalBase = $baseReqs->firstWhere('line', null)?->personal_deposit ?? 0;
            $lineBases = PartnerRankRequirement::query()
                ->whereNotNull('line')
                ->whereHas('rank', fn ($q) => $q->where('rank', '<=', $user->rank))
                ->selectRaw('line, SUM(required_turnover) as total')
                ->groupBy('line')
                ->pluck('total', 'line')
                ->all();
        }

        if ($personal = $reqs->firstWhere('line', null)) {
            $baseQuery = ItcPackage::query()
                ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                ->where('transactions.user_id', $user->id)
                ->whereNull('itc_packages.closed_at')
                ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
                ->with('transaction:id,uuid,amount,accepted_at,user_id');

            // ВЕСЬ депозит за всё время
            $allDeposit = (clone $baseQuery)
                ->withSum('reinvestProfitsAll', 'amount')
                ->get()
                ->sum(fn ($p) => (float) $p->transaction->amount +
                    (float) $p->reinvest_profits_all_sum_amount
                );

            // Минимум для текущего (override) ранга из базы, как раньше
            $minForRank = (float) $personalBase;

            if ($user->overridden_rank && $fromDate && $allDeposit < $minForRank) {
                // "минимум + прирост с даты override"
                $since = (clone $baseQuery)
                    ->withSum([
                        'reinvestProfitsAll' => function ($q) use ($fromDate) {
                            $q->when($fromDate, fn ($qq) => $qq->where('created_at', '>=', $fromDate));
                        },
                    ], 'amount')
                    ->get()
                    ->sum(function ($p) use ($fromDate) {
                        $buy = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                            ? (float) $p->transaction->amount
                            : 0.0;

                        return $buy + (float) $p->reinvest_profits_all_sum_amount;
                    });

                $currentDeposit = $minForRank + $since;
            } else {
                $currentDeposit = $allDeposit;
            }

            $bars[] = [
                'label' => __('livewire_partners_personal_deposit_label'),
                'current' => $currentDeposit,
                'target' => $personal->personal_deposit, // НЕ кумулятивно
            ];
        }

        // Сумма требований по каждой линии от ранга 1 до целевого ранга (кумулятивно)
        $cumToNextByLine = PartnerRankRequirement::query()
            ->whereNotNull('line')
            ->whereHas('rank', fn ($q) => $q->where('rank', '<=', $targetRank))
            ->selectRaw('line, SUM(required_turnover) as total')
            ->groupBy('line')
            ->pluck('total', 'line'); // [line => cum(1..R+1)]

        // Прогресс по линиям: current = сколько уже НАБРАНО в пределах R+1
        $lineReqs = $reqs->whereNotNull('line');

        if ($lineReqs->isEmpty()) {
            $maxDepth = $user->extended_lines ? 20 : 5;

            $lineReqs = PartnerClosure::query()
                ->where('ancestor_id', $user->id)
                ->whereBetween('depth', [1, $maxDepth])
                ->select('depth')
                ->distinct()
                ->orderBy('depth')
                ->pluck('depth')
                ->map(fn ($line) => (object) [
                    'line' => (int) $line,
                    'required_turnover' => null,
                ]);
        }

        foreach ($lineReqs as $r) {
            $line = $r->line;
            $target = $r->required_turnover; // требование следующего ранга (R+1)

            // При ручном ранге сохраняем излишек до override и докидываем недостающую базу.
            $actual = $this->calcEffectiveTurnoverByLine(
                line: $line,
                baseAmount: (float) ($lineBases[$line] ?? 0),
                fromDate: $fromDate instanceof Carbon ? $fromDate->toDateTimeString() : $fromDate,
            );

            $cumulativeToNext = (float) ($cumToNextByLine[$line] ?? 0);

            if ((float) $target <= 0.0) {
                $target = max(1.0, $actual);
            }

            if ($user->overridden_rank) {
                $factualTurnover = $this->calcTurnoverByLine($line);
                $displayTarget = (float) $target;
                $topUp = in_array((int) $line, $manualFillLines, true)
                    ? max(0.0, $displayTarget - $factualTurnover)
                    : 0.0;
                $current = $factualTurnover + $topUp;
            } else {
                // После понижения ранга к повышению засчитывается только оборот,
                // сгенерированный после базлайна понижения.
                $rankDemotedAt = (bool) config('rank.maintenance.enabled', false)
                    ? $user->rank_demoted_at
                    : null;
                $factualTurnover = $rankDemotedAt
                    ? $this->calcTurnoverByLine($line, $rankDemotedAt->toDateTimeString())
                    : $actual;
                $topUp = 0.0;
                $current = $factualTurnover;
                $displayTarget = $target;
            }

            $current = max(0.0, (float) $current);

            Log::debug('[Partners.getProgressBarsProperty] line progress calculated', [
                'user_id' => $user->id,
                'line' => $line,
                'target' => (float) $target,
                'display_target' => (float) $displayTarget,
                'cumulative_to_next' => $cumulativeToNext,
                'base_amount' => (float) ($lineBases[$line] ?? 0),
                'effective_amount' => $actual,
                'factual_turnover' => $factualTurnover,
                'top_up' => $topUp,
                'current' => $current,
                'overridden_rank' => (bool) $user->overridden_rank,
            ]);

            $bars[] = [
                'label' => __('livewire_partners_line_income_label', ['line' => $line]),
                'current' => $current,
                'target' => $displayTarget,
                'factual_current' => $factualTurnover,
                'top_up' => $topUp,
            ];
        }

        return $bars;
    }

    private function calcEffectiveTurnoverByLine(int $line, float $baseAmount, ?string $fromDate = null): float
    {
        $allAmount = $this->calcTurnoverByLine($line);

        if (! $fromDate) {
            $effectiveAmount = $baseAmount + $allAmount;

            Log::debug('[Partners.calcEffectiveTurnoverByLine] progress without start date', [
                'user_id' => Auth::id(),
                'line' => $line,
                'base_amount' => $baseAmount,
                'before_amount' => $allAmount,
                'since_amount' => 0.0,
                'all_amount' => $allAmount,
                'effective_amount' => $effectiveAmount,
            ]);

            return $effectiveAmount;
        }

        $sinceAmount = $this->calcTurnoverByLine($line, $fromDate);
        $beforeAmount = max(0.0, $allAmount - $sinceAmount);

        $effectiveAmount = $baseAmount + $allAmount;

        Log::debug('[Partners.calcEffectiveTurnoverByLine] overridden rank progress', [
            'user_id' => Auth::id(),
            'line' => $line,
            'base_amount' => $baseAmount,
            'before_amount' => $beforeAmount,
            'since_amount' => $sinceAmount,
            'all_amount' => $allAmount,
            'effective_amount' => $effectiveAmount,
        ]);

        return $effectiveAmount;
    }

    public function regularToPartner(): void
    {
        $amount = $this->regularBalances['available'];

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount) {
            $uuid = 'RP-' . Str::random(10);

            Transaction::create([
                'uuid' => $uuid,
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER,
                'balance_type' => BalanceTypeEnum::PARTNER,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);

            Transaction::create([
                'uuid' => $uuid . '-M',
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::REGULAR_PREMIUM_TO_PARTNER_MIRROR,
                'balance_type' => BalanceTypeEnum::REGULAR_PREMIUM,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);
        });

        app(BusinessActivityLogger::class)->write(new WriteBusinessActivityData(
            type: ActivityEventTypeEnum::RegularBonusTransferredToPartner,
            userId: Auth::id(),
            subject: Auth::user(),
            feeds: [ActivityFeedTypeEnum::Partners, ActivityFeedTypeEnum::UserDetailUser],
            properties: [
                'amount' => (string) $amount,
            ],
            causer: Auth::user(),
            logName: 'partners',
            context: 'account',
        ));
    }

    public function getPackagesForTopupProperty()
    {
        return ItcPackage::query()
            ->whereHas('transaction', fn ($q) => $q->where('user_id', Auth::id()))
            ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::PRESENT, PackageTypeEnum::STAKING])
            ->with(['transaction', 'zeroing'])
            ->withSum(['profits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitsAll' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['withdrawProfitsTransactions' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->get();
    }

    public function openPackageModal(): void
    {
        $this->isModalToPackage = true;
    }

    public function closePackageModal(): void
    {
        $this->isModalToPackage = false;
    }

    /**
     * @throws ValidationException
     */
    public function transferToPackage(
        TransactionRepositoryContract $trxRepo,
        ItcPackageRepositoryContract $pkgRepo
    ): void {
        $this->validateOnly('toPackageAmount');
        $this->validateOnly('selectedPackageUuid');

        $amount = (float) str_replace(',', '.', $this->toPackageAmount);

        $pkgRepo->partnerTransferToPackage(
            Auth::id(),
            $this->selectedPackageUuid,
            $amount,
            $trxRepo
        );

        app(StartBonusAccrualContract::class)->accrue(auth()->id(), $amount);

        $this->reset('toPackageAmount', 'selectedPackageUuid');
        $this->dispatch('isPackageModal', false);
        $this->dispatch(
            'new-system-notification',
            type: 'success',
            message: __('livewire_partners_funds_deposited_to_package')
        );
    }

    public function render()
    {
        $this->transactionRepo = app(TransactionRepositoryContract::class);

        [$logRows, $logHasMore] = $this->paginateFeed(
            $this->transactionRepo->partnerLog($this->feedFetchLimit())
        );

        return view('livewire.account.partners.partners', [
            'partnerBalance' => $this->partnerBalance,
            'partnerWeek' => max(0, $this->partnerDynamics['week']),
            'partnerMonth' => max(0, $this->partnerDynamics['month']),
            'regularAvailable' => max(0, $this->regularBalances['available']),
            'regularTotal' => max(0, $this->regularBalances['total']),
            'regularWeek' => max(0, $this->regularBalances['week']),
            'progressBars' => $this->progressBars,
            'rank' => Auth::user()->rank,
            'nextRank' => Auth::user()->rank + 1 <= 8 ? Auth::user()->rank + 1 : null,
            'partners' => $this->partners,
            'availableLines' => $this->availableLines,
            'logRows' => $logRows,
            'logHasMore' => $logHasMore,
            'nicknames' => User::query()
                ->withoutGlobalScope('notBanned')
                ->whereNull('banned_at')
                ->pluck('username')
                ->all(),
            'partnerLink' => url()->query('/', ['partner' => Auth::user()->username]),
        ]);
    }
}
