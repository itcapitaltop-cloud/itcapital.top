<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MoonShine\Permissions\Models\MoonshineUser;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeDocsAccess
{
    public const PERMISSION_KEY = 'Documentation';

    public const ABILITY_VIEW = 'view';

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth(config('moonshine.auth.guard', 'moonshine'))->user();

        abort_unless(self::allowsFor($user), Response::HTTP_FORBIDDEN);

        return $next($request);
    }

    public static function allowsFor(?Authenticatable $user): bool
    {
        if (! $user instanceof MoonshineUser) {
            return false;
        }

        if ($user->moonshineUserPermission !== null) {
            return $user->isHavePermission(self::PERMISSION_KEY, self::ABILITY_VIEW);
        }

        return $user->isSuperUser();
    }
}
