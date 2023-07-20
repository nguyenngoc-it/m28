<?php

namespace Modules\Lazada\Services;

use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\OAuth2StoreConnector;
use Modules\Marketplace\Services\StoreConnectionInterface;
use Modules\Service;

class LazadaStoreConnector extends OAuth2StoreConnector
{
    /**
     * Make new connection
     *
     * @return StoreConnectionInterface
     */
    protected function makeConnection()
    {
        return new ShopeeShopApi($this->getShopId(), $this->store->getSetting('access_token'));
    }

    /**
     * Perform refresh access token
     *
     * @throws RestApiException
     */
    protected function performRefreshAccessToken()
    {
        try {
            $token = Service::shopee()->publicApi()->refreshAccessToken([
                'refresh_token' => $this->store->getSetting('refresh_token'),
                'shop_id' => $this->getShopId(),
            ])->getData();
        } catch (RestApiException $exception) {
            $error = $exception->getResponse()->getData('error');
            $message = $exception->getResponse()->getData('message');

            if (Service::shopee()->detectApiError($error, $message) === Shopee::ERROR_REFRESH_TOKEN_INVALID) {
                $this->store->disconnect();
            }

            throw $exception;
        }

        $this->store->settings = array_merge($this->store->settings, Service::shopee()->makeToken($token));
        $this->store->save();
    }

    /**
     * @return int
     */
    protected function getShopId()
    {
        return (int)$this->store->marketplace_store_id;
    }
}
