<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\ItcStaking;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Field;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class ItcStakingIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    protected function multiSortCallback(): Closure
    {
        return function (Builder $q, string $col, string $dir) {
            // Берём именно наш параметр

            $sorts = json_decode(request('multi_sort', '{}'), true) ?: [];

            // Сбрасываем, если без Ctrl
            if (! request()->boolean('multi')) {
                $sorts = [];
            }

            // Обновляем карту
            $sorts[$col] = $dir;

            // Применяем все orderBy по порядку ключей
            foreach ($sorts as $c => $d) {
                $q->orderBy($c, $d);
            }
        };
    }

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        $multi = $this->multiSortCallback();

        return [
            Text::make('ID', 'id')->sortable(),
            Text::make('ФИО', 'first_name', formatted: static fn (User $user) => $user->first_name . ' ' . $user->last_name)
                ->showOnExport()
                ->sortable($multi),
            Text::make('Имя пользователя', 'username')->sortable($multi),
            Text::make('Email', 'email')->sortable($multi),
            Number::make('Пакеты', 'buy_packages_sum', formatted: function (User $user) {
                $sum = ItcPackage::query()
                    ->whereHas('transaction', fn ($q) => $q->where('user_id', $user->id))
                    ->whereIn('type', [PackageTypeEnum::STAKING])
                    ->withSum('transaction', 'amount')
                    ->get()
                    ->sum('transaction_sum_amount');

                return round((float) $sum, 2);
            })->showOnExport(),

        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
