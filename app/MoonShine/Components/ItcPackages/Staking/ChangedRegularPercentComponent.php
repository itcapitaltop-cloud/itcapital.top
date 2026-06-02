<?php

declare(strict_types=1);

namespace App\MoonShine\Components\ItcPackages\Staking;

use App\Models\ItcPackage;
use App\Models\User;
use App\Settings\GeneralSetting;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\Modal;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Number;
use MoonShine\Pages\PageComponents;

final class ChangedRegularPercentComponent
{
    /**
     * @param \App\Models\ItcPackage|null $package
     * @return \MoonShine\Components\MoonShineComponent|null
     */
    public function handle(?ItcPackage $package = null): ?MoonShineComponent
    {
        if (is_null($package)) {
            return null;
        }

        if (is_null($package->transaction->user_id)) {
            return Modal::make(
                title: 'Изменение процента регулярной премии',
                content: fn () => 'Что-то пошло не так',
                asyncUrl: null,
            );
        }

        $form = FormBuilder::make()
            ->action(route('admin.itc-staking.change-regular-percentage'))
            ->fields([
                Hidden::make('package_id')->fill($package->id),

                Hidden::make('user_id')->fill($package->transaction->user_id),

                Number::make('Процент регулярной премии', 'percent')
                    ->fill(User::findOrFail($package->transaction->user_id)->setting('regular_staking_percent', app(GeneralSetting::class)->regular_staking_percent))
                    ->customAttributes(
                        [
                            'wire:model.defer' => 'percent',
                            'step' => 'any',
                        ])
                    ->required(),
            ])
            ->submit('Изменить');

        $components = PageComponents::make([$form]);

        $action = ActionButton::make('Стейкинг - регулярная премия')
            ->icon('heroicons.receipt-percent')
            ->toggleModal('changed-regular-percent-modal')
            ->primary();

        return Modal::make(
            title: 'Изменение процента регулярной премии',
            content: fn () => null,
            outer: $action,
            asyncUrl: null,
            components: $components
        )->name('changed-regular-percent-modal');
    }
}
