<?php

namespace Modules\Shopee\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class ShopeeDownloadShippingDocument
{
    /**
     * @var integer
     */
    protected $shippingPartnerId;

    /**
     * @var array
     */
    protected $freightBillCodes;

    /**
     * @var Order[]
     */
    protected $orders;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * ShopeeDownloadShippingDocument constructor.
     * @param $shippingPartnerId
     * @param $freightBillCodes
     */
    public function __construct($shippingPartnerId, $freightBillCodes)
    {
        $this->shippingPartnerId = $shippingPartnerId;
        $this->freightBillCodes  = $freightBillCodes;

        $this->logger = LogService::logger('shopee_download_shipping_document', [
            'context' => compact('shippingPartnerId', 'freightBillCodes'),
        ]);
    }

    /**
     * @return array|string
     */
    public function handle()
    {
        $freightBillOrders = FreightBill::query()->whereIn('freight_bill_code', $this->freightBillCodes)
            ->where('shipping_partner_id', $this->shippingPartnerId)
            ->get()->pluck('order_id', 'freight_bill_code')->toArray();

        if(empty($freightBillOrders)) {
            $this->logger->debug('empty freight_bill');
            return '';
        }

        $storeIds  = [];
        foreach ($this->freightBillCodes as $freightBillCode) {
            $freightBillCode = trim($freightBillCode);
            if(isset($freightBillOrders[$freightBillCode])) {
                $orderId = $freightBillOrders[$freightBillCode];
                $order   = Order::find($orderId);

                $storeIds[$order->store_id][] = ['order_sn' => $order->code, 'shipping_document_type' => 'THERMAL_AIR_WAYBILL'];
            }
        }

        if(empty($storeIds)) {
            $this->logger->debug('empty order_list');
            return '';
        }

        $waybillFiles = [];
        foreach ($storeIds as $storeId => $orderList) {
            $store = Store::find($storeId);

            $params = [
                'order_list' => $orderList
            ];
            try {
                $shippingDocumentResult = $store->shopeeApi()->getShippingDocumentResult($params);
                $this->logger->debug('getShippingDocumentResult success', [
                    'shippingPartnerId' => $this->shippingPartnerId,
                    'freightBillCodes' => $this->freightBillCodes,
                    'params' => $params,
                    'shippingDocumentResult' => $shippingDocumentResult->getData()
                ]);
                $resultList = $shippingDocumentResult->getData('response.result_list');
                if(empty($resultList)) continue;
            } catch (\Exception $exception) {
                $this->logger->debug('getShippingDocumentResult error '.$exception->getMessage(), [
                    'shippingPartnerId' => $this->shippingPartnerId,
                    'freightBillCodes' => $this->freightBillCodes,
                    'params' => $params,
                    'errors' => $exception->getLine() .' - '.$exception->getFile()
                ]);
                continue;
            }


            $orderCodes = [];
            foreach ($resultList as $list) {
                if($list['status'] == 'READY') {
                    $orderCodes[] = ['order_sn' => $list['order_sn']];
                }
            }

            if(empty($orderCodes)) continue;

            $params = [
                'shipping_document_type' => 'THERMAL_AIR_WAYBILL',
                'order_list' => $orderCodes
            ];
            $waybillFile = null;
            $waybillFilePath = '';
            try {
                $shippingDocument = $store->shopeeApi()->downloadShippingDocument($params);
                $error = $shippingDocument->getData('error');
                if(empty($error)) {
                    $waybillFile = $shippingDocument->getBody();
                    $nameFile = date('Y-m-d').'_'.time().'_'.Helper::quickRandom(10);
                    $filePath = 'stamps/' . $nameFile . '.pdf';
                    $uploaded = $store->tenant->storage()->put($filePath , $waybillFile, 'public');
                    if ($uploaded) {
                        $waybillFilePath = $store->tenant->storage()->url($filePath);
                    }
                }

                $this->logger->debug('downloadShippingDocument success', [
                    'shippingPartnerId' => $this->shippingPartnerId,
                    'freightBillCodes' => $this->freightBillCodes,
                    'params' => $params,
                    'error' => $error,
                    'message' => $shippingDocument->getData('message'),
                    'waybillFilePath' => $waybillFilePath
                ]);
            } catch (\Exception $exception) {
                $this->logger->debug('downloadShippingDocument error '.$exception->getMessage(), [
                    'shippingPartnerId' => $this->shippingPartnerId,
                    'freightBillCodes' => $this->freightBillCodes,
                    'params' => $params,
                    'errors' => $exception->getLine() .' - '.$exception->getFile()
                ]);
                continue;
            }

            if(!empty($waybillFilePath)) {
                $waybillFiles[] = $waybillFilePath;
            }
        }


        return $waybillFiles;
    }
}