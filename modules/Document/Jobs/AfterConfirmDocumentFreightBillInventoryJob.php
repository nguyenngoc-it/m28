<?php

namespace Modules\Document\Jobs;

use App\Base\Job;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class AfterConfirmDocumentFreightBillInventoryJob extends Job
{
    public $connection = 'redis';
    public $queue = 'document_freight_bill_inventory';

    /**
     * @var integer
     */
    protected $documentId = 0;
    /**
     * @var User
     */
    protected $user;

    /**
     * AfterConfirmDocumentFreightBillInventoryJob constructor.
     * @param $documentId
     * @param $userId
     */
    public function __construct($documentId, $userId)
    {
        $this->documentId = $documentId;
        $this->user       = User::find($userId);
    }

    public function handle()
    {
        $document = Document::find($this->documentId);
        if (!$document instanceof Document) return;
        $this->changeOrderAmount($document);
        $this->changeFreightBillAmount($document);

    }

    /**
     * @param Document $document
     */
    protected function changeOrderAmount(Document $document)
    {
        /** @var DocumentFreightBillInventory $freightBillInventory */
        foreach ($document->documentFreightBillInventories as $freightBillInventory) {
            $order = $freightBillInventory->order;
            if (!$order instanceof Order) {
                continue;
            }
            $order->paid_amount += $freightBillInventory->cod_paid_amount;
            if (!$order->dropship) { //đơn dropship thì đã được tính phí VC theo bảng giá rồi
                $order->shipping_amount += $freightBillInventory->shipping_amount;
            }
            $order->cod_fee_amount += $freightBillInventory->cod_fee_amount;
            $order->other_fee      += $freightBillInventory->other_fee;
            if (empty($order->extent_service_amount)) {
                $order->extent_service_amount = $freightBillInventory->extent_amount;
            }
            $order->has_document_inventory = true;

            $order->save();
            (new OrderShippingFinancialStatusChanged($order, Order::SFS_COLLECTED, $order->shipping_financial_status, $this->user))->queue();
        }
    }

    /**
     * @param Document $document
     */
    protected function changeFreightBillAmount(Document $document)
    {
        /** @var DocumentFreightBillInventory $freightBillInventory */
        foreach ($document->documentFreightBillInventories as $freightBillInventory) {
            $freightBill = $freightBillInventory->freightBill;
            if (!$freightBill instanceof FreightBill) {
                continue;
            }
            $order = $freightBill->order;

            $freightBill->cod_paid_amount += $freightBillInventory->cod_paid_amount;

            if ($order->dropship) { //đơn dropship thì đã được tính phí VC theo bảng giá rồi
                $freightBill->shipping_amount = $order->shipping_amount;
            } else {
                $freightBill->shipping_amount += $freightBillInventory->shipping_amount;
            }

            $freightBill->cod_fee_amount += $freightBillInventory->cod_fee_amount;
            $freightBill->other_fee      += $freightBillInventory->other_fee;

            $freightBill->save();
        }
    }
}
