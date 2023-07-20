<?php

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Service;

class Authorize
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param string $abilities
     * @param string|null $object
     * @return mixed
     */
    public function handle($request, $next, $abilities, $object = null)
    {
        $abilities = explode('|', $abilities);
        $arguments = $object ? [$request->route($object)] : [];

        return !$this->performAuthorize($abilities, $arguments)
            ? $this->responseUnauthorized()
            : $next($request);
    }

    /**
     * @param array $abilities
     * @param array $arguments
     * @return bool
     */
    protected function performAuthorize(array $abilities, array $arguments)
    {
        return Gate::check($abilities, $arguments);
    }

    /**
     * @return mixed
     */
    protected function responseUnauthorized()
    {
        return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
    }
}
