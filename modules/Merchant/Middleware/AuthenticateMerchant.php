<?php

namespace Modules\Merchant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Service;
use Modules\User\Models\User;

class AuthenticateMerchant
{
    /**
     * @param Request$request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return Service::app()->response()->error(401, ['message' => 'Unauthenticated'], 401);
        }

        if (!$user->merchant) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
