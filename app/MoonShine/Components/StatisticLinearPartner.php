<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use App\Actions\User\Partner\ProgressBarAction;
use App\Models\PartnerRank;
use App\Models\User;
use MoonShine\Components\MoonShineComponent;

/**
 * @method static static make()
 */
final class StatisticLinearPartner extends MoonShineComponent
{
    protected string $view = 'admin.components.statistic-linear-partner';

    public function __construct(public int $userId)
    {
        //
    }

    /*
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $user = User::query()->findOrFail($this->userId);
        $maxRank = (int) PartnerRank::query()->max('rank');

        return [
            'nextRank' => $user->rank + 1 <= $maxRank ? $user->rank + 1 : null,
            'progressBars' => ProgressBarAction::make()->run($this->userId),
        ];
    }
}
