<?php

namespace Modules\Order\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\Order;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\User\Models\User;

class MerchantOrderDetailTransformer extends Transformer
{
    use OrderDetailTrait;

    /**
     * @var User|null
     */
    protected $user = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Transform the data
     *
     * @param Order $order
     * @return mixed
     */
    public function transform($order)
    {
        $orderTransactions = $order->orderTransactions()->orderBy('id', 'desc')->get();
        $creator           = $order->creator()->first(['id', 'username', 'name', 'email', 'phone', 'avatar']);

        return array_merge($order->only([
            'customer', 'sale', 'currency',
        ]), [
            'tenant' => $order->tenant->only(['id', 'code']),
            'merchant' => $this->user->merchant->only(['id', 'code', 'name', 'location_id']),
            'shipping_partner' => ($order->shippingPartner) ? $order->shippingPartner->only(['id', 'code', 'name', 'provider']) : null,
            'order' => $order,
            'order_sku_combo' => $this->makeOrderSkuCombos($order),
            'service_amount' => $order->service_amount,
            'remaining_amount' => $order->amount_paid_to_seller,
            'order_transactions' => $orderTransactions,
            'order_skus' => $this->makeOrderSkus($order),
            'order_stocks' => $this->makeOrderStocks($order),
            'order_freight_bills' => $this->makeOrderFreightBills($order),
            'creator' => $creator,
            'receiver_country' => $order->receiverCountry,
            'warehouse' => $order->warehouse,
            'receiver_province' => $order->receiverProvince,
            'receiver_district' => $order->receiverDistrict,
            'receiver_ward' => $order->receiverWard,
            'customer_address' => $order->customerAddress,
            'documents' => $this->makeDocuments($order),
            'can_cancel' => Service::order()->sellerCanCancel($order, $this->user),
            'returned_skus' => $order->returnedSkus(),
        ]);
    }
}
