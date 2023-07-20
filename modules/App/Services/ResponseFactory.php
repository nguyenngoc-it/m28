<?php

namespace Modules\App\Services;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Gobiz\Transformer\TransformerManagerInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var TransformerManagerInterface
     */
    protected $transformers;

    /**
     * ResponseFactory constructor
     *
     * @param TransformerManagerInterface $transformers
     */
    public function __construct(TransformerManagerInterface $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * Make the success response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    public function success($data = null)
    {
        return $this->make($data);
    }

    /**
     * Make the error response
     *
     * @param int|string|object $code
     * @param int $httpCode
     * @param mixed $data
     * @return JsonResponse
     */
    public function error($code,  $data = null, $httpCode = 400)
    {
        return $this->make($this->makeErrorData($code, $data), $httpCode);
    }

    /**
     * @param int|string|object $code
     * @param mixed $data
     * @return array
     */
    protected function makeErrorData($code, $data)
    {
        if ($code instanceof Validator) {
            return [
                'code' => 'INPUT_INVALID',
                'data' => $code,
            ];
        }

        return compact('code', 'data');
    }

    /**
     * Make the response
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    public function make($data = null, $status = 200)
    {
        return new JsonResponse($this->transformers->transform($data), $status);
    }
}