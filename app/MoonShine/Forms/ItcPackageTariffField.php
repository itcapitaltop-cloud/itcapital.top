<?php

declare(strict_types=1);

namespace App\MoonShine\Forms;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\Package\PackageDefinition;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Field;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Select;

/**
 * Builds the tariff input of the admin package edit form for a single package.
 */
final class ItcPackageTariffField
{
    private const LOCKED_TYPE = PackageTypeEnum::STAKING;

    private const KEEP_ARCHIVED_VALUE = '';

    public const ARCHIVE_VALUE = 'archive';

    public static function make(ItcPackage $package): Field
    {
        if ($package->type === self::LOCKED_TYPE) {
            return self::lockedTariffField($package);
        }

        if ($package->package_definition_id !== null) {
            return self::definitionTariffField($package);
        }

        return self::legacyTypeField($package);
    }

    private static function lockedTariffField(ItcPackage $package): Field
    {
        $label = $package->packageDefinition?->name ?? $package->type->getName();

        return Preview::make('Тариф', 'package_tariff_locked', formatted: fn (): string => $label);
    }

    private static function definitionTariffField(ItcPackage $package): Field
    {
        $isArchived = $package->type === PackageTypeEnum::ARCHIVE;
        $options = self::definitionOptions($package);

        if ($isArchived) {
            $options = [self::KEEP_ARCHIVED_VALUE => 'Оставить в архиве'] + $options;
        } else {
            $options[self::ARCHIVE_VALUE] = 'Архивный (скрыть у пользователя, тело не возвращается)';
        }

        return Select::make('Тариф', 'package_definition_id')
            ->options($options)
            ->fill($isArchived ? self::KEEP_ARCHIVED_VALUE : $package->package_definition_id)
            ->when(! $isArchived, fn (Select $field): Select => $field->required());
    }

    private static function legacyTypeField(ItcPackage $package): Field
    {
        return Enum::make('Тип пакета', 'type')
            ->attach(PackageTypeEnum::class)
            ->fill($package->type);
    }

    /**
     * Mirrors the option labels of the package create form. Inactive tariffs stay
     * selectable and are marked, so admin-only definitions remain assignable.
     *
     * @return array<int, string>
     */
    private static function definitionOptions(ItcPackage $package): array
    {
        $definitions = PackageDefinition::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $current = $package->packageDefinition;

        if ($current instanceof PackageDefinition && ! $definitions->contains('id', $current->id)) {
            $definitions->prepend($current);
        }

        return $definitions
            ->mapWithKeys(fn (PackageDefinition $definition): array => [
                $definition->id => $definition->is_active
                    ? "{$definition->name} ({$definition->slug})"
                    : "{$definition->name} ({$definition->slug}) — только в админке",
            ])
            ->all();
    }
}
