<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Psr\Log\LoggerInterface;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;

class ShopeeCreateShippingDocumentJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';


    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @var int
     */
    protected $orderId;

    /**
     * ShopeeCreateShippingDocumentJob constructor.
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $this->logger = LogService::logger('shopee_shipping_document');

        $order = Order::find($this->orderId);
        $freightBill = $order->freightBills()->first();
        if(!$freightBill instanceof FreightBill) {
            $this->logger->info('not found FreightBill '.$order->code);
            return;
        }

        $params = [
            'order_list' => [
                [
                    'order_sn' => $order->code,
                    'shipping_document_type' => 'THERMAL_AIR_WAYBILL',
                    'tracking_number' => $freightBill->freight_bill_code
                ]
            ]
        ];


        try {
            $shippingDocument = $order->store->shopeeApi()->createShippingDocument($params)->getData();
            $this->logger->info('createShippingDocument success '.$order->code, compact('shippingDocument', 'params'));

        } catch (\Exception $exception) {
            $this->logger->info('createShippingDocument error '.$order->code .' '.$exception->getMessage() ,[
                'params' => $params,
                'errors' => $exception->getLine() .' - '.$exception->getFile()
            ]);
        }

    }
}
