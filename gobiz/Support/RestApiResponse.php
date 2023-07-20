<?php

namespace Gobiz\Support;

use Illuminate\Support\Arr;

class RestApiResponse
{
    /**
     * @var string
     */
    protected $success;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $response = [];

    /**
     * WebhookResponse constructor
     *
     * @param string $success
     * @param string|array $data
     * @param array $response   $response = ['body' => '', 'headers' => [], 'status_code' => 200]
     */
    public function __construct($success, $data, $response = [])
    {
        $this->success = !!$success;
        $this->data = is_array($data) ? $data : ['message' => $data];
        $this->response = $response;
    }

    /**
     * Return true if response successful
     *
     * @return bool
     */
    public function success()
    {
        return $this->success;
    }

    /**
     * Get the response data
     *
     * @param string $key
     * @param mixed $default
     * @return array|mixed
     */
    public function getData($key = null, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getResponse('body');
    }

    /**
     * Get the response header
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($key = null, $default = null)
    {
        return Arr::get($this->getResponse('headers', []), $key, $default);
    }

    /**
     * Get the response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->getResponse('status_code');
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getResponse($key = null, $default = null)
    {
        return Arr::get($this->response, $key, $default);
    }
}
