<?php

namespace Gobiz\Support\Traits;

use Closure;
use Gobiz\Support\RestApiException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Gobiz\Support\RestApiResponse;
use Throwable;

trait RestApiRequestTrait
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Perform send request (throw RestApiException if request failed)
     *
     * @param Closure $handler
     * @return RestApiResponse
     * @throws RestApiException
     */
    protected function sendRequest(Closure $handler)
    {
        $res = $this->request($handler);

        if (!$res->success()) {
            throw new RestApiException($res);
        }

        return $res;
    }

    /**
     * Perform send request
     *
     * @param Closure $handler
     * @return RestApiResponse
     */
    protected function request(Closure $handler)
    {
        try {
            return $this->makeResponse(true, $handler());
        } catch (RequestException $e) {
            return $this->makeResponse(false, $e->getResponse());
        } catch (Throwable $e) {
            return $this->makeExceptionResponse($e);
        }
    }

    /**
     * @param bool $success
     * @param ResponseInterface|null $response
     * @return RestApiResponse
     */
    protected function makeResponse($success, $response)
    {
        $body = ($response && ($body = $response->getBody())) ? $body->getContents() : null;
        $data = $body ? (json_decode($body, true) ?: []) : [];

        if (!$success) {
            $this->logError('REQUEST_ERROR', ['body' => $body]);
        }

        return new RestApiResponse($success, $data, [
            'body' => $body,
            'headers' => $response ? $response->getHeaders() : [],
            'status_code' => $response ? $response->getStatusCode() : null,
        ]);
    }

    /**
     * @param Throwable $e
     * @return RestApiResponse
     */
    protected function makeExceptionResponse(Throwable $e)
    {
        $data = [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        $this->logError('REQUEST_EXCEPTION', $data);

        return new RestApiResponse(false, $data);
    }

    /**
     * @param string $error
     * @param array $context
     */
    protected function logError($error, array $context = [])
    {
        if ($this->logger) {
            $this->logger->error($error, $context);
        }
    }
}
