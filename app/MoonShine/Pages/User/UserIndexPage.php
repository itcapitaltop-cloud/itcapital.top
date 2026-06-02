<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\User;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Components\Alert;
use MoonShine\Components\Link;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Date;
use MoonShine\Fields\Email;
use MoonShine\Fields\Field;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class UserIndexPage extends IndexPage
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

            //            Log::channel('source')->debug($sorts);
            // Применяем все orderBy по порядку ключей
            foreach ($sorts as $c => $d) {
                $q->orderBy($c, $d);
            }
        };
    }

    public function fields(): array
    {
        $multi = $this->multiSortCallback();

        return [
            Email::make('Email')->showOnExport()->sortable($multi),
            Text::make('ФИО', 'first_name', formatted: fn (User $user) => $user->first_name . ' ' . $user->last_name)
                ->showOnExport()
                ->sortable($multi),
            Text::make('Имя пользователя', 'username')->showOnExport()->sortable($multi),
            Number::make('Пакеты', 'buy_packages_sum', formatted: fn (User $user) => $user->summary->buy_packages_sum)
                ->showOnExport()
                ->sortable($multi),
            Number::make('Реинвесты', 'reinvests_sum', formatted: fn (User $user) => $user->summary->reinvests_sum)
                ->showOnExport()
                ->sortable($multi),
            Number::make('Основной', formatted: fn (User $user) => $user->summary->investments_sum)
                ->showOnExport()
                ->sortable($multi),
            Number::make('Партнерский', 'partner_balance', formatted: fn (User $user) => $user->summary->partner_balance)
                ->showOnExport()
                ->sortable($multi),
            Date::make('Дата регистрации', 'created_at')->showOnExport()->sortable($multi),
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
        // получаем paginator от ресурса
        $paginator = $this->getResource()->paginate();

        // если ничего не нашлось — показываем сообщение и кнопку «Сбросить фильтр»
        if ($paginator->isEmpty()) {
            return [
                Alert::make(type: 'info')
                    ->content('Поиск с текущими настройками фильтра не дал результатов'),
                Link::make(request()->url() . '?reset=1', 'Сбросить фильтр')
                    ->customAttributes(['class' => 'btn btn-filter btn-secondary']),
            ];
        }

        // иначе — дефолтный вывод таблицы
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

    protected function actions(): array
    {
        return [];
    }
}
