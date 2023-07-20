<?php

namespace Modules\Order\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Generator;
use Gobiz\Database\DBHelper;
use Modules\Auth\Services\Permission;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ExportOrder
{
    /**
     * @var array
     */
    protected $filter;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var bool
     */
    protected $checkViewCustomer;

    /**
     * ExportOrder constructor
     *
     * @param array $filter
     * @param User $user
     * @param bool $checkViewCustomer
     */
    public function __construct(array $filter, User $user, $checkViewCustomer = true)
    {
        $this->filter            = $filter;
        $this->user              = $user;
        $this->checkViewCustomer = $checkViewCustomer;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->makeGenerator()))->export('/tmp/order-export-' . $this->user->id . '.xlsx');
    }

    /**
     * @return Generator
     */
    public function makeGenerator()
    {
        /**
         * @var Order $order
         */
        $results = DBHelper::chunkByIdGenerator($this->makeQuery(), 200, 'orders.id', 'id');

        foreach ($results as $orders) {
            foreach ($orders as $order) {
                foreach ($order->orderSkus as $orderSku) {
                    yield $this->makeRow($order, $orderSku);
                }
            }
        }
    }

    protected function makeQuery()
    {
        return Service::order()->query($this->filter)
            ->with([
                'orderSkus.sku.unit',
                'orderStocks.warehouse',
                'orderStocks.warehouseArea',
                'orderPackings.freightBill',
                'merchant',
                'receiverCountry',
                'receiverProvince',
                'receiverDistrict',
                'receiverWard',
            ])
            ->getQuery();
    }

    /**
     * @param Order $order
     * @param OrderSku $orderSku
     * @return array
     */
    protected function makeRow(Order $order, OrderSku $orderSku)
    {
        /**
         * @var OrderStock $orderStock
         */
        $orderStock       = $order->orderStocks->filter(function (OrderStock $orderStock) use ($orderSku) {
            return $orderStock->sku_id == $orderSku->sku_id;
        })->first();
        $sku              = $orderSku->sku;
        $merchant         = $order->merchant;
        $canViewCustomer  = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        $receiver_address = ($canViewCustomer || !$this->checkViewCustomer) ? $order->receiver_address : '***';
        $receiver_phone   = ($canViewCustomer || !$this->checkViewCustomer) ? $order->receiver_phone : '***';
        /** @var OrderPacking|null $orderPacking */
        $orderPacking = $order->orderPacking;

        return [
            trans('order_code') => $order->code,
            trans('tracking_number') => $orderPacking ? ($orderPacking->freightBill ? $orderPacking->freightBill->freight_bill_code : '') : '',
            trans('shipping_partner_code') => $order->shippingPartner ? $order->shippingPartner->code : '',
            trans('name_store') => $order->name_store,
            trans('campaign') => $order->campaign,
            trans('created_date') => $order->created_at_origin ? date('d/m/Y', strtotime($order->created_at_origin)) : '',
            trans('seller_code') => $merchant->code,
            trans('seller_name') => $merchant->name,
            trans('buyer_name') . ' *' => $order->receiver_name,
            trans('contact_number') . ' *' => $receiver_phone,
            trans('shipping_country') . ' *' => $order->receiverCountry ? $order->receiverCountry->label : '',
            trans('receiver_postal_code')  => $order->receiver_postal_code ? : '',
            trans('shipping_province') . ' *' => $order->receiverProvince ? $order->receiverProvince->label : '',
            trans('shipping_district') . ' *' => $order->receiverDistrict ? $order->receiverDistrict->label : '',
            trans('shipping_ward') . ' *' => $order->receiverWard ? $order->receiverWard->label : '',
            trans('shipping_address') . ' *' => $receiver_address,
            trans('sku_code') . ' *' => $sku->code ?? '',
            trans('sku_name') => $sku->name ?? '',
            trans('warehouse_code_name') => $orderStock instanceof OrderStock && $orderStock->warehouse ? $orderStock->warehouse->code . ' - ' . $orderStock->warehouse->name : '',
            trans('warehouse_area_code') => $orderStock instanceof OrderStock && $orderStock->warehouseArea ? $orderStock->warehouseArea->code : '',
            trans('quantity') . ' *' => $orderSku->quantity,
            trans('properties_unit') => $sku->unit->name ?? '',
            trans('price') . ' *' => round($orderSku->price, 2),
            trans('discount') . ' *' => round($orderSku->discount_amount, 2),
            trans('subtotal') => $this->formatMoney($orderSku->total_amount),
            trans('total_payment') => $this->formatMoney($order->order_amount),
            trans('discount_price') => $this->formatMoney($order->discount_amount),
            trans('cod') . ' *' => round($order->cod, 2),
            trans('status') => trans('order.status.' . $order->status),
            trans('finance_status') => trans('order.finance_status.' . $order->finance_status)
        ];
    }

    /**
     * @param $amount
     * @return string|string[]
     */
    public function formatMoney($amount)
    {
        $tenant = $this->user->tenant;

        $amountFormatted = number_format(
            $amount,
            (int)$tenant->getSetting(Tenant::SETTING_CURRENCY_PRECISION),
            $tenant->getSetting(Tenant::SETTING_CURRENCY_DECIMAL_SEPARATOR),
            $tenant->getSetting(Tenant::SETTING_CURRENCY_THOUSANDS_SEPARATOR)
        );

        return str_replace('{amount}', $amountFormatted, $tenant->getSetting(Tenant::SETTING_CURRENCY_FORMAT));
    }
}
