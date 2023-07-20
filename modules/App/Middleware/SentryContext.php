<?php

namespace Modules\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentry\State\Scope;

class SentryContext
{
    /**
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('SENTRY_ENABLE') && app()->bound('sentry')) {
            if ($user = Auth::user()) {
                \Sentry\configureScope(function (Scope $scope) use ($user) {
                    $scope->setUser([
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                    ]);
                });
            }
        }

        return $next($request);
    }
}
