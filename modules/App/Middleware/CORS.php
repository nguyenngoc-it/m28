<?php

namespace Modules\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware handle Cross-Origin Resource Sharing (CORS)
 */
class CORS
{
    /**
     * The Access-Control-Allow-Origin
     *
     * @var string
     */
    public $allowOrigin = '*';

    /**
     * The Access-Control-Allow-Methods
     *
     * @var string
     */
    public $allowMethods = 'GET, POST, PATCH, PUT, DELETE, OPTIONS';

    /**
     * HandleWebApiRequest constructor
     *
     * @param string $allowOrigin
     */
    public function __construct($allowOrigin = null)
    {
        $this->allowOrigin = $allowOrigin ?: $this->allowOrigin;
    }

    /**
     * Perform handle
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        return $this->setHeaderAccessControl($response);
    }

    /**
     * Set header Access-Control
     *
     * @param Response $response
     * @return Response
     */
    protected function setHeaderAccessControl($response)
    {
        $response->headers->set('Access-Control-Allow-Origin', $this->allowOrigin, true);
        $response->headers->set('Access-Control-Allow-Methods', $this->allowMethods, true);
        $response->headers->set('Access-Control-Allow-Headers', 'access-control-allow-headers, authorization, Origin, Content-Type, Idempotency-Key', true);
        $response->headers->set('Access-Control-Allow-Credentials', 'true', true);

        return $response;
    }
}
