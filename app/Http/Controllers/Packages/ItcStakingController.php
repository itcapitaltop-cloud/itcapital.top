<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Enums\Activity\ActivityFeedTypeEnum;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Enums\LogActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\ItcPackage;
use App\Models\TokenRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\Package\Staking\StakingPerformanceService;
use App\Services\Package\Staking\StakingPurchaseService;
use App\Services\Token\TokenRateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use MoonShine\Enums\ToastType;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;

final class ItcStakingController extends Controller
{
    /**
     * @param MoonShineRequest $request
     * @return RedirectResponse
     */
    public function changePercentage(MoonShineRequest $request): RedirectResponse
    {
        ItcPackage::query()
            ->where('type', PackageTypeEnum::STAKING)
            ->chunkById(100, function (Collection $packages) use ($request): void {
                $packages->each(function (ItcPackage $package) use ($request): void {
                    $package->update([
                        'month_profit_percent' => $request->get('percent'),
                    ]);
                });
            });

        activity('admin')
            ->causedBy(auth()->user())
            ->withProperties([
                'percent' => $request->input('percent'),
            ])
            ->log('admin_package_staking_changed_percentage');

        return back();
    }

    /**
     * @param MoonShineRequest $request
     * @param \App\Settings\GeneralSetting $generalSetting
     * @return RedirectResponse
     */
    public function changeStartBonusPercentage(MoonShineRequest $request, GeneralSetting $generalSetting): RedirectResponse
    {
        if ($request->has('user_id')) {
            $package = ItcPackage::query()->findOrFail($request->input('package_id'));

            User::query()->findOrFail($request->input('user_id'))->setSettings([
                'start_bonus_staking_percent' => $request->input('percent'),
            ]);

            activity('packages')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'percent' => $request->input('percent'),
                    'package_uuid' => $package->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('admin_package_changed_staking_start_bonus_percent');

            return back();
        }

        $generalSetting->start_bonus_staking_percent = $request->input('percent');
        $generalSetting->save();

        activity('admin')
            ->causedBy(auth()->user())
            ->withProperties([
                'percent' => $request->get('percent'),
            ])
            ->log('admin_package_staking_changed_start_bonus_percentage');

        return back();
    }

    /**
     * @param MoonShineRequest $request
     * @param \App\Settings\GeneralSetting $generalSetting
     * @return RedirectResponse
     */
    public function changeRegularPercentage(MoonShineRequest $request, GeneralSetting $generalSetting): RedirectResponse
    {
        if ($request->has('user_id')) {
            $package = ItcPackage::query()->findOrFail($request->input('package_id'));

            User::query()->findOrFail($request->input('user_id'))->setSettings([
                'regular_staking_percent' => $request->input('percent'),
            ]);

            activity('packages')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'percent' => $request->input('percent'),
                    'package_uuid' => $package->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                ])
                ->log('admin_package_changed_staking_regular_percent');

