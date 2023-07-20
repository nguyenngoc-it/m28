<?php

namespace Modules\EventBridge\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\Order;

class OrderTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Order $order
     * @return mixed
     */
    public function transform($order)
    {
        return array_merge($order->only([
            'id',
            'tenant_id',
            'merchant_id',
            'store_id',
            'name_store',
            'marketplace_code',
            'marketplace_store_id',
            'code',
            'ref_code',
            'status',
            'order_amount',
            'discount_amount',
            'shipping_amount',
            'expected_shipping_amount',
            'total_amount',
            'paid_amount',
            'debit_amount',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_note',
            'intended_delivery_at',
            'payment_time',
            'payment_type',
            'payment_method',
            'description',
            'customer_id',
            'customer_address_id',
            'sale_id',
            'created_at',
            'updated_at',
            'receiver_country_id',
            'receiver_province_id',
            'receiver_district_id',
            'receiver_ward_id',
            'extra_services',
            'creator_id',
            'cancel_note',
            'cancel_reason',
            'freight_bill',
            'campaign',
            'created_at_origin',
            'currency_id',
            'shipping_partner_id',
            'cod',
            'finance_status',
            'cod_fee_amount',
            'other_fee',
            'service_amount',
            'extent_service_expected_amount',
            'extent_service_amount',
            'finance_extent_service_status',
            'amount_paid_to_seller',
            'finance_service_status',
            'service_import_return_goods_amount',
            'finance_service_import_return_goods_status',
            'cost_price',
            'dropship',
            'warehouse_id',
            'inspected',
            'priority',
            'packer_id',
            'packed_at',
            'has_document_inventory',
            'receiver_postal_code',
            'delivery_fee',
            'standard_code',
            'payment_note',
            'shipping_financial_status',
        ]), [
            'receiver_province_code' => $order->receiverProvince ? $order->receiverProvince->code : null,
            'receiver_district_code' => $order->receiverDistrict ? $order->receiverDistrict->code : null,
            'receiver_ward_code' => $order->receiverWard ? $order->receiverWard->code : null,
        ]);
    }
}
