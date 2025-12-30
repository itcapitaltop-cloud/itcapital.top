<?php

use App\Http\Middleware\SetLocale;
use     Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
//        $middleware->redirectUsersTo(fn(Request $request) => route('index'));
    })
    ->withMiddleware(function(Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->trustProxies(
        // доверять всем прокси (ngrok может иметь динамический IP)
            at: '*',
            // доверять именно этим заголовкам
            headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
    )
    ->create();