            return back();
        }

        $generalSetting->regular_staking_percent = $request->input('percent');
        $generalSetting->save();

        activity('admin')
            ->causedBy(auth()->user())
            ->withProperties([
                'percent' => $request->input('percent'),
            ])
            ->log('admin_package_staking_changed_regular_percentage');

        return back();
    }

    public function changeTokenRate(MoonShineRequest $request, TokenRateResolver $tokenRateResolver): MoonShineJsonResponse
    {
        $tokenRateId = $request->integer('token_rate_id') ?: null;
        $existingTokenRate = $tokenRateId !== null
            ? TokenRate::query()->find($tokenRateId)
            : TokenRate::query()->whereDate('effective_from', $request->input('effective_from'))->first();
        $oldValues = $existingTokenRate instanceof TokenRate
            ? $this->tokenRateActivityValues($existingTokenRate)
            : [];

        $admin = auth()->user();
        $tokenRate = $tokenRateResolver->upsertRate(
            $request->input('effective_from'),
            (float) $request->input('rate'),
            $tokenRateId
        );

        activity('admin')
            ->performedOn($tokenRate)
            ->causedBy($admin)
            ->withProperties([
                'feeds' => [ActivityFeedTypeEnum::GlobalAdmin->value],
                'admin_login' => $this->adminActivityLogin($admin),
                'old_values' => $oldValues,
                'new_values' => $this->tokenRateActivityValues($tokenRate),
            ])
            ->log(LogActionTypeEnum::UPSERT_TOKEN_RATE->value);

        return MoonShineJsonResponse::make()
            ->toast('Курс токена сохранен', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * @return array{effective_from:string,rate:string}
     */
    private function tokenRateActivityValues(TokenRate $tokenRate): array
    {
        return [
            'effective_from' => $tokenRate->effective_from?->format('d.m.Y') ?? '',
            'rate' => (string) $tokenRate->rate,
        ];
    }

    private function adminActivityLogin(mixed $admin): string
    {
        foreach (['username', 'login', 'email', 'name'] as $attribute) {
            $value = data_get($admin, $attribute);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return '';
    }

    public function deleteTokenRate(int $tokenRateId, TokenRateResolver $tokenRateResolver): MoonShineJsonResponse
    {
        $tokenRateResolver->deleteRate($tokenRateId);

        return MoonShineJsonResponse::make()
            ->toast('Курс токена удален', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * @param MoonShineRequest $request
     * @param string $uuid
     * @return MoonShineJsonResponse
     */
    public function editStaking(MoonShineRequest $request, string $uuid): MoonShineJsonResponse
    {
        $request->validate([
            'profit_percent' => ['required', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'manual_profit' => ['nullable', 'numeric', 'min:0'],
            // TopUpBonus is a bookkeeping shadow of a purchase and is excluded from
            // every token sum, so accepting it here would silently accrue nothing.
            'manual_accrual_type' => [
                'nullable',
                Rule::enum(StakingTransactionAccrualEnum::class)
                    ->except(StakingTransactionAccrualEnum::TopUpBonus),
            ],
        ]);

        $transaction = Transaction::query()
            ->whereUuid($uuid)
            ->firstOrFail();

        $package = ItcPackage::query()
            ->with(['transaction', 'stakingPurchases', 'stakingTransactionAccruals'])
            ->whereUuid($uuid)
            ->firstOrFail();

        $oldAmount = $transaction->amount;
        $oldPercent = $package->month_profit_percent;
        $manualProfit = round((float) $request->input('manual_profit', 0), 2);
        $topUpTokens = round((float) $request->input('amount', 0), 2);
        $oldTotalTokens = app(StakingPerformanceService::class)->forPackage($package)['total_tokens'];
        $manualAccrualType = StakingTransactionAccrualEnum::from(
            (string) $request->input('manual_accrual_type', StakingTransactionAccrualEnum::Profit->value)
        );

        if ($topUpTokens > 0) {
            app(StakingPurchaseService::class)->addPurchaseByTokens($package, $topUpTokens, $package->transaction->user_id);
            $package->refresh();
            $package->load(['transaction', 'stakingPurchases', 'stakingTransactionAccruals']);
            $transaction = $package->transaction;
        }

        if ($manualProfit > 0) {
            new StakingAccrualService()
                ->accrue($package, $manualAccrualType, $manualProfit, $package->transaction->user_id);
        }

        $newPercent = $request->input('profit_percent');
        $packageAttributes = ['month_profit_percent' => $newPercent];

        // A manual per-package edit pins the rate so a later package-definition
        // edit does not cascade over the admin's intentional override.
        if ((float) $oldPercent !== (float) $newPercent) {
            $packageAttributes['profit_percent_overridden'] = true;
        }

        $package->update($packageAttributes);

        $newTotalTokens = app(StakingPerformanceService::class)->forPackage($package)['total_tokens'];

        if (round((float) $oldTotalTokens, 2) !== round((float) $newTotalTokens, 2)) {
            activity('admin')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'package_uuid' => $transaction->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'amount' => round((float) $newTotalTokens, 2),
                    'old_amount' => round((float) $oldTotalTokens, 2),
                ])
                ->log('admin_package_changed_amount');
        }

        if ($package->wasChanged('month_profit_percent')) {
            activity('admin')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'package_uuid' => $transaction->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'amount' => $topUpTokens,
                    'percent' => $oldPercent,
                    'old_percent' => $package->month_profit_percent,
                ])
                ->log('admin_package_changed_percentage');
        }

        if ($manualProfit > 0) {
            activity('admin')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'package_uuid' => $transaction->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'amount' => $manualProfit,
                    'accrual_type' => $manualAccrualType->value,
                ])
                ->log('admin_package_added_manual_profit');
        }

        $referer = request()->headers->get('referer', '');

        if (! str_contains($referer, 'tab=')) {
            $referer .= (str_contains($referer, '?') ? '&' : '?') . 'tab=packages';
        }

        return MoonShineJsonResponse::make()
            ->toast(__('admin_controller_package_updated'), ToastType::SUCCESS)
            ->redirect($referer);
    }
}
