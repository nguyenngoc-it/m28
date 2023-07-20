<?php

namespace Modules\Marketplace\Services;

use Modules\Store\Models\Store;

interface MarketplaceInterface
{
    /**
     * Marketplace code
     *
     * @return string
     */
    public function getCode();

    /**
     * Marketplace name
     *
     * @return string
     */
    public function getName();

    /**
     * Connect to store
     *
     * @param Store $store
     * @return StoreConnectionInterface
     */
    public function connect(Store $store);
}
