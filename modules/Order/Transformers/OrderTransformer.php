<?php

namespace Modules\Order\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\FreightBill\Models\FreightBill;
use Modules\FreightBill\Transformers\FreightBillTransformerNew;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\User\Models\User;
use Modules\User\Transformers\UserTransformerNew;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class OrderTransformer extends TransformerAbstract
{

    public function __construct()
    {
        $this->setAvailableIncludes(['creator', 'shipping_info', 'order_skus', 'order_sku_combos', 'order_transactions', 'warehouse']);
    }

    public function transform(Order $order)
    {
        //Business logic
        $locations = $this->getReceiverLocations($order);

        return [
            'id' => (int)$order->id,
            'code' => $order->code,
            'ref_code' => $order->ref_code,
            'campaign' => $order->campaign,
            'status' => $order->status,
            'cancel_reason' => $order->cancel_reason,
            'order_amount' => (float)$order->order_amount,
            'discount_amount' => (float)$order->discount_amount,
            'shipping_amount' => (float)$order->shipping_amount,
            'total_amount' => (float)$order->total_amount,
            'paid_amount' => (float)$order->paid_amount,
            'debit_amount' => (float)$order->debit_amount,
            'return_goods_amount' => (float)$order->service_import_return_goods_amount,
            'other_fee' => (float)$order->other_fee,
            'service_amount' => (float)$order->service_amount,
            'extent_service_amount' => (float)$order->extent_service_amount,
            'cost_price_fee' => (float)$order->cost_price,
            'cod_fee_amount' => (float)$order->cod_fee_amount,
            'cod_amount' => (float)$order->cod,
            'currency' => $order->currency ? $order->currency->code : '',
            'receiver' => [
                'name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'address' => $order->receiver_address,
                'country' => data_get($locations, 'country.label'),
                'province' => data_get($locations, 'province.label'),
                'district' => data_get($locations, 'district.label'),
                'ward' => data_get($locations, 'ward.label'),
                'postal_code' => $order->receiver_postal_code,
            ],
            'payment_type' => $order->payment_type,
            'payment_method' => $order->payment_method,
            'payment_note' => $order->payment_note,
            'payment_time' => $order->payment_time,
            'finance_service_status' => $order->finance_service_status,
            'description' => $order->description,
            'intended_delivery_at' => $order->intended_delivery_at,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCreator(Order $order)
    {
        $creator = $order->creator;
        if (!$creator) {
            $creator = new User();
        }

        return $this->item($creator, new UserTransformerNew);
    }

    /**
     * Include ShippingInfo
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeShippingInfo(Order $order)
    {
        $freightBills = $order->freightBills;
        if (!$freightBills) {
            $freightBills = new FreightBill();
        }

        return $this->collection($freightBills, new FreightBillTransformerNew);
    }

    /**
     * Include Order Items
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeOrderSkus(Order $order)
    {
        $orderSkus = $order->orderSkus->where('from_sku_combo', OrderSku::FROM_SKU_COMBO_FALSE);

        return $this->collection($orderSkus, new OrderSkuTransformer);
    }

    /**
     * Include Order Items
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeOrderSkuCombos(Order $order)
    {
        $orderSkuCombos = $order->orderSkuCombos;

        return $this->collection($orderSkuCombos, new OrderSkuComboTransformer);
    }

    /**
     * Include Order Transactions
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeOrderTransactions(Order $order)
    {
        $orderTransactions = $order->orderTransactions;

        return $this->collection($orderTransactions, new OrderTransactionTransformer);
    }

    /**
     * Include Warehouse
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeWarehouse(Order $order)
    {
        $warehouse = $order->warehouse;
        if (!$warehouse) {
            $warehouse = new Warehouse();
        }

        return $this->item($warehouse, new WarehouseTransformerNew);
    }

    /**
     * Get Receiver Locations
     *
     * @param Order $order
     * @return array
     */
    protected function getReceiverLocations(Order $order)
    {
        $dataReturn = [
            'country' => null,
            'province' => null,
            'district' => null,
            'ward' => null,
        ];
        $locations  = [
            $order->receiver_country_id,
            $order->receiver_province_id,
            $order->receiver_district_id,
            $order->receiver_ward_id,
        ];
        $locations  = Location::whereIn('id', $locations)->get();
        if ($locations) {
            foreach ($locations as $location) {
                switch ($location->type) {
                    case Location::TYPE_COUNTRY:
                        $dataReturn['country'] = $location->toArray();
                        break;

                    case Location::TYPE_PROVINCE:
                        $dataReturn['province'] = $location->toArray();
                        break;

                    case Location::TYPE_DISTRICT:
                        $dataReturn['district'] = $location->toArray();
                        break;

                    case Location::TYPE_WARD:
                        $dataReturn['ward'] = $location->toArray();
                        break;

                    default:
                        # code...
                        break;
                }
            }
        }

        return $dataReturn;

    }
}
