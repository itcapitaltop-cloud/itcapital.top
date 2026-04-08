<?php

declare(strict_types=1);

namespace App\Http\Controllers\Packages;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Http\Controllers\Controller;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Package\Staking\StakingAccrualService;
use App\Services\Token\TokenRateResolver;
use App\Settings\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use MoonShine\Enums\ToastType;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;

final class ItcStakingController extends Controller
{
    /**
     * @param \MoonShine\MoonShineRequest $request
     * @return \Illuminate\Http\RedirectResponse
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
     * @param \MoonShine\MoonShineRequest $request
     * @param \App\Settings\GeneralSetting $generalSetting
     * @return \Illuminate\Http\RedirectResponse
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
     * @param \MoonShine\MoonShineRequest $request
     * @param \App\Settings\GeneralSetting $generalSetting
     * @return \Illuminate\Http\RedirectResponse
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
        $tokenRateResolver->upsertRate(
            $request->input('effective_from'),
            (float) $request->input('rate'),
            $request->integer('token_rate_id') ?: null
        );

        return MoonShineJsonResponse::make()
            ->toast('Курс токена сохранен', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    public function deleteTokenRate(int $tokenRateId, TokenRateResolver $tokenRateResolver): MoonShineJsonResponse
    {
        $tokenRateResolver->deleteRate($tokenRateId);

        return MoonShineJsonResponse::make()
            ->toast('Курс токена удален', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * @param \MoonShine\MoonShineRequest $request
     * @param string $uuid
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function editStaking(MoonShineRequest $request, string $uuid): MoonShineJsonResponse
    {
        $request->validate([
            'profit_percent' => ['required', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'manual_profit' => ['nullable', 'numeric', 'min:0'],
            'manual_accrual_type' => ['nullable', Rule::enum(StakingTransactionAccrualEnum::class)],
        ]);

        $transaction = Transaction::query()
            ->whereUuid($uuid)
            ->firstOrFail();

        $package = ItcPackage::query()
            ->with(['transaction'])
            ->whereUuid($uuid)
            ->firstOrFail();

        $oldAmount = $transaction->amount;
        $oldPercent = $package->month_profit_percent;
        $manualProfit = round((float) $request->input('manual_profit', 0), 2);
        $topUpAmount = round((float) $request->input('amount', 0), 2);
        $manualAccrualType = StakingTransactionAccrualEnum::from(
            (string) $request->input('manual_accrual_type', StakingTransactionAccrualEnum::Profit->value)
        );

        $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
        $token = $topUpAmount / $exchangeRateItc;

        $profit = $topUpAmount - $token;

        if ($topUpAmount > 0) {
            $transaction->increment('amount', $token);

            new StakingAccrualService()
                ->accrue($package, StakingTransactionAccrualEnum::TopUpBonus, $profit, $package->transaction->user_id);
        }

        if ($manualProfit > 0) {
            new StakingAccrualService()
                ->accrue($package, $manualAccrualType, $manualProfit, $package->transaction->user_id);
        }

        $package->update([
            'month_profit_percent' => $request->input('profit_percent'),
        ]);

        if ($oldAmount !== $transaction->amount) {

            activity('admin')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'package_uuid' => $transaction->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'amount' => $topUpAmount,
                    'old_amount' => $oldAmount,
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
                    'amount' => $topUpAmount,
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
