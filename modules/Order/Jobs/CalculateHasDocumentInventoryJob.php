<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;

class CalculateHasDocumentInventoryJob extends Job
{
    public $connection = 'redis';

    public $queue = 'order_calculate_has_document_inventory';

    /**
     * @var int
     */
    protected $orderId;

    /**
     * CalculateAmountPaidToSeller constructor
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        if ($order) {
            $documentFreightBillInventories = $order->documentFreightBillInventories;
            $hasDocumentInventory = false;
            foreach ($documentFreightBillInventories as $documentFreightBillInventory) {
                if(in_array($documentFreightBillInventory->document->status, [Document::STATUS_COMPLETED, Document::STATUS_DRAFT])) {
                    $hasDocumentInventory = true;
                    break;
                }
            }

            if($order->has_document_inventory != $hasDocumentInventory) {
                $order->has_document_inventory = $hasDocumentInventory;
                $order->save();
            }
        }
    }
}
