<?php

namespace Gobiz\Support;

use Exception;

class RestApiException extends Exception
{
    /**
     * @var RestApiResponse
     */
    protected $response;

    /**
     * RestApiException constructor
     *
     * @param RestApiResponse $response
     */
    public function __construct(RestApiResponse $response)
    {
        $this->response = $response;
        parent::__construct($response->getBody() ?: json_encode($response->getData()), (int)$response->getStatusCode());
    }

    /**
     * @return RestApiResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
