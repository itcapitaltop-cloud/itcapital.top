<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Transaction;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Text;
use Throwable;

class TransactionIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Text::make('UUID', 'uuid'),
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
