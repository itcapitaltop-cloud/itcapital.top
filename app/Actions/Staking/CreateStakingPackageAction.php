<?php

declare(strict_types=1);

namespace App\Actions\Staking;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Services\Package\PackageDefinitionResolver;
use App\Tasks\Transaction\CreateTransactionTask;
use Illuminate\Support\Facades\Log;
use LeMaX10\SimpleActions\Action;

final class CreateStakingPackageAction extends Action
{
    protected function handle(int $userId, float $amount, ?float $monthProfitPercent = null, ?int $packageDefinitionId = null): ItcPackage
    {
        $packageDefinitionResolver = app(PackageDefinitionResolver::class);
        $packageDefinition = $packageDefinitionId === null
            ? $packageDefinitionResolver->resolve(PackageTypeEnum::STAKING)
            : $packageDefinitionResolver->resolveById($packageDefinitionId);

        Log::debug('[CreateStakingPackageAction.handle] resolved package definition', [
            'user_id' => $userId,
            'package_definition_id' => $packageDefinition->id,
            'amount' => $amount,
            'definition_profit_percent' => $packageDefinition->default_profit_percent,
            'requested_profit_percent' => $monthProfitPercent,
        ]);

        $transaction = new CreateTransactionTask()->acceptedAt(now())->run($amount, $userId);

        $package = ItcPackage::query()
            ->create([
                'uuid' => $transaction->uuid,
                'package_definition_id' => $packageDefinition->id,
                'work_to' => now()->addYears(3), // Пакет бессрочный, но предыдущий разраб дебил сделал поле обязательным :)
                'duration_months' => $packageDefinition->duration_months,
                'month_profit_percent' => $monthProfitPercent === null
                    ? $packageDefinition->default_profit_percent
                    : (string) $monthProfitPercent,
                'type' => PackageTypeEnum::STAKING,
            ]);

        Log::info('[CreateStakingPackageAction.handle] staking package created', [
            'user_id' => $userId,
            'package_uuid' => $package->uuid,
            'package_definition_id' => $packageDefinition->id,
            'month_profit_percent' => $package->month_profit_percent,
        ]);

        return $package;
    }
}
