<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\PromoCode;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;

class PromoCodeIndexPage extends IndexPage
{
    protected string $elementAlias = 'promo-code-index-page';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Text::make('Промокод', 'code')->showOnExport(),
            Text::make('Пакет', formatted: fn (PromoCode $item) => $item->packageDefinition?->name ?? '-')->showOnExport(),
            Number::make('Порог покупки', 'reduced_minimum_amount', formatted: fn ($item) => round((float) $item->reduced_minimum_amount, 2))->showOnExport(),
            Text::make('Использован', formatted: fn ($item) => $item->usages()->exists() ? 'Да' : 'Нет')->showOnExport(),
            Number::make('Кол-во использований', formatted: fn ($item) => (string) $item->usages()->count())->showOnExport(),
            Date::make('Дата создания', 'created_at')->format('d.m.Y H:i:s')->showOnExport(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     */
    public function components(): array
    {
        $activeTab = request('tab', 'list');

        $deferredTab = static function (string $tab, string $label): FlexibleRender {
            $url = e(request()->fullUrlWithQuery(['tab' => $tab]));
            $id = 'deferred-tab-' . $tab;

            return FlexibleRender::make(
                fn () => <<<HTML
                    <div id="{$id}" class="p-4" data-deferred-tab-url="{$url}">
                        <span class="opacity-70">Загрузка...</span>
                        <noscript><a class="btn btn-primary" href="{$url}">{$label}</a></noscript>
                        <script>
                            (() => {
                                const el = document.getElementById('{$id}');
                                if (!el || el.dataset.deferredTabBound === '1') return;
                                el.dataset.deferredTabBound = '1';

                                const redirectWhenVisible = () => {
                                    const visible = el.offsetParent !== null;
                                    if (visible && window.location.href !== el.dataset.deferredTabUrl) {
                                        window.location.href = el.dataset.deferredTabUrl;
                                    }
                                };

                                redirectWhenVisible();

                                const observer = new IntersectionObserver((entries) => {
                                    if (entries.some((entry) => entry.isIntersecting)) {
                                        redirectWhenVisible();
                                    }
                                });

                                observer.observe(el);
                            })();
                        </script>
                    </div>
                HTML
            );
        };

        $logsData = PromoCodeUsage::with(['user', 'promoCode', 'packageDefinition'])
            ->orderByDesc('used_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PromoCodeUsage $usage) => [
                'promo_code' => $usage->promoCode?->code ?? '-',
                'user' => $usage->user?->username ?? '-',
                'package_definition' => $usage->packageDefinition?->name ?? '-',
                'used_at' => $usage->used_at?->format('d.m.Y H:i:s') ?? '-',
            ])
            ->toArray();

        $listData = PromoCode::with(['usages', 'createdByAdmin', 'packageDefinition'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PromoCode $item) => [
                'code' => $item->code,
                'package_definition' => $item->packageDefinition?->name ?? '-',
                'reduced_minimum_amount' => round((float) $item->reduced_minimum_amount, 2),
                'is_used' => $item->usages()->exists() ? 'Да' : 'Нет',
                'usages_count' => (string) $item->usages()->count(),
                'created_at' => $item->created_at?->format('d.m.Y H:i:s') ?? '-',
            ])
            ->toArray();

        $generateButton = ActionButton::make('Сгенерировать промокод')
            ->inModal(
                title: fn () => 'Генерация промокода',
                content: fn () => Block::make([
                    FormBuilder::make()
                        ->action(route('admin.promo-codes.generate'))
                        ->fields([
                            Select::make('Пакет', 'package_definition_id')
                                ->options(
                                    PackageDefinition::query()
                                        ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING, PackageTypeEnum::PRESENT])
                                        ->orderBy('sort_order')
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (PackageDefinition $definition) => [$definition->id => $definition->name . ' (' . $definition->slug . ')'])
                                        ->all()
                                )
                                ->required(),
                            Number::make('Снизить порог до суммы', 'reduced_minimum_amount')
                                ->customAttributes([
                                    'min' => 0,
                                    'step' => 'any',
                                ])
                                ->required(),
                        ])
                        ->method('POST')
                        ->async()
                        ->submit('Сгенерировать'),
                ])
            )
            ->success();

        return [
            $generateButton,
            Tabs::make([
                Tab::make('Список')
                    ->active(fn () => $activeTab === 'list')
                    ->fields([
                        ...($activeTab !== 'list' ? [$deferredTab('list', 'Загрузить список')] : []),
                        TableBuilder::make()
                            ->withNotFound()
                            ->fields([
                                Text::make('Промокод', 'code')->showOnExport(),
                                Text::make('Пакет', 'package_definition')->showOnExport(),
                                Number::make('Порог покупки', 'reduced_minimum_amount')->showOnExport(),
                                Text::make('Использован', 'is_used')->showOnExport(),
                                Number::make('Кол-во использований', 'usages_count')->showOnExport(),
                                Text::make('Дата создания', 'created_at')->showOnExport(),
                            ])
                            ->items($listData),
                    ]),
                Tab::make(
                    'Журнал'
                )
                    ->active(fn () => $activeTab === 'logs')
                    ->fields([
                        ...($activeTab !== 'logs' ? [$deferredTab('logs', 'Загрузить журнал')] : []),
                        TableBuilder::make()
                            ->withNotFound()
                            ->fields([
                                Text::make('Промокод', 'promo_code')->showOnExport(),
                                Text::make('Пользователь', 'user')->showOnExport(),
                                Text::make('Пакет', 'package_definition')->showOnExport(),
                                Text::make('Дата использования', 'used_at')->showOnExport(),
                            ])
                            ->items($logsData),
                    ]),
            ]),
        ];
    }
}
