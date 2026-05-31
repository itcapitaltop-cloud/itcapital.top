<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionDetailPage;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionFormPage;
use App\MoonShine\Pages\PackageDefinition\PackageDefinitionIndexPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use MoonShine\Fields\Select;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\Pages\Page;
use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<PackageDefinition>
 */
class PackageDefinitionResource extends ModelResource
{
    private const EVENT_CREATED = 'Создание тарифа';

    private const EVENT_UPDATED = 'Изменение тарифа';

    private const EVENT_ARCHIVED = 'Архивация тарифа';

    private const EVENT_RESTORED = 'Восстановление тарифа';

    private const AUDIT_LOG_NAME = 'package_definitions';

    /**
     * @var array<string, string>
     */
    private const AUDIT_FIELD_LABELS = [
        'slug' => 'Slug',
        'category' => 'Категория',
        'name' => 'Название',
        'default_profit_percent' => 'Процент прибыли по умолчанию',
        'min_start_amount' => 'Минимальная сумма',
        'duration_months' => 'Срок работы, месяцев',
        'card_image_path' => 'Изображение карточки',
        'is_active' => 'Доступен в ЛК',
        'sort_order' => 'Порядок сортировки',
        'status' => 'Статус',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $oldValues = [];

    protected string $model = PackageDefinition::class;

    protected string $title = 'Настройки пакетов';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            PackageDefinitionIndexPage::make($this->title()),
            PackageDefinitionFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            PackageDefinitionDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    public function query(): Builder
    {
        return parent::query()
            ->withTrashed()
            ->orderByRaw('deleted_at IS NOT NULL')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function resolveItemQuery(): Builder
    {
        return parent::resolveItemQuery()->withTrashed();
    }

    public function restorePackageDefinition(): MoonShineJsonResponse
    {
        $packageDefinition = PackageDefinition::query()
            ->withTrashed()
            ->findOrFail($this->getItemID());

        Log::info('[PackageDefinitionResource.restorePackageDefinition] Restore package definition requested', [
            'admin_id' => auth()->id(),
            'package_definition_id' => $packageDefinition->id,
            'package_slug' => $packageDefinition->slug,
            'package_type' => $packageDefinition->type->value,
            'was_trashed' => $packageDefinition->trashed(),
            'was_active' => $packageDefinition->is_active,
        ]);

        $packageDefinition->restore();
        $packageDefinition->forceFill(['is_active' => true])->save();

        $this->writeAuditLog(
            packageDefinition: $packageDefinition,
            event: self::EVENT_RESTORED,
            oldValues: ['status' => 'Архив'],
            newValues: ['status' => 'Доступен в ЛК'],
        );

        return MoonShineJsonResponse::make()
            ->toast('Тариф восстановлен и доступен для новых покупок.')
            ->redirect(to_page(new PackageDefinitionIndexPage(), new self()));
    }

    /**
     * @return list<Select>
     */
    public function filters(): array
    {
        return [
            Select::make('Статус', 'package_status')
                ->options([
                    'active' => 'Доступен в ЛК',
                    'inactive' => 'Только в админке',
                    'archived' => 'Архив',
                ])
                ->nullable()
                ->onApply(function (Builder $query, ?string $value): void {
                    match ($value) {
                        'active' => $query->whereNull('deleted_at')->where('is_active', true),
                        'inactive' => $query->whereNull('deleted_at')->where('is_active', false),
                        'archived' => $query->whereNotNull('deleted_at'),
                        default => null,
                    };
                }),
            Select::make('Системная категория', 'type')
                ->options($this->packageTypeOptions())
                ->nullable(),
        ];
    }

    public function search(): array
    {
        return ['name', 'slug', 'type'];
    }

    protected function beforeUpdating(Model $item): Model
    {
        if ($item instanceof PackageDefinition) {
            $this->oldValues = $this->auditableValues($item);
        }

        return $item;
    }

    protected function beforeDeleting(Model $item): Model
    {
        if ($item instanceof PackageDefinition) {
            $this->oldValues = ['status' => $item->trashed() ? 'Архив' : ($item->is_active ? 'Доступен в ЛК' : 'Только в админке')];
        }

        return $item;
    }

    protected function afterCreated(Model $item): Model
    {
        if ($item instanceof PackageDefinition) {
            $this->writeAuditLog($item, self::EVENT_CREATED, [], $this->auditableValues($item));
        }

        return $item;
    }

    protected function afterUpdated(Model $item): Model
    {
        if ($item instanceof PackageDefinition) {
            $newValues = $this->auditableValues($item);

            $this->writeAuditLog(
                packageDefinition: $item,
                event: self::EVENT_UPDATED,
                oldValues: array_intersect_key($this->oldValues, $item->getChanges()),
                newValues: array_intersect_key($newValues, $item->getChanges()),
            );
        }

        return $item;
    }

    protected function afterDeleted(Model $item): Model
    {
        if ($item instanceof PackageDefinition) {
            $this->writeAuditLog(
                packageDefinition: $item,
                event: self::EVENT_ARCHIVED,
                oldValues: $this->oldValues,
                newValues: ['status' => 'Архив'],
            );
        }

        return $item;
    }

    /**
     * @param PackageDefinition $item
     * @return array<string, mixed>
     */
    public function rules(Model $item): array
    {
        return [
            'slug' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('package_definitions', 'slug')->ignore($item->id),
            ],
            'type' => [
                'required',
                Rule::enum(PackageTypeEnum::class),
                Rule::notIn([PackageTypeEnum::ARCHIVE->value]),
            ],
            'name' => ['required', 'string', 'max:100'],
            'default_profit_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'min_start_amount' => ['required', 'numeric', 'min:0'],
            'duration_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'card_image_path' => ['nullable', 'image', 'max:3072', Rule::dimensions()->maxWidth(3000)->maxHeight(3000)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function packageTypeOptions(): array
    {
        return collect(PackageTypeEnum::cases())
            ->reject(fn (PackageTypeEnum $packageType): bool => $packageType === PackageTypeEnum::ARCHIVE)
            ->mapWithKeys(fn (PackageTypeEnum $packageType): array => [$packageType->value => $packageType->getName()])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditableValues(PackageDefinition $packageDefinition): array
    {
        return [
            'slug' => $packageDefinition->slug,
            'category' => $packageDefinition->type->getName(),
            'name' => $packageDefinition->name,
            'default_profit_percent' => $packageDefinition->default_profit_percent,
            'min_start_amount' => $packageDefinition->min_start_amount,
            'duration_months' => $packageDefinition->duration_months,
            'card_image_path' => $packageDefinition->card_image_path,
            'is_active' => $packageDefinition->is_active ? 'Да' : 'Нет',
            'sort_order' => $packageDefinition->sort_order,
        ];
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    private function writeAuditLog(
        PackageDefinition $packageDefinition,
        string $event,
        array $oldValues,
        array $newValues,
    ): void {
        if ($oldValues === [] && $newValues === []) {
            return;
        }

        $admin = auth(config('moonshine.auth.guard', 'moonshine'))->user();
        $admin = $admin instanceof MoonshineUser ? $admin : null;

        foreach ($this->auditLogRows($oldValues, $newValues) as $field => $values) {
            $fieldLabel = self::AUDIT_FIELD_LABELS[$field] ?? (string) $field;

            activity(self::AUDIT_LOG_NAME)
                ->performedOn($packageDefinition)
                ->causedBy($admin)
                ->event('admin')
                ->withProperties([
                    'old_values' => $this->labelAuditValues($values['old']),
                    'new_values' => $this->labelAuditValues($values['new']),
                    'package_definition_id' => $packageDefinition->id,
                    'package_slug' => $packageDefinition->slug,
                    'admin_id' => $admin?->id,
                    'admin_login' => $admin?->email,
                ])
                ->log($event . ': ' . $fieldLabel);
        }
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @return array<string, array{old: array<string, mixed>, new: array<string, mixed>}>
     */
    private function auditLogRows(array $oldValues, array $newValues): array
    {
        $fields = array_unique([...array_keys($oldValues), ...array_keys($newValues)]);
        $rows = [];

        foreach ($fields as $field) {
            $rows[(string) $field] = [
                'old' => array_key_exists($field, $oldValues) ? [(string) $field => $oldValues[$field]] : [],
                'new' => array_key_exists($field, $newValues) ? [(string) $field => $newValues[$field]] : [],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function labelAuditValues(array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn (mixed $value, string $field): array => [self::AUDIT_FIELD_LABELS[$field] ?? $field => $value])
            ->all();
    }
}
