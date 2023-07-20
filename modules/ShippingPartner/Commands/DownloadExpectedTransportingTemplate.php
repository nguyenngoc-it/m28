<?php

namespace Modules\ShippingPartner\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;
use Modules\Warehouse\Models\Warehouse;

class DownloadExpectedTransportingTemplate
{
    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * DownloadReceivedSkus constructor.
     * @param ShippingPartner $shippingPartner
     * @param Warehouse $warehouse
     */
    public function __construct(ShippingPartner $shippingPartner, Warehouse $warehouse)
    {
        $this->shippingPartner = $shippingPartner;
        $this->warehouse       = $warehouse;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws ExpectedTransportingPriceException
     */
    public function handle(): string
    {
        return $this->shippingPartner->expectedTransporting()->makeTemplateTablePrice($this->warehouse);
    }
}
