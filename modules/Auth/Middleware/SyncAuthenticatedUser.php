<?php

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Service;
use Modules\User\Models\User;

class SyncAuthenticatedUser
{
    /**
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($user = Auth::user()) {
            Service::oauth()->syncUserIfExpired($user);
            Auth::setUser(User::find($user->id));
        }

        return $next($request);
    }
}