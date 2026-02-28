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
use App\Settings\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
                ->log('admin_package_changed_staking_regular_percent');
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

    /**
     * @param \MoonShine\MoonShineRequest $request
     * @param string $uuid
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function editStaking(MoonShineRequest $request, string $uuid): MoonShineJsonResponse
    {
        $transaction = Transaction::query()
            ->whereUuid($uuid)
            ->firstOrFail();

        $package = ItcPackage::query()
            ->with(['transaction'])
            ->whereUuid($uuid)
            ->firstOrFail();

        $oldAmount = $transaction->amount;
        $oldPercent = $package->month_profit_percent;

        $exchangeRateItc = app(GeneralSetting::class)->exchange_rate_itc * 100;
        $token = $request->input('amount') / $exchangeRateItc;

        $profit = $request->input('amount') - $token;

        $transaction->increment('amount', $token);

        new StakingAccrualService()
            ->accrue($package, StakingTransactionAccrualEnum::TopUpBonus, $profit, $package->transaction->user_id);

        $package->update([
            'month_profit_percent' => $request->input('profit_percent'),
        ]);

        if ($transaction->wasChanged('amount')) {

            activity('admin')
                ->performedOn($package)
                ->causedBy(auth()->user())
                ->withProperties([
                    'package_uuid' => $transaction->uuid,
                    'package_type' => PackageTypeEnum::STAKING,
                    'amount' => $request->input('amount'),
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
                    'amount' => $request->input('amount'),
                    'percent' => $oldPercent,
                    'old_percent' => $package->month_profit_percent,
                ])
                ->log('admin_package_changed_percentage');
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
