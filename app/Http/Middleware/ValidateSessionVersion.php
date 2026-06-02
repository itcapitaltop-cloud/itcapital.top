<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateSessionVersion
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('itcapitalmoonshineadminpanel*')) {
            return $next($request);
        }

        if (auth()->check()) {
            $user = auth()->user();
            $sessionVersion = session('session_version');

            if ($sessionVersion === null) {
                session(['session_version' => $user->session_version]);
            } elseif ($sessionVersion !== $user->session_version) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Your session has been invalidated by an administrator.');
            }
        }

        return $next($request);
    }
}
