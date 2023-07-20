<?php

namespace Modules\OrderPacking\Commands;

use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Location\Models\Location;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Services\OrderPackingEvent;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\User\Models\User;

class CreateTrackingNo
{
    /**
     * @var OrderPacking|null
     */
    protected $orderPacking = null;
    /**
     * @var int
     */
    protected $creatorId;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var string
     */
    protected $pickupType;

    /**
     * CreateTrackingNo constructor.
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @param string|null $pickupType
     */
    public function __construct(OrderPacking $orderPacking, $creatorId, $pickupType = null)
    {
        $this->orderPacking = $orderPacking;
        $this->creator      = User::find($creatorId);
        $this->pickupType   = $pickupType;
    }

    /**
     * @return OrderPacking|null
     * @throws WorkflowException
     */
    public function handle()
    {
        if (!$this->orderPacking->canCreateTrackingNo()) {
            return $this->orderPacking;
        }

        if (!$this->validateWeight()) {
            return $this->orderPacking;
        }

        $shippingPartner = $this->orderPacking->shippingPartner;
        if ($shippingPartner) {
            if ($shippingPartner->code == ShippingPartner::SHIPPING_PARTNER_JNTM) {
                $order = $this->orderPacking->order;
                if (!$order->receiver_postal_code) {
                    $this->orderPacking->error_type = OrderPacking::ERROR_RECEIVER_POSTAL_CODE;
                    $this->orderPacking->save();
                    return $this->orderPacking;
                }
            }
            
            if (!$shippingPartner->status) {
                $this->orderPacking->error_type = OrderPacking::ERROR_TYPE_SHIPPING_PARTNER_INACTIVE;
                $this->orderPacking->save();
                return $this->orderPacking;
            }
    
            $shippingPartnerOrder = null;
    
            try {
                $shippingPartnerOrder = $shippingPartner->api()->createOrder($this->orderPacking, $this->pickupType);
                if (!empty($shippingPartnerOrder->trackingNo)) {
                    $freightBill                         = $this->createFreightBill($shippingPartnerOrder);
                    $this->orderPacking->freight_bill_id = $freightBill->id;
                    if ($this->orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PICKING)) {
                        $this->orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PICKING, $this->creator);
                        if ($this->orderPacking->error_type) {
                            $this->orderPacking->error_type = null;
                        }
                    }
                } else {
                    $this->orderPacking->error_type = OrderPacking::ERROR_TYPE_TECHNICAL;
                }
    
            } catch (ShippingPartnerApiException $exception) {
                $this->orderPacking->error_type = OrderPacking::ERROR_TYPE_TECHNICAL;
            }
        }

        $this->orderPacking->save();
        $this->orderPacking->logActivity(OrderPackingEvent::CREATE_FREIGHT_BILL, $this->creator, $this->orderPacking->getChanges());

        return $this->orderPacking;
    }

    /**
     * @return bool
     */
    protected function validateWeight()
    {
        $orderPackingItems = $this->orderPacking->orderPackingItems;
        foreach ($orderPackingItems as $orderPackingItem) {
            if (empty($orderPackingItem->sku->weight)) {
                $this->orderPacking->error_type = OrderPacking::ERROR_TYPE_NO_WEIGHT;
                $this->orderPacking->save();
                return false;
            }
        }

        return true;
    }

    /**
     * @param ShippingPartnerOrder $shippingPartnerOrder
     * @return FreightBill
     */
    protected function createFreightBill(ShippingPartnerOrder $shippingPartnerOrder)
    {
        /**
         * Cập nhật mã vđ cho YCDH và đơn
         * Nếu không có mã dvvc trên file thì update mã vđ theo dvvc trên YCĐH
         */
        return FreightBill::updateOrCreate(
            [
                'freight_bill_code' => $shippingPartnerOrder->trackingNo,
                'shipping_partner_id' => $this->orderPacking->shipping_partner_id,
                'tenant_id' => $this->orderPacking->tenant_id,
                'order_packing_id' => $this->orderPacking->id,
            ],
            [
                'order_id' => $this->orderPacking->order_id,
                'status' => FreightBill::STATUS_WAIT_FOR_PICK_UP,
                'snapshots' => Service::orderPacking()->makeSnapshots($this->orderPacking),
                'cod_total_amount' => $this->orderPacking->order->cod
            ]
        );
    }
}
