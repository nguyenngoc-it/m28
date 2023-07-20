<?php

namespace Modules\ShippingPartner\Services\ShippingPartnerApi;

use Exception;

class ShippingPartnerApiException extends Exception
{
    /**
     * @var string
     */
    protected $error;

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * ShippingPartnerApiException constructor
     *
     * @param string $error
     * @param array $payload
     */
    public function __construct($error, array $payload = [])
    {
        $this->error   = $error;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
