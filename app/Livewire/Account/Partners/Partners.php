<?php

declare(strict_types=1);

namespace App\Livewire\Account\Partners;

use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Partners\PartnerRewardTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PackageProfitReinvest;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\PartnerRankRequirement;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Throwable;

class Partners extends Component
{
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

            $val = (float)str_replace([' ', ','], ['', '.'], $value);

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

        if (!in_array($this->line, $this->availableLines, true)) {
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

        if ($amount <= 0) {
            $this->addError('toPartnerAmount', __('livewire_partners_partner_balance_empty'));
            return;
        }

        DB::transaction(function () use ($amount) {
            Transaction::create([
                'uuid' => $uuid = 'PSM-' . Str::random(10),
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::PARTNER_TO_MAIN_SELF,
                'balance_type' => BalanceTypeEnum::MAIN,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);

            Transaction::create([
                'uuid' => $uuid . '-M',
                'amount' => $amount,
                'trx_type' => TrxTypeEnum::PARTNER_TO_MAIN_SELF_MIRROR,
                'balance_type' => BalanceTypeEnum::PARTNER,
                'user_id' => Auth::id(),
                'accepted_at' => now(),
            ]);
        });

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

            $amount = (float)str_replace(',', '.', $this->toPartnerAmount);

            DB::transaction(function () use ($receiver, $amount) {
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
        $rangeDelta = function (Carbon $since): float {
            $debit = Transaction::query()
                ->where('user_id', Auth::id())
                ->where('balance_type', BalanceTypeEnum::PARTNER)
                ->whereIn('trx_type', TrxTypeEnum::getDebits())
                ->whereNull('rejected_at')
                ->where('created_at', '>=', $since)
                ->sum('amount');

            $credit = Transaction::query()
                ->where('user_id', Auth::id())
                ->where('balance_type', BalanceTypeEnum::PARTNER)
                ->whereIn('trx_type', TrxTypeEnum::getCredits())
                ->whereNull('rejected_at')
                ->where('created_at', '>=', $since)
                ->sum('amount');

            return $debit - $credit;
        };

        return [
            'week' => $rangeDelta(now()->subWeek()),
            'month' => $rangeDelta(now()->subMonth()),
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

        $buy = Transaction::whereIn('user_id', $ids)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->whereNotNull('accepted_at');

        if ($fromDate) {
            $buy->where('accepted_at', '>=', $fromDate);
        }

        $buySum = $buy->sum('amount');

//        Log::channel('source')->debug('---buySum----');
//        Log::channel('source')->debug($buySum);
//        Log::channel('source')->debug('---buySum----');

        $reinvest = ItcPackage::query()
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->whereIn('transactions.user_id', $ids);

        $reinvestSum = (float)$reinvest
            ->withSum([
                'reinvestProfitsAll' => function ($q) use ($fromDate) {
                    if ($fromDate) {
                        $q->where('created_at', '>=', $fromDate);
                    }
                }
            ], 'amount')
            ->get()
            ->sum('reinvest_profits_all_sum_amount');

//        Log::channel('source')->debug('---reinvestSum----');
//        Log::channel('source')->debug($reinvestSum);
//        Log::channel('source')->debug('---reinvestSum----');

        return $buySum + $reinvestSum;
    }

    public function getProgressBarsProperty(): array
    {
        $user = Auth::user();
        $next = max(1, $user->rank + 1);

        // Требования ТОЛЬКО для следующего ранга (R+1)
        $reqs = PartnerRankRequirement::whereHas('rank', fn($q) => $q->where('rank', $next))->get();

        $bars = [];

        $fromDate = null;
        $personalBase = 0;
        $lineBases = [];

        if ($user->overridden_rank && $user->overridden_rank_from) {
            $fromDate = $user->overridden_rank_from;

            // стартовые базы для ранга, с которого начали считать вручную
            $baseReqs = PartnerRankRequirement::whereHas('rank',
                fn($q) => $q->where('rank', $user->rank))->get();

            $personalBase = $baseReqs->firstWhere('line', null)?->personal_deposit ?? 0;
            $lineBases = PartnerRankRequirement::query()
                ->whereNotNull('line')
                ->whereHas('rank', fn($q) => $q->where('rank', '<=', $user->rank))
                ->selectRaw('line, SUM(required_turnover) as total')
                ->groupBy('line')
                ->pluck('total', 'line')
                ->all();
        }

//        Log::channel('source')->debug('------------------------');
//        Log::channel('source')->debug($user->overridden_rank);
//        Log::channel('source')->debug($user->overridden_rank_from);
//        Log::channel('source')->debug($personalBase);
//        Log::channel('source')->debug('------------------------');

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
                ->sum(fn($p) => (float)$p->transaction->amount +
                    (float)$p->reinvest_profits_all_sum_amount
                );

            // Минимум для текущего (override) ранга из базы, как раньше
            $minForRank = (float)$personalBase;

            if ($user->overridden_rank && $fromDate && $allDeposit < $minForRank) {
                // "минимум + прирост с даты override"
                $since = (clone $baseQuery)
                    ->withSum([
                        'reinvestProfitsAll' => function ($q) use ($fromDate) {
                            $q->when($fromDate, fn($qq) => $qq->where('created_at', '>=', $fromDate));
                        }
                    ], 'amount')
                    ->get()
                    ->sum(function ($p) use ($fromDate) {
                        $buy = ($p->transaction?->accepted_at && $p->transaction->accepted_at >= $fromDate)
                            ? (float)$p->transaction->amount
                            : 0.0;

                        return $buy + (float)$p->reinvest_profits_all_sum_amount;
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

        // Сумма требований по каждой линии от ранга 1 до R+1 (кумулятивно)
        $cumToNextByLine = PartnerRankRequirement::query()
            ->whereNotNull('line')
            ->whereHas('rank', fn($q) => $q->where('rank', '<=', $next))
            ->selectRaw('line, SUM(required_turnover) as total')
            ->groupBy('line')
            ->pluck('total', 'line'); // [line => cum(1..R+1)]

        // Прогресс по линиям: current = сколько уже НАБРАНО в пределах R+1
        foreach ($reqs->whereNotNull('line') as $r) {
            $line = $r->line;
            $target = $r->required_turnover; // требование следующего ранга (R+1)

            // что уже набрано: стартовая база (если есть) + оборот с fromDate
            $actual = ($lineBases[$line] ?? 0) + $this->calcTurnoverByLine($line, $fromDate);

            // current = target - (cum(1..R+1) - actual)  == target + actual - cum(1..R+1)
            $current = $target + $actual - ($cumToNextByLine[$line] ?? 0);

//            Log::channel('source')->debug('--------------');
//            Log::channel('source')->debug($actual);
//            Log::channel('source')->debug($cumToNextByLine[$line]);
//            Log::channel('source')->debug($current);
//            Log::channel('source')->debug($target);
//            Log::channel('source')->debug('--------------');

            $bars[] = [
                'label' => __('livewire_partners_line_income_label', ['line' => $line]),
                'current' => $current,
                'target' => $target,
            ];
        }

        return $bars;
    }

    public function regularToPartner(): void
    {
        $amount = $this->regularBalances['available'];
        if ($amount <= 0) return;

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
    }

    public function getPackagesForTopupProperty()
    {
        return ItcPackage::query()
            ->whereHas('transaction', fn($q) => $q->where('user_id', Auth::id()))
            ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::PRESENT])
            ->with(['transaction', 'zeroing'])
            ->withSum(['profits' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitsAll' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfits' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['withdrawProfitsTransactions' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitWithdraws' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
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
        ItcPackageRepositoryContract  $pkgRepo
    ): void
    {

//        Log::channel('source')->debug($this->selectedPackageUuid);
//        Log::channel('source')->debug($this->toPackageAmount);

        $this->validateOnly('toPackageAmount');
        $this->validateOnly('selectedPackageUuid');

        $amount = (float)str_replace(',', '.', $this->toPackageAmount);

//        Log::channel('source')->debug($amount);

        $pkgRepo->partnerTransferToPackage(
            Auth::id(),
            $this->selectedPackageUuid,
            $amount,
            $trxRepo
        );

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

        $logRows = $this->transactionRepo->partnerLog();
//        Log::channel('source')->debug($this->regularBalances['available']);
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
            'nicknames' => User::query()
                ->withoutGlobalScope('notBanned')
                ->whereNull('banned_at')
                ->pluck('username')
                ->all(),
            'partnerLink' => url()->query('/', ['partner' => Auth::user()->username]),
        ]);
    }
}
