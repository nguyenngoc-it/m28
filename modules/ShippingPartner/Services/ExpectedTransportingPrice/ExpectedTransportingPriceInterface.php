<?php

namespace Modules\ShippingPartner\Services\ExpectedTransportingPrice;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use http\Encoding\Stream\Inflate;
use Modules\Order\Models\Order;
use Modules\Warehouse\Models\Warehouse;

interface ExpectedTransportingPriceInterface
{
    /**
     * @return string
     */
    public function getCountryCode(): string;

    /**
     * @param Warehouse $warehouse
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function makeTemplateTablePrice(Warehouse $warehouse): string;

    /**
     * Lấy các field cần valid theo bảng phí
     *
     * @return array
     */
    public function requiredFieldTablePrices(): array;

    /**
     * @return void
     */
    public function makeTablePrice(array $inputs);

    /**
     * @param Order $order
     * @param bool $retry
     * @param bool $snapshot
     * @return float
     * @throws ExpectedTransportingPriceException
     */
    public function getPrice(Order $order, bool $retry = false, bool $snapshot = false): float;

    /**
     * @param Order $order
     * @return float
     * @throws ExpectedTransportingPriceException
     */
    public function getReturnPrice(Order $order): float;
}
