<?php

namespace App\Http\Controllers;

use App\Contracts\Packages\ItcPackageRepositoryContract;
use App\Contracts\Packages\PackageReinvestRepositoryContract;
use App\Contracts\Transactions\TransactionRepositoryContract;
use App\Contracts\Logs\LogRepositoryContract;
use App\Dto\Transactions\CreateTransactionDto;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TransactionStatusEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Helpers\Notify;
use App\Models\ItcPackage;
use App\Models\PackageProfit;
use App\Models\PackageProfitReinvest;
use App\Models\PackageProfitReinvestWithdraw;
use App\Models\PackageProfitWithdraw;
use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSummary;
use App\MoonShine\Pages\ItcPackage\ItcPackageIndexPage;
use App\MoonShine\Resources\ItcPackageResource;
use App\Traits\Moonshine\CanStatusModifyTrait;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MoonShine\Enums\ToastType;
use MoonShine\MoonShineRequest;
use Illuminate\Support\Str;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use RuntimeException;
use Throwable;

class AdminController extends Controller
{
    use CanStatusModifyTrait;

    public function getItemID(): string|null
    {
        return request('uuid');
    }

    public function createItcPackagesProfits(MoonShineRequest $request)
    {
        ItcPackage::query()
            ->with('transaction')
            ->where('type', '!=', PackageTypeEnum::ARCHIVE)
            ->whereNot(function ($q) {
                $q->where('type', PackageTypeEnum::PRESENT)
                    ->where('work_to', '<=', now())
                    ->whereDoesntHave('reinvestProfits', fn ($qq) => $qq->where('amount', '>', 0));
            })
            ->withSum(['reinvestProfits' => fn($query) => $query->select(DB::raw('COALESCE(SUM(amount), 0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->chunkById(50, function (Collection $packages) use ($request) {
            $packages->each(function (ItcPackage $package) use ($request) {
                $base = ($package->type === PackageTypeEnum::PRESENT && Carbon::parse($package->work_to)->lte(now()))
                    ? BigDecimal::of($package->reinvest_profits_sum_amount)
                    : BigDecimal::of($package->transaction->amount)
                        ->plus($package->reinvest_profits_sum_amount)
                        ->plus($package->partner_transfers_sum_amount ?? 0)
                        ->plus($package->reinvest_to_body_sum_amount ?? 0)
                        ->minus($package->balance_withdraws_sum_amount ?? 0);


                $profit = PackageProfit::query()->create([
                    'uuid'         => 'PP-' . Str::random(10),
                    'package_uuid' => $package->uuid,
                    'amount'       => $base
                        ->multipliedBy(
                            BigDecimal::of($package->month_profit_percent)
                                ->dividedBy('3100', 8, RoundingMode::HALF_EVEN)
                        )
                        ->multipliedBy(
                            BigDecimal::of($request->input('profit_percent'))
                                ->dividedBy('100', 8, RoundingMode::HALF_EVEN)
                        )
                        ->multipliedBy('7'),
                ]);

//                Log::channel('source')->debug($package?->transaction?->user_id);

                $u = User::withoutGlobalScope('notBanned')->where('id', $package->transaction?->user_id)->firstOrFail();

                Notify::dividends($u, $profit->amount->toScale(2, RoundingMode::HALF_EVEN), $package->created_at?->format('d/m/y'), $package->type->getCAPSName(), $profit->uuid);

            });
        });

        return back();
    }

    public function addAmountToBalance(MoonShineRequest $request, TransactionRepositoryContract $transactionRepo)
    {
        $userId      = $request->input('user_id');
        $balanceType = BalanceTypeEnum::from($request->input('balance_type'));
        $amount      = (float) $request->input('amount');

        // 1) Получаем старое значение нужного поля из summary
        $summary = UserSummary::where('user_id', $userId)->firstOrFail();
        $field     = $balanceType === BalanceTypeEnum::MAIN
            ? 'investments_sum'
            : 'partner_balance';
        $oldValue  = (float) $summary->$field;

        // 2) Создаём транзакцию
        $tx = $transactionRepo->commonStore(new CreateTransactionDto(
            userId:      $userId,
            trxType:     TrxTypeEnum::HIDDEN_DEPOSIT,
            balanceType: $balanceType,
            amount:      (string) $amount,
            acceptedAt:  Carbon::now()->toDateTimeString(),
            prefix:      'HD-'
        ));

        // 3) После сохранения summary обновится триггером — подгружаем новое значение
        $summary->refresh();
        $newValue = (float) $summary->$field;

        // 4) Логируем через ваш сервис
        app(LogRepositoryContract::class)->updated(
            $tx,
            $balanceType === BalanceTypeEnum::MAIN
                ? 'update_investments_sum'
                : 'update_partner_balance',
            [$field => $oldValue],
            [$field => $newValue],
            $userId
        );
        return MoonShineJsonResponse::make()
            ->toast('Отредактировано', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    public function updateItcPackage(string $uuid, MoonShineRequest $request)
    {
        $package = ItcPackage::query()->where('uuid', $uuid)->first();

        $oldAmount    = $package->transaction->amount;
        $oldType      = $package->type;
        $oldPercent   = $package->month_profit_percent;
        $targetUserId = $package->transaction->user_id;
        $oldCreatedAt = $package->created_at->toDateString();

        $package->transaction->amount = $request->input('amount');
        $newCreatedAt = Carbon::parse($request->input('created_at'))->toDateString();


        $package->created_at = $newCreatedAt;
        $package->type = $request->input('type');
        $package->month_profit_percent = $request->input('profit_percent');

        $package->transaction->save();
        $package->save();

        $logRepo = app(LogRepositoryContract::class);

        if ((float)$oldAmount !== (float)$package->transaction->amount) {
            $logRepo->updated(
                $package->transaction,
                'update_itc_package_amount',
                ['amount' => (string)$oldAmount],
                ['amount' => (string)$package->transaction->amount],
                $targetUserId
            );
        }

        if ($oldType !== $package->type) {
            $logRepo->updated(
                $package,
                'update_itc_package_type',
                ['type' => $oldType],
                ['type' => $package->type],
                $targetUserId
            );
        }

        if ((float)$oldPercent !== (float)$package->month_profit_percent) {
            $logRepo->updated(
                $package,
                'update_itc_package_profit_percent',
                ['month_profit_percent' => (string)$oldPercent],
                ['month_profit_percent' => (string)$package->month_profit_percent],
                $targetUserId
            );
        }

        if ($oldCreatedAt !== $newCreatedAt) {
            $logRepo->updated(
                $package,
                'update_itc_package_created_at',
                ['created_at' => $oldCreatedAt],
                ['created_at' => $newCreatedAt],
                $targetUserId
            );
        }

        $referer = request()->headers->get('referer', '');

        if (! str_contains($referer, 'tab=')) {
            $referer .= (str_contains($referer, '?') ? '&' : '?') . 'tab=packages';
        }

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_package_updated'), ToastType::SUCCESS)
            ->redirect($referer);
    }

    public function reinvest(string $uuid)
    {
        $package = ItcPackage::query()->where('uuid', $uuid)->first();
    }

    public function addPartner(MoonShineRequest $request): MoonShineJsonResponse
    {
        if (is_int($request->input('user'))) {
            $user = User::query()
                ->where('id', $request->input('user'))
                ->first();
        } else {
            $user = User::query()
                ->where('username', $request->input('user'))
                ->first();
        }

        $referer = request()->headers->get('referer', '');

        if (! str_contains($referer, 'tab=')) {
            $referer .= (str_contains($referer, '?') ? '&' : '?') . 'tab=referrals';
        }

        if (is_null($user)) {
            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_user_not_found', ['user' => $request->input("user")]), ToastType::ERROR)
                ->redirect($referer);
        }

        if (Partner::query()->where('user_id', $user->id)->exists()) {
            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_partner_already_attached'), ToastType::ERROR)
                ->redirect($referer);
        }

        try {
            DB::transaction(function () use ($request, $user) {
                // 2) Создаём связь Partner
                Partner::query()->create([
                    'user_id'    => $user->id,
                    'partner_id' => $request->input('user_id'),
                ]);

                // 3) Пытаемся создать связь в closure-таблице
                $parentId = (int) $request->input('user_id');
                $userId   = (int) $user->id;

                // Все узлы поддерева: пользователь + его потомки (depth относительно $userId)
                $subtree = PartnerClosure::query()
                    ->where('ancestor_id', $userId)
                    ->get(['descendant_id', 'depth']);

                // Все предки нового партнёра (включая его самого с depth=0)
                $newAncestors = PartnerClosure::query()
                    ->where('descendant_id', $parentId)
                    ->get(['ancestor_id', 'depth']);

                // Вставляем пары: каждый новый предок -> каждый узел поддерева
                // depth = depth(ancestor -> parent) + 1 + depth(user -> d)
                $bulk = [];
                foreach ($newAncestors as $na) {
                    foreach ($subtree as $sd) {
                        $bulk[] = [
                            'ancestor_id'   => (int) $na->ancestor_id,
                            'descendant_id' => (int) $sd->descendant_id,
                            'depth'         => (int) $na->depth + 1 + (int) $sd->depth,
                        ];
                        if (count($bulk) >= 1000) {
                            PartnerClosure::insert($bulk);
                            $bulk = [];
                        }
                    }
                }
                if (!empty($bulk)) {
                    PartnerClosure::insert($bulk);
                }
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                // та же ошибка, что и для Partner
                return MoonShineJsonResponse::make()
                    ->toast(__('admin_controller_partner_already_attached'), ToastType::ERROR)
                    ->redirect($referer);
            }

            // прочие ошибки — общий ответ
            report($e);

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_failed_to_add_partner'), ToastType::ERROR)
                ->redirect($referer);
        }

        Artisan::call("users:recalc-rank --no-bonus");

        // 4) Успех
        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_partner_added'), ToastType::SUCCESS)
            ->redirect($referer);
    }

    public function updatePartner(MoonShineRequest $request, int $oldPartnerId): MoonShineJsonResponse
    {
        $userId = (int) $request->input('user_id');
        $newPartnerId = (int) $request->input('partner_id');
        $referer = request()->headers->get('referer', '');

        if (! str_contains($referer, 'tab=')) {
            $referer .= (str_contains($referer, '?') ? '&' : '?') . 'tab=referrals';
        }

        try {
            DB::transaction(function () use ($userId, $oldPartnerId, $newPartnerId) {
                $partner = Partner::query()
                    ->where('user_id', $userId)
                    ->where('partner_id', $oldPartnerId)
                    ->firstOrFail();

                $partner->partner_id = $newPartnerId;
                $partner->saveOrFail();

                $subtree = PartnerClosure::query()
                    ->where('ancestor_id', $userId)
                    ->get(['descendant_id', 'depth']); // depth относительно $userId

                $descendantIds = $subtree->pluck('descendant_id')->all();

                // 2) Старые предки пользователя (кроме самого пользователя)
                $oldAncestorIds = PartnerClosure::query()
                    ->where('descendant_id', $userId)
                    ->where('depth', '>', 0)
                    ->pluck('ancestor_id')
                    ->all();

                // 3) Удаляем все старые пути: старые предки -> любое d из поддерева
                if ($oldAncestorIds && $descendantIds) {
                    PartnerClosure::query()
                        ->whereIn('ancestor_id', $oldAncestorIds)
                        ->whereIn('descendant_id', $descendantIds)
                        ->delete();
                }

                // 4) Новые предки: все предки нового партнёра (включая его самого с depth=0)
                $newAncestors = PartnerClosure::query()
                    ->where('descendant_id', $newPartnerId)
                    ->get(['ancestor_id', 'depth']); // depth относительно $newPartnerId
                // 5) Вставляем новые пути: каждый новый предок -> каждый d из поддерева
                //    depth = depth(ancestor -> newPartner) + 1 + depth(user -> d)
                $bulk = [];
                foreach ($newAncestors as $na) {
                    foreach ($subtree as $sd) {
                        $bulk[] = [
                            'ancestor_id'   => (int) $na->ancestor_id,
                            'descendant_id' => (int) $sd->descendant_id,
                            'depth'         => (int) $na->depth + 1 + (int) $sd->depth,
                        ];
                        if (count($bulk) >= 1000) {
                            PartnerClosure::query()->insert($bulk);
                            $bulk = [];
                        }
                    }
                }

                if (!empty($bulk)) {
                    PartnerClosure::query()->insert($bulk);
                }
                app(LogRepositoryContract::class)->updated(
                    $partner,
                    'update_referrer',
                    ['partner_id' => $oldPartnerId],
                    ['partner_id' => $newPartnerId],
                    $oldPartnerId
                );
            });
        } catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_failed_to_transfer_partner'), ToastType::ERROR)
                ->redirect($referer);
        }

        Artisan::call("users:recalc-rank --no-bonus");

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_referral_updated'), ToastType::SUCCESS)
            ->redirect($referer);
    }

    public function updateRank(Request $request)
    {
        User::query()->where('id', $request->input('user_id'))->update([
            'rank' => $request->input('rank')
        ]);

        return back();
    }

    public function bulkWithdraw(
        Request $request,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo,
    ): MoonShineJsonResponse
    {
        $uuidsString = $request->query('uuids', '');
        $uuids = $uuidsString !== '' ? explode(',', $uuidsString) : [];
        $errors = [];
        foreach ($uuids as $uuid) {
            try {
                $reinvestRepo->withdraw($uuid, $transactionRepo);
            } catch (Throwable $e) {
                $errors[] = "Ошибка для $uuid: " . $e->getMessage();
            }
        }

        if ($errors) {
            return MoonShineJsonResponse::make()
                ->toast(implode("\n", $errors), 'error')
                ->redirect(url()->previous());
        }

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_reinvest_withdraw_bulk_success'))
            ->redirect(url()->previous());
    }

    public function withdrawOneProfitReinvest(
        string $reinvestUuid,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo,
    ): MoonShineJsonResponse  {
        try {
            $reinvestRepo->withdraw($reinvestUuid, $transactionRepo);

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_reinvest_withdrawn'))
                ->redirect(url()->previous());
        } catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }

    public function deleteProfitReinvest(string $reinvestUuid): MoonShineJsonResponse
    {
        $reinvest = PackageProfitReinvest::where('uuid', $reinvestUuid)->firstOrFail();

        $package = ItcPackage::where('uuid', $reinvest->package_uuid)
            ->with('transaction')
            ->firstOrFail();
        $targetUserId = $package->transaction->user_id;

//        Log::channel('source')->debug($reinvest->matured_at);

        app(LogRepositoryContract::class)->updated(
            $reinvest,
            'delete_package_reinvest_profit',
            [
                'amount'     => (string)$reinvest->amount,
                'matured_at' => Carbon::parse($reinvest->matured_at)->toDateTimeString()
            ],
            [],
            $targetUserId
        );

        DB::transaction(function () use ($reinvest) {
            // если есть связь — удаляем
            if ($reinvest->hasprofitLink()) {
                $reinvest->profitLink()->delete();
            }

            // затем удаляем сам реинвест
            $reinvest->delete();
        });


        $reinvest->delete();

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_reinvest_deleted'), 'success')
            ->redirect(url()->previous());
    }

    public function removeAllProfitsAndReinvests(string $reinvestUuid): MoonShineJsonResponse
    {
        $reinvest = PackageProfitReinvest::query()
            ->where('uuid', $reinvestUuid)
            ->with('profitLink.profit')
            ->firstOrFail();

        $packageUuid = $reinvest->package_uuid;
        $targetAt    = $reinvest->created_at;

        if ($reinvest->hasprofitLink()) {
            DB::transaction(function () use ($reinvest) {
                // удалить сам дивиденд, если подгружен
                if ($reinvest->profitLink?->profit) {
                    $reinvest->profitLink->profit->delete();
                }
                // удалить связь
                $reinvest->profitLink()->delete();
                // удалить реинвест
                $reinvest->delete();
            });

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_reinvest_and_dividend_deleted'), 'success')
                ->redirect(url()->previous());
        }

        try {
            DB::transaction(function () use ($reinvest, $packageUuid, $targetAt) {

                $reinvestEvents = PackageProfitReinvest::query()
                    ->where('package_uuid', $packageUuid)
                    ->where('created_at', '<=', $targetAt)
                    ->orderBy('created_at')
                    ->orderBy('uuid')
                    ->get(['uuid', 'amount', 'created_at']);

                $withdrawEvents = PackageProfitWithdraw::query()
                    ->where('package_uuid', $packageUuid)
                    ->join('transactions as t', 'package_profit_withdraws.uuid', '=', 't.uuid')
                    ->where('t.created_at', '<=', $targetAt)
                    ->orderBy('t.created_at')
                    ->orderBy('t.uuid')
                    ->get(['t.uuid', 't.amount', 't.created_at']);

                $events = collect();

                foreach ($reinvestEvents as $e) {
                    $events->push([
                        'type'   => $e->uuid === $reinvest->uuid ? 'target-reinvest' : 'reinvest',
                        'uuid'   => $e->uuid,
                        'at'     => $e->created_at,
                        'amount' => BigDecimal::of((string) $e->amount),
                    ]);
                }
                foreach ($withdrawEvents as $e) {
                    $events->push([
                        'type'   => 'withdraw',
                        'uuid'   => $e->uuid,
                        'at'     => $e->created_at,
                        'amount' => BigDecimal::of((string) $e->amount),
                    ]);
                }

                $events = $events->sortBy([['at', 'asc'], ['uuid', 'asc']])->values();

                $consumed = [];
                $targetProfitUuids = [];

                $pickTail = function (BigDecimal $need, $eventAt, array $exclude) use ($packageUuid) {
                    $picked = [];
                    $sum = BigDecimal::of('0');

                    $profits = PackageProfit::query()
                        ->where('package_uuid', $packageUuid)
                        ->where('created_at', '<=', $eventAt)
                        ->when(!empty($exclude), fn ($q) => $q->whereNotIn('uuid', $exclude))
                        ->orderBy('created_at', 'desc')
                        ->orderBy('uuid', 'desc')
                        ->get(['uuid', 'amount', 'created_at']);

                    foreach ($profits as $p) {
                        $a = BigDecimal::of((string) $p->amount);

                        if ($sum->plus($a)->compareTo($need) > 0) {
                            break;
                        }

                        $sum = $sum->plus($a);
                        $picked[] = $p->uuid;

                        if ($sum->compareTo($need) === 0) {
                            return $picked;
                        }
                    }

                    return null;
                };

                foreach ($events as $ev) {
                    $picked = $pickTail($ev['amount'], $ev['at'], $consumed);

                    if ($picked === null) {
                        throw new RuntimeException(
                            "Не удалось сопоставить событие {$ev['uuid']} с дивидендами (package {$packageUuid})"
                        );
                    }

                    if ($ev['type'] === 'target-reinvest') {
                        $targetProfitUuids = $picked;
                        break;
                    }

                    $consumed = array_merge($consumed, $picked);
                }

                if (empty($targetProfitUuids)) {
                    throw new RuntimeException("Пустой набор дивидендов для реинвеста {$reinvest->uuid}");
                }

//                Log::channel('source')->debug($targetProfitUuids);
//
//                $sumAmount = PackageProfit::query()
//                    ->whereIn('uuid', $targetProfitUuids)
//                    ->selectRaw('COALESCE(SUM(amount),0) AS total')
//                    ->value('total');
//
//                Log::channel('source')->debug('targetProfitUuids sum: ' . $sumAmount);

                // Удаляем дивиденды и сам реинвест
                PackageProfit::query()
                    ->whereIn('uuid', $targetProfitUuids)
                    ->get()
                    ->each
                    ->delete();

                $reinvest->delete();
            });

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_reinvest_and_dividend_deleted'), 'success')
                ->redirect(url()->previous());

        } catch (Throwable $e) {
            report($e);

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_matching_failed'), 'error')
                ->redirect(url()->previous());
        }
    }


    public function extendProfitReinvest(string $reinvestUuid): MoonShineJsonResponse
    {
        $r = PackageProfitReinvest::where('uuid', $reinvestUuid)->firstOrFail();

        $oldM = Carbon::parse($r->matured_at)?->toDateTimeString();

        $r->matured_at = Carbon::parse($r->matured_at ?? $r->created_at)
            ->addDays(180);
        $r->save();

        $package = ItcPackage::where('uuid', $r->package_uuid)
            ->with('transaction')
            ->firstOrFail();
        $targetUserId = $package->transaction->user_id;

        app(LogRepositoryContract::class)->updated(
            $r,
            'extend_package_reinvest_profit',
            ['matured_at' => $oldM],
            ['matured_at' => $r->matured_at->toDateTimeString()],
            $targetUserId
        );

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_reinvest_extend_success'), 'success')
            ->redirect(url()->previous());
    }

    public function suggestUsers(Request $request): MoonShineJsonResponse
    {
        $query = trim((string) $request->get('query', ''));

        $results = User::query()
            ->when($query !== '', fn($q) =>
            $q->where('username', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
            )
            ->limit(10)
            ->get(['id', 'username', 'email'])
            ->map(fn(User $user) => [
                'value' => $user->id,
                'label' => "{$user->username}, {$user->email}",
            ]);

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_withdraw_referer'), 'success')
            ->redirect(url()->previous());
    }

    public function closeItcPackage(
        string $uuid,
        ItcPackageRepositoryContract $itcPackageRepo,
        TransactionRepositoryContract $transactionRepo,
        PackageReinvestRepositoryContract $reinvestRepo
    ): MoonShineJsonResponse {
        try {
            $itcPackageRepo->closePackage($uuid, $transactionRepo, $reinvestRepo);

            return MoonShineJsonResponse::make()
                ->toast(__('admin_controller_package_closed_success'))
                ->redirect(url()->previous());
        } catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error')
                ->redirect(url()->previous());
        }
    }

    public function withdrawUpdate(Request $request): MoonShineJsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:'
                . TransactionStatusEnum::MODERATE->getName() . ','
                . TransactionStatusEnum::ACCEPTED->getName() . ','
                . TransactionStatusEnum::REJECTED->getName(),
        ]);

        // обновили сумму и получили модель
        $this->updateAmount($data['amount']);

        // переключаем статус
        if ($data['status'] === TransactionStatusEnum::ACCEPTED->getName()) {
            return $this->accept();
        }

        if ($data['status'] === TransactionStatusEnum::REJECTED->getName()) {
            return $this->reject();
        }

        if ($data['status'] === TransactionStatusEnum::MODERATE->getName()) {
            return $this->toModerate();
        }

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_withdraw_updated'), ToastType::SUCCESS)
            ->redirect($request->headers->get('referer'));
    }
}
