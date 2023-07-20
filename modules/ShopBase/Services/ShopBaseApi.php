<?php

namespace Modules\ShopBase\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;

class ShopBaseApi implements ShopBaseApiInterface
{
    /**
     * @var Client
     */
    protected $url;

    protected $logger;

    /**
     * ShopBaseApi constructor.
     * @param $account
     * @param $apiKey
     * @param $password
     * @param array $options
     */
    public function __construct($account, $apiKey, $password, array $options = [])
    {
        $this->url = "https://{$apiKey}:{$password}@{$account}.onshopbase.com/admin";

        $this->logger = LogService::logger('shop-base-api');
    }

    /**
     * @param $url
     * @param $postData
     * @param string $method
     * @return mixed
     */
    protected function request($url, $postData = [], $method = 'POST')
    {
        $postData = json_encode($postData);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        $this->logger->info('Debug', compact('postData', 'result'));

        return @json_decode($result);
    }

    /**
     * Create webhook
     *
     * @param array $payload
     * @return RestApiResponse
     */
    public function createWebhook(array $payload)
    {
        $url = $this->url.'/webhooks.json';

        return $this->request($url, $payload);
    }

    /**
     * Delete webhook
     * @param $webhookId
     * @return RestApiResponse
     */
    public function deleteWebhook($webhookId)
    {
        $url = $this->url.'/webhooks/'.$webhookId.'.json';

        return $this->request($url, [], 'DELETE');
    }
}
