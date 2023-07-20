<?php

namespace Modules\App\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustProxies
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
        if ($proxies = config('trustedproxy.proxies')) {
            $request::setTrustedProxies(explode(',', $proxies), $this->getTrustedHeader());
        }

        return $next($request);
    }

    /**
     * @return int
     */
    protected function getTrustedHeader()
    {
        return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT;
    }
}
