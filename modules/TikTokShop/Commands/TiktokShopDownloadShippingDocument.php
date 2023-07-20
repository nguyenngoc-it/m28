<?php

namespace Modules\TikTokShop\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Psr\Log\LoggerInterface;

class TiktokShopDownloadShippingDocument
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
     * TikTokShopDownloadShippingDocument constructor.
     * @param $shippingPartnerId
     * @param $freightBillCodes
     */
    public function __construct($shippingPartnerId, $freightBillCodes)
    {
        $this->shippingPartnerId = $shippingPartnerId;
        $this->freightBillCodes  = $freightBillCodes;

        $this->logger = LogService::logger('tiktokshop-download-shipping-document', [
            'context' => compact('shippingPartnerId', 'freightBillCodes'),
        ]);
    }

    /**
     * @return array
     */
    public function handle()
    {
        $tiktokShopApi = Service::tikTokShop()->api(); 

        $responseUrls = [];
        if ($this->freightBillCodes) {
            $orders = Order::where('marketplace_code', Marketplace::CODE_TIKTOKSHOP)
                           ->where('shipping_partner_id', $this->shippingPartnerId)
                           ->whereIn('freight_bill', $this->freightBillCodes)
                           ->get();
            if ($orders) {
                foreach ($orders as $order) {
                    $paramsRequest = [
                        'access_token'  => $order->store->getSetting('access_token'),
                        'shop_id'       => $order->store->marketplace_store_id,
                        'order_id'      => $order->code,
                    ];
                    // dd($paramsRequest);
                    $shippingDocument    = $tiktokShopApi->getShippingDocument($paramsRequest)->getData('data');
                    $urlShippingDocument = data_get($shippingDocument, 'doc_url', '');
                    
                    if ($urlShippingDocument) {

                        $filePath = "shipping_document/tenant_{$order->tenant->id}/{$order->marketplace_code}_{$order->code}.pdf";

                        if (App::environment('local')) {
                            if (Storage::put($filePath, file_get_contents($urlShippingDocument))) {
                                $responseUrls[] = Storage::url($filePath);
                            }
                        } else {
                            $uploaded = $order->store->tenant->storage()->put($filePath , file_get_contents($urlShippingDocument), 'public');
                            if ($uploaded) {
                                $responseUrls[] = $order->store->tenant->storage()->url($filePath);
                            }   
                        }
                    }
                }
            }
        }

        return $responseUrls;
    }
}