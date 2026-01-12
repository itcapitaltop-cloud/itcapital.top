<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (! $locale) {
            $acceptLanguage = $request->server('HTTP_ACCEPT_LANGUAGE', '');
            $browserLang = substr($acceptLanguage, 0, 2);

            $locale = $browserLang;
        }

        if (auth()->check() && ! is_null(auth()->user()->locale)) {

            $locale = auth()->user()->locale;
        }

        $available = ['ru', 'en', 'zh'];

        if (! in_array($locale, $available, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);
        session(compact('locale'));

        return $next($request);
    }
}
