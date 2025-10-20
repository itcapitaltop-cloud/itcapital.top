<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\Badge;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Enums\ToastType;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<User>
 */
class VerifyingUserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Верификация пользователей';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                Text::make('Email', 'email')->showOnExport()->sortable(),
                Text::make('ФИО', 'first_name', formatted: static fn (User $user) => $user->first_name . ' ' . $user->last_name)
                    ->showOnExport()
                    ->sortable(),
                Text::make('Имя пользователя', 'username')->showOnExport()->sortable(),
                Date::make('Дата регистрации', 'created_at')->showOnExport()->sortable(),
                Td::make('Статус', static function (User $user): array {
                    $badge = Badge::make('Ожидает верификации', 'green');

                    if (is_null($user->email_verification_sent_at)) {
                        $badge = Badge::make('Отклонено', 'error');
                    }

                    return [
                        $badge,
                    ];
                })->showOnExport(),
                Td::make('Действие', function (User $user): array {
                    return $this->managerActions($user);
                }),
            ]),
        ];
    }

    /**
     * @return string[]
     */
    public function search(): array
    {
        return ['email', 'first_name', 'last_name', 'username', 'created_at'];
    }

    /**
     * @return array|\MoonShine\ActionButtons\ActionButton[]
     *
     * @throws \Throwable
     */
    public function actions(): array
    {
        return [
            ActionButton::make('Отклонить все заявки')
                ->method(
                    'overrideEmailAll',
                )
                ->icon('heroicons.x-mark')
                ->error(),
        ];
    }

    /**
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function query(): Builder
    {
        return parent::query()->withoutGlobalScope('notBanned')
            ->whereNull('email_verified_at')
            ->whereNull('banned_at');
    }

    /**
     * @return string[]
     */
    public function getActiveActions(): array
    {
        return ['view'];
    }

    /**
     * @param User $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    /**
     * We confirm the email of a specific user.
     *
     * @param \MoonShine\MoonShineRequest $request
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function confirmEmail(MoonShineRequest $request): MoonShineJsonResponse
    {
        $userId = $request->get('resourceItem');

        $user = User::query()->withoutGlobalScope('notBanned')->findOrFail($userId);

        if (is_null($user)) {
            return MoonShineJsonResponse::make()
                ->toast('Пользователь не найден', ToastType::ERROR);
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        return MoonShineJsonResponse::make()
            ->toast('Email успешно подтвержден', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * We reject email confirmation for all users.
     *
     * @param \MoonShine\MoonShineRequest $request
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function overrideEmail(MoonShineRequest $request): MoonShineJsonResponse
    {
        $userId = $request->get('resourceItem');

        $user = User::query()->withoutGlobalScope('notBanned')->findOrFail($userId);

        if (is_null($user)) {
            return MoonShineJsonResponse::make()
                ->toast('Пользователь не найден', ToastType::ERROR);
        }

        $user->update([
            'email_verified_at' => null,
            'email_verification_sent_at' => null,
        ]);

        return MoonShineJsonResponse::make()
            ->toast('Верификация пользователю отменена', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * We reject email confirmation for all users.
     *
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function overrideEmailAll(): MoonShineJsonResponse
    {
        User::query()->withoutGlobalScope('notBanned')
            ->whereNull('email_verified_at')
            ->whereNull('banned_at')
            ->update([
                'email_verified_at' => null,
                'email_verification_sent_at' => null,
            ]);

        return MoonShineJsonResponse::make()
            ->toast('Верификация для пользователей отменена', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * @param \MoonShine\MoonShineRequest $request
     * @return \MoonShine\Http\Responses\MoonShineJsonResponse
     */
    public function cancelEmail(MoonShineRequest $request): MoonShineJsonResponse
    {
        $userId = $request->get('resourceItem');

        $user = User::query()->withoutGlobalScope('notBanned')->findOrFail($userId);

        if (is_null($user)) {
            return MoonShineJsonResponse::make()
                ->toast('Пользователь не найден', ToastType::ERROR);
        }

        $user->update([
            'email_verification_sent_at' => now(),
        ]);

        return MoonShineJsonResponse::make()
            ->toast('Статус верификации сброшен', ToastType::SUCCESS)
            ->redirect(request()->headers->get('referer'));
    }

    /**
     * @param \App\Models\User $user
     * @return array
     *
     * @throws \Throwable
     */
    public function managerActions(User $user): array
    {
        if (! is_null($user->email_verification_sent_at)) {
            return [
                ActionButton::make('Принять')
                    ->method(
                        'confirmEmail',
                        params: fn () => ['resourceItem' => $user->id]
                    )
                    ->icon('heroicons.check')
                    ->success(),

                ActionButton::make('Отклонить')
                    ->method(
                        'overrideEmail',
                        params: fn () => ['resourceItem' => $user->id]
                    )
                    ->icon('heroicons.x-mark')
                    ->error(),
            ];
        }

        return [
            ActionButton::make('Отменить')
                ->method(
                    'cancelEmail',
                    params: fn () => ['resourceItem' => $user->id]
                )
                ->icon('heroicons.arrow-uturn-left')
                ->info(),
        ];
    }
}
