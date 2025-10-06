<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\MoonShineComponent;

class Dashboard extends Page
{
    protected string $title = 'Dashboard';

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     */
    public function components(): array
    {
        return [];
    }
}
