<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use App\Http\Middleware\AuthorizeDocsAccess;
use Closure;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Components\FormBuilder;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Grid;
use MoonShine\Fields\Switcher;
use MoonShine\Resources\ModelResource;
use MoonShine\Traits\HasResource;
use MoonShine\Traits\WithLabel;

/**
 * @method static static make(Closure|string $label, ModelResource $resource)
 */
final class AdminPermissions extends MoonShineComponent
{
    use HasResource;
    use WithLabel;

    protected string $view = 'moonshine-permissions::components.permissions';

    protected Model $item;

    protected $except = [
        'getItem',
        'getResource',
        'getForm',
    ];

    public function __construct(
        Closure|string $label,
        ModelResource $resource
    ) {
        $this->setResource($resource);
        $this->setLabel($label);
    }

    public function getItem(): Model
    {
        return $this->getResource()->getItemOrInstance();
    }

    public function getForm(): FormBuilder
    {
        $url = $this->getResource()
            ->route('permissions', $this->getItem()->getKey());

        $elements = [];
        $values = [];
        $all = true;

        $resources = moonshine()
            ->getResources()
            ->unique(static fn (ResourceContract $resource): string => $resource::class);

        foreach ($resources as $resource) {
            $elements[] = $this->buildSectionColumn(
                $resource->title(),
                $resource::class,
                $resource->gateAbilities(),
                $values,
                $all
            );
        }

        $elements[] = $this->buildSectionColumn(
            'Документация',
            AuthorizeDocsAccess::PERMISSION_KEY,
            [AuthorizeDocsAccess::ABILITY_VIEW],
            $values,
            $all
        );

        return FormBuilder::make($url)
            ->fields([
                Switcher::make('All')
                    ->customAttributes([
                        '@change' => "document.querySelectorAll('.permission_switcher, .permission_switcher_section').forEach((el) => { el.checked = event.target.checked; el.value = event.target.checked ? '1' : '0'; el.dispatchEvent(new Event('change')); })",
                    ])
                    ->setValue($all),
                Divider::make(),
                Grid::make($elements),
            ])
            ->fill($values)
            ->submit(__('moonshine::ui.save'));
    }

    /**
     * @param list<string> $abilities
     * @param array{permissions?: array<string, array<string, bool>>} $values
     */
    private function buildSectionColumn(
        string $title,
        string $permissionKey,
        array $abilities,
        array &$values,
        bool &$all
    ): Column {
        $checkboxes = [];
        $class = 'ps_' . class_basename($permissionKey);
        $allSections = true;

        foreach ($abilities as $ability) {
            $values['permissions'][$permissionKey][$ability] = $this->getItem()->isHavePermission(
                $permissionKey,
                $ability
            );

            if (! $values['permissions'][$permissionKey][$ability]) {
                $allSections = false;
                $all = false;
            }

            $checkboxes[] = Switcher::make(
                $ability,
                'permissions.' . $permissionKey . ".{$ability}"
            )
                ->customAttributes(['class' => 'permission_switcher ' . $class])
                ->setName("permissions[{$permissionKey}][{$ability}]");
        }

        return Column::make([
            Switcher::make($title)
                ->customAttributes([
                    'class' => 'permission_switcher_section',
                    '@change' => "document.querySelectorAll('.{$class}').forEach((el) => { el.checked = event.target.checked; el.value = event.target.checked ? '1' : '0'; el.dispatchEvent(new Event('change')); })",
                ])
                ->setValue($allSections)
                ->hint('Toggle off/on all'),
            ...$checkboxes,
            Divider::make(),
        ])->columnSpan(6);
    }

    protected function viewData(): array
    {
        return [
            'label' => $this->label(),
            'form' => $this->getItem()?->exists
                ? $this->getForm()
                : '',
            'item' => $this->getItem(),
            'resource' => $this->getResource(),
        ];
    }
}
