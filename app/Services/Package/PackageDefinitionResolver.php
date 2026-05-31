<?php

declare(strict_types=1);

namespace App\Services\Package;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class PackageDefinitionResolver
{
    public function resolve(PackageTypeEnum|string $packageType): PackageDefinition
    {
        $type = $this->normalizePackageType($packageType);

        Log::debug('[PackageDefinitionResolver.resolve] Start', [
            'package_type' => $type->value,
        ]);

        try {
            $definition = PackageDefinition::query()
                ->when($type !== PackageTypeEnum::STAKING, fn ($query) => $query->active())
                ->where('slug', $type->value)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        } catch (QueryException $exception) {
            Log::error('[PackageDefinitionResolver.resolve] Database error', [
                'package_type' => $type->value,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (! $definition instanceof PackageDefinition) {
            Log::warning('[FIX:promo-code-package-definition] Active definition not found by package slug', [
                'package_type' => $type->value,
            ]);

            throw new RuntimeException("Active package definition for [{$type->value}] is not configured.");
        }

        Log::debug('[FIX:promo-code-package-definition] Resolved definition by package slug', [
            'package_type' => $type->value,
            'package_definition_id' => $definition->id,
            'package_definition_slug' => $definition->slug,
            'package_definition_category' => $definition->type->value,
            'default_profit_percent' => $definition->default_profit_percent,
            'min_start_amount' => $definition->min_start_amount,
            'duration_months' => $definition->duration_months,
        ]);

        return $definition;
    }

    public function resolveById(int $packageDefinitionId): PackageDefinition
    {
        Log::debug('[PackageDefinitionResolver.resolveById] Start', [
            'package_definition_id' => $packageDefinitionId,
        ]);

        $definition = PackageDefinition::query()
            ->whereKey($packageDefinitionId)
            ->first();

        if (! $definition instanceof PackageDefinition) {
            Log::warning('[PackageDefinitionResolver.resolveById] Admin-purchasable definition not found', [
                'package_definition_id' => $packageDefinitionId,
            ]);

            throw new RuntimeException("Package definition [{$packageDefinitionId}] is archived or not configured.");
        }

        Log::debug('[PackageDefinitionResolver.resolveById] Resolved definition', [
            'package_definition_id' => $definition->id,
            'package_slug' => $definition->slug,
            'package_type' => $definition->type->value,
            'is_active' => $definition->is_active,
            'default_profit_percent' => $definition->default_profit_percent,
            'min_start_amount' => $definition->min_start_amount,
            'duration_months' => $definition->duration_months,
        ]);

        return $definition;
    }

    public function defaultProfitPercent(PackageTypeEnum|string $packageType): string
    {
        return $this->resolve($packageType)->default_profit_percent;
    }

    public function minStartAmount(PackageTypeEnum|string $packageType): string
    {
        return $this->resolve($packageType)->min_start_amount;
    }

    public function durationMonths(PackageTypeEnum|string $packageType): ?int
    {
        return $this->resolve($packageType)->duration_months;
    }

    private function normalizePackageType(PackageTypeEnum|string $packageType): PackageTypeEnum
    {
        if ($packageType instanceof PackageTypeEnum) {
            return $packageType;
        }

        try {
            return PackageTypeEnum::from($packageType);
        } catch (Throwable $exception) {
            Log::warning('[PackageDefinitionResolver.normalizePackageType] Invalid package type', [
                'package_type' => $packageType,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            throw new RuntimeException("Invalid package type [{$packageType}].", previous: $exception);
        }
    }
}
