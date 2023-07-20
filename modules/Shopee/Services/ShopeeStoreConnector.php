<?php

namespace Modules\Shopee\Services;

use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\OAuth2StoreConnector;
use Modules\Marketplace\Services\StoreConnectionInterface;
use Modules\Service;

class ShopeeStoreConnector extends OAuth2StoreConnector
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

            // Get shop info
            $shopInfo = (new ShopeeShopApi($this->store->marketplace_store_id, data_get($token, 'access_token')))->getShopInfo()->getData();
            $storeName = data_get($shopInfo, 'shop_name');
            $this->store->name = $storeName;

        } catch (RestApiException $exception) {
            $error = $exception->getResponse()->getData('error');
            $message = $exception->getResponse()->getData('message');
            $errorCode = Service::shopee()->detectApiError($error, $message);

            if (in_array($errorCode, [Shopee::ERROR_REFRESH_TOKEN_INVALID, Shopee::ERROR_NO_LINKED])) {
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
