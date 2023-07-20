<?php

namespace Modules\ShippingPartner\Services\ExpectedTransportingPrice;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\Order\Models\Order;
use Modules\Warehouse\Models\Warehouse;

class ExpectedTransporting
{
    protected $expectedTransportingPrice;

    /**
     * ExpectedTransporting constructor.
     * @param ExpectedTransportingPriceInterface $expectedTransportingPrice
     */
    public function __construct(ExpectedTransportingPriceInterface $expectedTransportingPrice)
    {
        $this->expectedTransportingPrice = $expectedTransportingPrice;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->expectedTransportingPrice->getCountryCode();
    }

    /**
     * @param Warehouse $warehouse
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function makeTemplateTablePrice(Warehouse $warehouse): string
    {
        return $this->expectedTransportingPrice->makeTemplateTablePrice($warehouse);
    }

    /**
     * @return array
     */
    public function requiredFieldTablePrices(): array
    {
        return $this->expectedTransportingPrice->requiredFieldTablePrices();
    }

    /**
     * @return void
     */
    public function makeTablePrice(array $inputs)
    {
        $this->expectedTransportingPrice->makeTablePrice($inputs);
    }

    /**
     * @param Order $order
     * @param bool $retry
     * @param bool $snapshot
     * @return float
     * @throws ExpectedTransportingPriceException
     */
    public function getPrice(Order $order, bool $retry = false, bool $snapshot = false): float
    {
        return $this->expectedTransportingPrice->getPrice($order, $retry, $snapshot);
    }

    /**
     * @param Order $order
     * @return float
     * @throws ExpectedTransportingPriceException
     */
    public function getReturnPrice(Order $order): float
    {
        return $this->expectedTransportingPrice->getReturnPrice($order);
    }
}
