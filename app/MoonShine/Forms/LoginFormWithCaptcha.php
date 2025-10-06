<?php

declare(strict_types=1);

namespace App\MoonShine\Forms;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Switcher;   // «чистый» HTML/Blade компонент :contentReference[oaicite:2]{index=2}
use MoonShine\UI\Fields\Text;

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
                        'autofocus' => true,
                        'autocomplete' => 'username',
                    ]),

                Password::make(__('moonshine::ui.login.password'), 'password')
                    ->required(),

                // ===== reCAPTCHA =====
                FlexibleRender::make(
                    fn () => '<div style="margin-bottom:20px">' .
                        NoCaptcha::display() .
                        '</div>' .
                        NoCaptcha::renderJs('', false, 'recaptchaLoaded')
                ),

                Switcher::make(__('moonshine::ui.login.remember_me'), 'remember'),
            ])
            ->submit(__('moonshine::ui.login.login'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
