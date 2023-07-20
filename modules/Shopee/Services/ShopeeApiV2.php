<?php

namespace Modules\Shopee\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class ShopeeApiV2
{
    use RestApiRequestTrait;

    /**
     * @var int
     */
    protected $partnerId;

    /**
     * @var string
     */
    protected $partnerKey;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShopeeApiV2 constructor
     */
    public function __construct()
    {
        $this->partnerId = (int)config('services.shopee.partner_id');
        $this->partnerKey = config('services.shopee.partner_key');
        $this->http = new Client(['base_uri' => config('services.shopee.api_url')]);
        $this->logger = LogService::logger('shopee-api');
    }

    /**
     * Make common params
     *
     * @param string $apiPath
     * @return array
     */
    abstract protected function makeCommonParams($apiPath);

    /**
     * Send GET request
     *
     * @param string $apiPath
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    protected function getRequest($apiPath, array $input = [])
    {
        return $this->sendRequest(function () use ($apiPath, $input) {
            $query = http_build_query(array_merge($input, $this->makeCommonParams($apiPath)));
            $query = preg_replace('/%5B\d+%5D=/', '=', $query);

            return $this->http->get($apiPath, ['query' => $query]);
        });
    }

    /**
     * Send POST request
     *
     * @param string $apiPath
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    protected function postRequest($apiPath, array $input = [])
    {
        return $this->sendRequest(function () use ($apiPath, $input) {
            return $this->http->post($apiPath, [
                'query' => $this->makeCommonParams($apiPath),
                'json' => $input,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    protected function sendRequest(Closure $handler)
    {
        $res = $this->request($handler);

        if (!$res->success() || $res->getData('error')) {
            throw new RestApiException(new RestApiResponse(false, $res->getData(), $res->getResponse()));
        }

        return $res;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function makeSign($data)
    {
        return hash_hmac('sha256', $data, $this->partnerKey);
    }
}
