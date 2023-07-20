<?php

namespace Modules\ShippingPartner\Services\ExpectedTransportingPrice;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Generator;
use Gobiz\Support\Conversion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\Order\Models\ExpectedTransportingOrderSnapshot;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\ShippingPartner\Models\ExpectedTransportingPriceByWeight;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;

class JNTTExpectedTransportingPrice implements ExpectedTransportingPriceInterface
{

    /**
     * @param Warehouse $warehouse
     * @return Generator
     */
    protected function locationGenerator(Warehouse $warehouse): Generator
    {
        $locationQuery = Location::query()
            ->where('parent_code', $warehouse->country->code)
            ->where('type', Location::TYPE_PROVINCE);
        foreach ($locationQuery->cursor() as $data) {
            yield $data;
        }
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return Location::COUNTRY_THAILAND;
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
        return (new FastExcel($this->locationGenerator($warehouse)))
            ->export('downloadExpectedTransportingTemplate.xlsx', function (Location $location) {
                return [
                    trans('max_weight') => '',
                    trans('price', [], 'vi') => '',
                    trans('return_price_ratio') => '',
                    trans('shipping_province', [], 'vi') => $location->label,
                    'receiver_province_id' => $location->id
                ];
            });
    }

    /**
     * Lấy các field cần valid theo bảng phí
     *
     * @return array
     */
    public function requiredFieldTablePrices(): array
    {
        return ['max_weight', 'price', 'return_price_ratio', 'receiver_province', 'receiver_province_id'];
    }

    /**
     * @return void
     */
    public function makeTablePrice(array $inputs)
    {
        ExpectedTransportingPriceByWeight::updateOrCreate(
            [
                'max_weight' => Arr::get($inputs, 'max_weight'),
                'tenant_id' => Arr::get($inputs, 'tenant_id'),
                'warehouse_id' => Arr::get($inputs, 'warehouse_id'),
                'shipping_partner_id' => Arr::get($inputs, 'shipping_partner_id'),
                'receiver_province_id' => Arr::get($inputs, 'receiver_province_id')
            ],
            [
                'receiver_province' => Arr::get($inputs, 'receiver_province'),
                'price' => Arr::get($inputs, 'price'),
                'return_price_ratio' => Arr::get($inputs, 'return_price_ratio'),
            ]
        );
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
        if ($order->expected_shipping_amount && !$retry) {
            return $order->expected_shipping_amount;
        }

        if (empty($order->orderPacking)) {
            throw new ExpectedTransportingPriceException('Not found order packing with order ' . $order->code);
        }

        if (empty($order->expectedTransportingOrderSnapshot)) {
            $weight = Conversion::convertWeight((($order->orderPacking->height * $order->orderPacking->width * $order->orderPacking->length) * 1000000 / 6000));
            $skus   = $order->orderSkus->map(function (OrderSku $orderSku) {
                $sku = $orderSku->sku;
                return $sku->only(['id', 'code', 'weight', 'length', 'width', 'height']);
            })->toArray();
        } else {
            $weight = $order->expectedTransportingOrderSnapshot->weight;
            $skus   = $order->expectedTransportingOrderSnapshot->skus;
        }
        /** @var ExpectedTransportingPriceByWeight $applyPrice */
        $applyPrice = ExpectedTransportingPriceByWeight::query()->where([
            'shipping_partner_id' => $order->orderPacking->shipping_partner_id,
            'warehouse_id' => $order->orderPacking->warehouse_id,
            'receiver_province_id' => $order->receiver_province_id
        ])->where('max_weight', '>=', $weight)->orderBy('max_weight')->first();
        if (empty($applyPrice)) {
            throw new ExpectedTransportingPriceException('Not found expected transporting price for order ' . $order->code, [
                'shipping_partner_id' => $order->orderPacking->shipping_partner_id,
                'warehouse_id' => $order->orderPacking->warehouse_id,
                'receiver_province_id' => $order->receiver_province_id,
                'weight' => $weight
            ]);
        }
        DB::transaction(function () use ($order, $weight, $skus, $applyPrice, $snapshot) {
            if ($snapshot) {
                ExpectedTransportingOrderSnapshot::updateOrCreate(
                    [
                        'order_id' => $order->id,
                    ],
                    [
                        'weight' => $weight,
                        'height' => $order->orderPacking->height,
                        'width' => $order->orderPacking->width,
                        'length' => $order->orderPacking->length,
                        'skus' => $skus,
                        'apply_price' => $applyPrice->toArray()
                    ]
                );
            }
            $order->expected_shipping_amount = $applyPrice->price;
            $order->save();
            $order->logActivity(OrderEvent::UPDATE_EXPECTED_TRANSPORTING_PRICE, Service::user()->getSystemUserDefault(), [
                [
                    'weight' => $weight,
                    'skus' => $skus,
                    'apply_price' => $applyPrice->toArray()
                ]
            ]);
        });

        return $applyPrice->price;
    }

    /**
     * @param Order $order
     * @return float
     * @throws ExpectedTransportingPriceException
     */
    public function getReturnPrice(Order $order): float
    {
        /** @var ExpectedTransportingOrderSnapshot|null $expectedTransportingOrderSnapshot */
        $expectedTransportingOrderSnapshot = ExpectedTransportingOrderSnapshot::query()->where('order_id', $order->id)
            ->first();
        if (empty($expectedTransportingOrderSnapshot)) {
            throw new ExpectedTransportingPriceException('Not found Expected Transporting Order Snapshot for order ' . $order->code);
        }

        if (empty($order->expected_shipping_amount)) {
            throw new ExpectedTransportingPriceException('Empty Expected Transporting Price for order ' . $order->code);
        }
        $price                           = $this->getPrice($order, true);
        $returnPrice                     = Conversion::convertMoney($price * (float)$expectedTransportingOrderSnapshot->apply_price['return_price_ratio']);
        $order->expected_shipping_amount = $price + $returnPrice;
        $order->save();
        $order->logActivity(OrderEvent::UPDATE_EXPECTED_TRANSPORTING_PRICE, Service::user()->getSystemUserDefault(), [
            'price' => $price,
            'return_price' => $returnPrice
        ]);

        return $returnPrice;
    }
}
