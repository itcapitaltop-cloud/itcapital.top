<?php

declare(strict_types=1);

namespace App\MoonShine\AuthPipelines;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        $rules = ['g-recaptcha-response' => 'required|captcha'];

        $messages = [                         // <‑‑‑ добавили
            'g-recaptcha-response.required' => 'Подтвердите, что вы не робот',
            'g-recaptcha-response.captcha' => 'Капча пройдена неверно',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if (! $validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        return $next($request);
    }
}
