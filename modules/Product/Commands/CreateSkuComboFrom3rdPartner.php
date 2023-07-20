<?php

namespace Modules\Product\Commands;

use Carbon\Carbon;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Models\OrderSkuCombo;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuComboSku;
use Modules\Shopee\Jobs\SyncShopeeFreightBillJob;
use Modules\Store\Models\Store;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Service;
use Illuminate\Support\Facades\DB;
use Modules\KiotViet\Jobs\SyncKiotVietFreightBillJob;
use Modules\Lazada\Jobs\SyncLazadaFreightBillJob;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;
use Modules\Product\Resource\Data3rdResource;
use Modules\Order\Validators\CreateOrderFrom3rdPartnerValidator;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShopBaseUs\Jobs\SyncShopBaseUsFreightBillJob;
use Modules\Sapo\Jobs\SyncSapoFreightBillJob;
use Modules\Store\Models\StoreSku;
use Modules\TikTokShop\Jobs\SyncTikTokShopFreightBillJob;
use Psr\Log\LoggerInterface;
use Modules\User\Models\User;

/**
 * Class tạo đơn hàng đồng bộ từ bên thứ 3
 *
 */
class CreateSkuComboFrom3rdPartner
{
    /**
     * @var Store
     * Store Model
     */
    protected $store;

    /**
     * @var array
     * Array data transform
     */
    protected $paramsTransform;

    /**
     * @var User
     * User Model
     */
    protected $creator;

    /**
     * @var Data3rdResource
     * Data from resource
     */
    protected $dataResource;

    /**
     * @var LoggerInterface
     * Logger Object
     */
    protected $logger;

    public function __construct(Store $store, Data3rdResource $dataResource)
    {
        $this->store           = $store;
        $this->creator         = $this->store->merchant->user ? $this->store->merchant->user : Service::user()->getSystemUserDefault();
        $this->dataResource    = $dataResource;
        $this->paramsTransform = $this->transformData($dataResource);
        $channel               = strtolower($this->paramsTransform['marketplace_code']);

        // Lưu log đồng bộ theo resource
        $this->logger = LogService::logger("{$channel}-create-sku-combo", [
            'context' => ['shop_id' => $this->store->id, 'store' => $this->store->id],
        ]);
    }

    /**
     * Handle Logic
     *
     * @return Order
     * @throws WorkflowException
     */
    public function handle()
    {
        /**
         * Tạo Sku Combo
         */
        $this->logger->info('create-sku-combo-inputs', $this->paramsTransform);
        $skuCombo = $this->makeSkuCombo();

        return $skuCombo;
    }

    /**
     * Transform data from 3rd partner resource
     * @param Data3rdResource $dataResource data from 3rd partner resource
     * @return array $dataTransform
     */
    protected function transformData(Data3rdResource $dataResource)
    {
        $merchantId  = $dataResource->merchant_id ? $dataResource->merchant_id : $this->store->merchant->id;

        $dataTransform = [
            'code'             => $dataResource->code,
            'name'             => $dataResource->name,
            'category_id'      => $dataResource->category_id,
            'merchant_id'      => $merchantId,
            'source'           => $dataResource->source,
            'price'            => $dataResource->price,
            'marketplace_code' => $dataResource->marketplace_code,
            'items'            => $dataResource->items,
        ];

        return $dataTransform;
    }

    /**
     * Make Common Data For Make Order Record
     * @param array $transformData
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function makeCommonData(array $transformData)
    {
        // $this->validateDataTransform($transformData);

        $merchantId = data_get($transformData, 'merchant_id', 0);
        $tenantId   = 0;

        $merchant = Merchant::find($merchantId);
        if ($merchant) {
            $tenantId = $merchant->tenant_id;
        }

        $dataCommon = [
            'code'        => data_get($transformData, 'code'),
            'name'        => data_get($transformData, 'name'),
            'category_id' => data_get($transformData, 'category_id', 0),
            'merchant_id' => $merchantId,
            'tenant_id'   => $tenantId,
            'status'      => SkuCombo::STATUS_ON_SELL,
            'source'      => data_get($transformData, 'source'),
            'price'       => data_get($transformData, 'price'),
        ];

        return $dataCommon;
    }

    /**
     * Make Order From 3rd Resource
     *
     * @return SkuCombo $skuCombo
     */
    protected function makeSkuCombo()
    {
        return DB::transaction(function () {

            // Tạo dữ liệu chung, mặc định phải có cho việc tạo bản ghi Product
            $dataCommon = $this->makeCommonData($this->paramsTransform);

            $query = [
                'code'        => $dataCommon['code'],
                'merchant_id' => $dataCommon['merchant_id']
            ];

            $this->logger->info('data-sku-combo-insert', $dataCommon);

            // Tạo bản ghi Order
            $skuCombo = SkuCombo::updateOrCreate($query, $dataCommon);

            // Tạo các bản ghi quan hệ
            $this->makeSkuComboRelation($skuCombo);

            return $skuCombo;
        });
    }

    /**
     * Make Data Order's Relationship
     *
     * @param SkuCombo $skuCombo
     * @return void
     */
    protected function makeSkuComboRelation(SkuCombo $skuCombo)
    {
        $this->makeSkus($skuCombo);
    }

    /**
     * Make Order Sku Record
     *
     * @param SkuCombo $skuCombo
     * @return void
     */
    protected function makeSkus(SkuCombo $skuCombo)
    {
        // Danh sách Sku của Order
        $items = $this->paramsTransform['items'];
        /**
         * $item [
         *    'id'              => Sku Id
         *    'code'            => Sku Code,
         *    'discount_amount' => Order Sku Item Discount Amount,
         *    'price'           => Base Price Of Sku Item,
         *    'quantity'        => Quantity Of Sku Item,
         * ]
         */
        foreach ($items as $item) {

            $itemId             = data_get($item, 'id');
            $itemCode           = data_get($item, 'code');
            $itemQuantity       = data_get($item, 'quantity');

            // Get Sku data
            $sku = null;
            if ($itemId) {
                $sku = Sku::find($itemId);
            } else {
                $storeSku = StoreSku::select('store_skus.*')
                                        ->join('skus', 'store_skus.sku_id', 'skus.id')
                                        ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                                        ->where('store_skus.code', $itemCode)
                                        ->where(function($query) {
                                            return $query->where('skus.merchant_id', $this->paramsTransform['merchant_id'])
                                                         ->orWhere('product_merchants.merchant_id', $this->paramsTransform['merchant_id']);
                                        })
                                        ->first();
                if ($storeSku instanceof StoreSku) {
                    $sku = $storeSku->sku;
                }
            }

            if ($sku instanceof Sku) {
                $query = [
                    'sku_combo_id' => $skuCombo->id,
                    'sku_id'       => $sku->id,
                ];

                $dataCreate = [
                    'quantity' => $itemQuantity
                ];

                SkuComboSku::updateOrcreate($query, $dataCreate);
            }
        }
    }
}
