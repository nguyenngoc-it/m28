<?php

namespace Modules\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Language
{
    /**
     * Perform handle
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($locale = $request->get('locale')) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
