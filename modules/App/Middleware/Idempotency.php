<?php

namespace Modules\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Modules\Service;

class Idempotency
{
    /**
     * Itempotency header key
     *
     * @var string
     */
    public $headerKey = 'Idempotency-Key';

    /**
     * Itempotency key time to live
     *
     * @var int
     */
    public $ttl = 7200; // 2h

    /**
     * Idempotency constructor.
     * @param string $headerKey
     * @param int $ttl
     */
    public function __construct($headerKey = null, $ttl = null)
    {
        $this->headerKey = $headerKey ?: config('services.idempotency.header_key', $this->headerKey);
        $this->ttl = $ttl ?: config('services.idempotency.ttl', $this->ttl);
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
        // Không apply idempotency cho GET method
        if ($request->method() === 'GET') {
            return $next($request);
        }

        // Nếu không cần check idempotency
        if (!$requestId = $request->header($this->headerKey)) {
            return $next($request);
        }

        $redis = Redis::connection();
        $key = "idempotency:{$requestId}";

        // Nếu request đã được xử lý
        if ($redis->get($key)) {
            return Service::app()->response()->error('REQUEST_PROCESSED', ['request_id' => $requestId]);
        }

        // Gán trạng thái request đã được xử lý, nếu không thành công thì có nghĩa request đã được xử lý
        if (!$redis->command('set', [$key, true, ['nx', 'ex' => $this->ttl]])) {
            return Service::app()->response()->error('REQUEST_PROCESSED', ['request_id' => $requestId]);
        }

        return $next($request);
    }
}
