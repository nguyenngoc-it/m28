<?php

namespace Modules\Marketplace\Services;

use Exception;

interface StoreConnectionInterface
{
    /**
     * Test connection
     *
     * @throws Exception
     */
    public function testConnection();
}
