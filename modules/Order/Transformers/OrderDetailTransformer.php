<?php

namespace Modules\Order\Transformers;

use App\Base\Transformer;
use Modules\Auth\Services\Permission;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\User\Models\User;

class OrderDetailTransformer extends Transformer
{
    use OrderDetailTrait;

    /**
     * @var User|null
     */
    protected $user = null;
    protected $isOperation;

    public function __construct(User $user, $isOperation = false)
    {
        $this->user = $user;
        $this->isOperation = $isOperation;
    }

    /**
     * Transform the data
     *
     * @param Order $order
     * @return mixed
     */
    public function transform($order)
    {
        $canViewCustomer           = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        $creator                   = $order->creator()->first(['id', 'username', 'name', 'email', 'phone', 'avatar']);
        $orderTransactions         = $order->orderTransactions()->orderBy('id', 'desc')->get();
        $importReturnGoodsServices = [];
        $freightBills = !(count($order->freightBills) > 0);

        foreach ($order->orderImportReturnGoodsServices as $orderImportReturnGoodsService) {
            $importReturnGoodsServices[] = [
                'service' => $orderImportReturnGoodsService->service,
                'service_price' => $orderImportReturnGoodsService->servicePrice,
            ];
        }

        return array_merge($order->only([
            'customerAddress', 'customer', 'sale',
            'receiverCountry', 'receiverProvince', 'receiverDistrict', 'receiverWard', 'currency',
        ]), [
            'tenant' => $order->tenant->only(['id', 'code']),
            'merchant' => $order->merchant->only(['id', 'code', 'name', 'location_id']),
            'shippingPartner' => ($order->shippingPartner) ? $order->shippingPartner->only(['id', 'code', 'name', 'provider', 'required_postcode']) : null,
            'order' => $order,
            'order_sku_combo' => $this->makeOrderSkuCombos($order),
            'orderTransactions' => $orderTransactions,
            'import_return_goods_services' => $importReturnGoodsServices,
            'orderSkus' => $this->makeOrderSkus($order, $this->isOperation),
            'orderStocks' => $this->makeOrderStocks($order),
            'orderFreightBills' => $this->makeOrderFreightBills($order),
            'documents' => $this->makeDocuments($order),
            'creator' => $creator,
            'canInspection' => Service::order()->canInspection($order, $this->user),
            'canCreatePackage' => $order->canCreatePackage($this->user),
            'canDelivery' => Service::order()->canDelivery($order, $this->user),
            'canPaymentConfirm' => $order->canPaymentConfirm($this->user),
            'canCancel' => Service::order()->canCancel($order, $this->user),
            'canSync' => $order->canSync(),
            'can_view_customer' => $canViewCustomer,
            'can_update_carrier' => $this->user->can(Permission::ORDER_UPDATE_CARRIER),
            'cancel_reasons' => Order::$cancelReasons,
            'can_update_order' => $order->canUpdateOrder() && $this->user->can(Permission::ORDER_UPDATE),
            'warehouse' => $order->warehouse,
            'tags' => $order->getTags(),
            'can_update_receiver_postal_code' => $freightBills
        ]);
    }
}
