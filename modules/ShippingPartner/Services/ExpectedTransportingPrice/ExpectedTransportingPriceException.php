<?php

namespace Modules\ShippingPartner\Services\ExpectedTransportingPrice;

use Exception;
use Gobiz\Log\LogService;

class ExpectedTransportingPriceException extends Exception
{
    /**
     * @var array
     */
    protected $payload = [];

    /**
     *
     * @param string $error
     * @param array $payload
     */
    public function __construct(string $error, array $payload = [])
    {
        parent::__construct($error);
        LogService::logger('expected-transporting-exception')->error($error, $payload);
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
