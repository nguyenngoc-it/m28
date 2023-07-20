<?php

namespace Modules\App\Services;

use Illuminate\Http\JsonResponse;

interface ResponseFactoryInterface
{
    /**
     * Make the success response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    public function success($data = null);

    /**
     * Make the error response
     *
     * @param int|string|object $code
     * @param int $httpCode
     * @param mixed $data
     * @return JsonResponse
     */
    public function error($code, $data = null, $httpCode = 400);

    /**
     * Make the response
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    public function make($data = null, $status = 200);
}