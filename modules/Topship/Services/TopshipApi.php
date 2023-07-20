<?php

namespace Modules\Topship\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;

class TopshipApi implements TopshipApiInterface
{
    use RestApiRequestTrait;

    /**
     * @var array
     */
    protected $config = [
        'url' => 'https://api.etop.vn',
        'token' => null,
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * TopshipApi constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);

        $this->http = new Client([
            'base_uri' => rtrim($this->config['url'], '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['token'],
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->logger = LogService::logger('topship');
    }

    /**
     * Get current account info
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function me()
    {
        return $this->sendRequest(function () {
            return $this->http->post('/v1/shop.Misc/CurrentAccount');
        });
    }

    /**
     * Get fulfillment
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getFulfillment(array $input)
    {
        return $this->sendRequest(function () use ($input) {
            return $this->http->post('/v1/shop.Shipping/GetFulfillment', ['json' => $input]);
        });
    }

    /**
     * Get shipping services
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingServices(array $input)
    {
        return $this->sendRequest(function () use ($input) {
            return $this->http->post('/v1/shop.Shipping/GetShippingServices', ['json' => $input]);
        });
    }

    /**
     * Create and confirm order
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createAndConfirmOrder(array $input)
    {
        $this->logger->debug('CREATE_AND_CONFIRM_ORDER.REQUEST', compact('input'));

        $res = $this->sendRequest(function () use ($input) {
            return $this->http->post('/v1/shop.Shipping/CreateAndConfirmOrder', ['json' => $input]);
        });

        $this->logger->debug('CREATE_AND_CONFIRM_ORDER.SUCCESS', $res->getData());

        return $res;
    }

    /**
     * Create webhook
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createWebhook(array $input)
    {
        return $this->sendRequest(function () use ($input) {
            return $this->http->post('/v1/shop.Webhook/CreateWebhook', ['json' => $input]);
        });
    }

    /**
     * Delete webhook
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deleteWebhook(array $input)
    {
        return $this->sendRequest(function () use ($input) {
            return $this->http->post('/v1/shop.Webhook/DeleteWebhook', ['json' => $input]);
        });
    }

    /**
     * Delete webhook
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getWebhooks()
    {
        return $this->sendRequest(function () {
            return $this->http->post('/v1/shop.Webhook/GetWebhooks', ['body' => '{}']);
        });
    }
}
