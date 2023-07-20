<?php

namespace Modules\Marketplace\Services;

use Modules\Store\Models\Store;

abstract class OAuth2StoreConnector
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * OAuth2StoreConnector constructor
     *
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Make new connection
     *
     * @return StoreConnectionInterface
     */
    abstract protected function makeConnection();

    /**
     * Perform refresh access token
     */
    abstract protected function performRefreshAccessToken();

    /**
     * Connect to store
     *
     * @return StoreConnectionInterface
     * @throws MarketplaceException
     */
    public function connect()
    {
        if ($this->store->status === Store::STATUS_DISCONNECTED) {
            throw new MarketplaceException("The store {$this->store->id} disconnected");
        }

        if ($this->accessTokenExpired()) {
            $this->refreshAccessToken();
        }

        return $this->makeConnection();
    }

    /**
     * Refresh access token
     *
     * @return Store
     * @throws MarketplaceException
     */
    public function refreshAccessToken()
    {
        if ($this->refreshTokenExpired()) {
            $this->store->disconnect();
            throw new MarketplaceException("The store {$this->store->id} disconnected");
        }

        $this->performRefreshAccessToken();

        return $this->store;
    }

    /**
     * Get expire time of access token (timestamp)
     *
     * @return int
     */
    public function getAccessTokenExpiredAt()
    {
        return (int)$this->store->getSetting('access_token_expired_at');
    }

    /**
     * Return true if access token expired
     *
     * @return bool
     */
    public function accessTokenExpired()
    {
        return $this->getAccessTokenExpiredAt() <= time();
    }

    /**
     * Get expire time of refresh token (timestamp)
     *
     * @return int
     */
    public function getRefreshTokenExpiredAt()
    {
        return (int)$this->store->getSetting('refresh_token_expired_at');
    }

    /**
     * Return true if refresh token expired
     *
     * @return bool
     */
    public function refreshTokenExpired()
    {
        return $this->getRefreshTokenExpiredAt() <= time();
    }

    /**
     * @return Store
     */
    public function getStore()
    {
        return $this->store;
    }
}
