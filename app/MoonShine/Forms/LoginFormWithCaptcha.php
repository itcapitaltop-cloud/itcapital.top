<?php

declare(strict_types=1);

namespace App\MoonShine\Forms;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use MoonShine\Components\FlexibleRender;   // «чистый» HTML/Blade компонент :contentReference[oaicite:2]{index=2}
use MoonShine\Components\FormBuilder;
use MoonShine\Fields\Password;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;

class LoginFormWithCaptcha
{
    public function __invoke(): FormBuilder
    {
        return FormBuilder::make()
            ->customAttributes(['class' => 'authentication-form'])
            ->action(moonshineRouter()->to('authenticate'))
            ->fields([
                Text::make(__('moonshine::ui.login.username'), 'username')
                    ->required()
                    ->customAttributes([
                        'autofocus'     => true,
                        'autocomplete'  => 'username',
                    ]),

                Password::make(__('moonshine::ui.login.password'), 'password')
                    ->required(),

                ...$this->recaptchaFields(),

                Switcher::make(__('moonshine::ui.login.remember_me'), 'remember'),
            ])
            ->submit(__('moonshine::ui.login.login'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }

    private function recaptchaFields(): array
    {
        if (app()->environment(['local', 'dev'])) {
            return [];
        }

        return [
            FlexibleRender::make(
                fn () =>
                    '<div style="margin-bottom:20px">' .
                    NoCaptcha::display() .
                    '</div>' .
                    NoCaptcha::renderJs('', false, 'recaptchaLoaded')
            ),
        ];
    }
}
