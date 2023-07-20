<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Gobiz\Log\LogService;
use Modules\Product\Models\SkuCombo;
use Psr\Log\LoggerInterface;

/**
 * Class tạo sản phẩm đồng bộ từ bên thứ 3
 *
 * Dữ liệu truyền vào đám bảo định dạng:
 *
 * $paramsRequest [
 * 'name'              => Product Name
 * 'price'             => Override Price
 * 'original_price'    => Base Price
 * 'code'              => Product Code
 * 'source'            => Source Create Data - Check From Model Modules\Marketplace\Services\Marketplace
 * 'product_id_origin' => Product Id From 3rdPartner Resource
 * 'sku_id_origin'     => Sku Id From 3rdPartner Resource
 * 'description'       => Description
 * 'images'            => Images
 * 'weight'            => Weight
 * "status"            => Status Product - Check From Model Modules\Product\Models\Product
 * ]
 *
 */
class CreateProductFrom3rdPartner
{

    /**
     * @var Store
     * Store Model
     */
    protected Store $store;

    /**
     * @var array
     * Array data transform
     */
    protected array $paramsTransform;

    /**
     * @var User
     * User Model
     */
    protected User $creator;

    /**
     * @var array
     * Array data from resource
     */
    protected array $paramsRequest;

    /**
     * @var LoggerInterface
     * Logger Object
     */
    protected LoggerInterface $logger;


    /**
     * __construct function
     *
     * @param Store $store
     * @param array $paramsRequest [
     * 'name'              => Product Name
     * 'price'             => Override Price
     * 'original_price'    => Base Price
     * 'code'              => Product Code
     * 'source'            => Source Create Data - Check From Model Modules\Marketplace\Services\Marketplace
     * 'product_id_origin' => Product Id From 3rdPartner Resource
     * 'sku_id_origin'     => Sku Id From 3rdPartner Resource
     * 'description'       => Description
     * 'images'            => Images
     * 'weight'            => Weight
     * "status"            => Status Product - Check From Model Modules\Product\Models\Product
     * ]
     */
    public function __construct(Store $store, array $paramsRequest)
    {
        $this->store           = $store;
        $this->creator         = $this->store->merchant->user ? $this->store->merchant->user : Service::user()->getSystemUserDefault();
        $this->paramsRequest   = $paramsRequest;
        $this->paramsTransform = $this->transformData($paramsRequest);

        $channel      = strtolower($this->paramsTransform['source']);
        $this->logger = LogService::logger("{$channel}-sync-product", [
            'context' => ['shop_id' => $this->store->id, 'store' => $this->store->id, 'item_id' => $this->paramsTransform['sku_id_origin']],
        ]);
    }

    /**
     * Handle Logic
     *
     * @return Product|null
     */
    public function handle()
    {
        $this->logger->info("Start sync item {$this->paramsTransform['sku_id_origin']}", $this->paramsRequest);
        try {
            // Tạo sản phẩm
            $productData = $this->makeProduct();
        } catch (\Exception $exception) {
            $this->logger->error("Error sync item {$this->paramsTransform['sku_id_origin']}", [
                'error' => $exception->getMessage(),
                'request' => $this->paramsRequest
            ]);

            return null;
        }

        // Gọi các Event liên quan
        if (($productData instanceof Product) && $productData->wasRecentlyCreated) {
            $this->logger->info('created');
            (new ProductCreated($productData->id))->queue();
        }

        return $productData;
    }

    /**
     * Transform data from 3rd partner resource
     * @param array $dataInput data from 3rd partner resource
     * @return array $dataTransform
     */
    protected function transformData(array $dataInput)
    {
        $creatorId  = data_get($dataInput, 'creator_id', $this->creator->id);
        $merchantId = data_get($dataInput, 'merchant_id', $this->store->merchant->id);

        $dataTransform = [
            "tenant_id"         => $this->store->merchant->tenant_id,
            "creator_id"        => $creatorId,
            "merchant_id"       => $merchantId,
            'name'              => data_get($dataInput, 'name'),
            'category_id'       => data_get($dataInput, 'category_id', 0),
            'price'             => data_get($dataInput, 'price'),
            'original_price'    => data_get($dataInput, 'original_price'),
            'code'              => data_get($dataInput, 'code'),
            'source'            => data_get($dataInput, 'source'),
            'product_id_origin' => data_get($dataInput, 'product_id_origin'),
            'sku_id_origin'     => data_get($dataInput, 'sku_id_origin'),
            'description'       => data_get($dataInput, 'description'),
            'images'            => data_get($dataInput, 'images'),
            'weight'            => data_get($dataInput, 'weight'),
            'height'            => data_get($dataInput, 'height'),
            'width'             => data_get($dataInput, 'width'),
            'length'            => data_get($dataInput, 'length'),
            'status'            => data_get($dataInput, 'status'),
            'force_update'      => data_get($dataInput, 'force_update', false),
        ];
        return $dataTransform;
    }

    /**
     * Make Common Data For Make Product Record
     *
     * @param array $transformData
     * @return array $dataCommon
     */
    protected function makeCommonData(array $transformData)
    {
        $dataCommon = [
            "source"            => data_get($transformData, 'source'),
            "code"              => data_get($transformData, 'code'),
            "product_id_origin" => data_get($transformData, 'product_id_origin'),
            "sku_id_origin"     => data_get($transformData, 'sku_id_origin'),
            "name"              => data_get($transformData, 'name'),
            "category_id"       => data_get($transformData, 'category_id', 0),
            "description"       => data_get($transformData, 'description'),
            "images"            => data_get($transformData, 'images'),
            "weight"            => data_get($transformData, 'weight'),
            "height"            => data_get($transformData, 'height'),
            "width"             => data_get($transformData, 'width'),
            "length"            => data_get($transformData, 'length'),
            "tenant_id"         => data_get($transformData, 'tenant_id'),
            "creator_id"        => data_get($transformData, 'creator_id'),
            "merchant_id"       => data_get($transformData, 'merchant_id'),
            "status"            => data_get($transformData, 'status'),
        ];

        return $dataCommon;
    }

    /**
     * Make Data Product's Relationship
     *
     * @param Product $product
     * @param Sku|null $checkSku
     * @return void
     */
    protected function makeProductRelation(Product $product, Sku $checkSku = null)
    {
        $skuCreated = $checkSku ?: $this->makeSku($product);
        $this->makeSkuRelation($skuCreated);
        $this->makeProductMerchant($product);
    }

    /**
     * Make Data Sku's Relationship
     *
     * @param Sku $sku
     * @return void
     */
    protected function makeSkuRelation(Sku $sku)
    {
        $this->makeStoreSku($sku);
        $this->makeSkuPrice($sku);
    }

    /**
     * Make Product From Data Resource
     *
     * @return Product
     */
    protected function makeProduct()
    {
        $productData = DB::transaction(function () {

            // Tạo dữ liệu chung, mặc định phải có cho việc tạo bản ghi Product
            $dataCommon = $this->makeCommonData($this->paramsTransform);

            // Kiểm tra nếu sku đồng bộ về có code trùng với code của sku combo thì không đồng bộ nữa
            $code = $dataCommon['code'];
            $skuCombo = SkuCombo::where('code', $code)->first();
            if ($skuCombo) {
                return null;
            }

            // Kiểm tra xem mã Sku đã tồn tại gán cho merchant nào chưa:
            $checkSku = Service::merchant()->getMerchantSkuByCode($this->paramsTransform['code'], $this->store->merchant);

            if ($this->paramsTransform['force_update']) {
                $checkSku = null;
            }

            if ($checkSku instanceof Sku) {
                $product = $checkSku->product;
            } else {
                $query   = [
                    'merchant_id'   => $this->paramsTransform['merchant_id'],
                    'sku_id_origin' => $this->paramsTransform['sku_id_origin']
                ];
                $product = Product::updateOrCreate($query, $dataCommon);
            }
            // Tạo các bản ghi quan hệ
            $this->makeProductRelation($product, $checkSku);

            return $product;
        });

        return $productData;
    }

    /**
     * Make Product Sku Record
     *
     * @param Product $product
     * @return Sku
     */
    protected function makeSku(Product $product)
    {
        $retailPrice = $this->paramsTransform['price'];
        if (!$retailPrice) {
            $retailPrice = $this->paramsTransform['original_price'];
        }

        $query = [
            'code'          => $product->code,
            'merchant_id'   => $product->merchant_id,
            'tenant_id'     => $product->tenant_id
        ];

        if (!$product->wasRecentlyCreated) {
            $query = [
                'product_id'    => $product->id,
                'sku_id_origin' => $product->sku_id_origin,
                'merchant_id'   => $product->merchant_id,
                'tenant_id'     => $product->tenant_id
            ];
        }

        $data = [
            'product_id'        => $product->id,
            'creator_id'        => $product->creator_id,
            'code'              => $product->code,
            'status'            => $product->status,
            'product_id_origin' => $product->product_id_origin,
            'sku_id_origin'     => $product->sku_id_origin,
            'name'              => $product->name,
            'images'            => $product->images,
            'retail_price'      => $retailPrice,
            'weight'            => $product->weight,
            'height'            => $product->height,
            'width'             => $product->width,
            'length'            => $product->length
        ];

        $sku = Sku::updateOrCreate($query, $data);

        return $sku;
    }

    /**
     * Make Product Merchant Record
     *
     * @param Product $product
     * @return void
     */
    public function makeProductMerchant(Product $product)
    {
        ProductMerchant::firstOrCreate([
            'product_id'  => $product->id,
            'merchant_id' => $this->paramsTransform['merchant_id']
        ]);
    }

    /**
     * Make Store Sku Record
     *
     * @param Sku $sku
     * @return void
     */
    protected function makeStoreSku(Sku $sku)
    {
        $query        = [
            'sku_id'    => $sku->id,
            'tenant_id' => $sku->product->tenant_id,
            'sku_id_origin' => $this->paramsTransform['sku_id_origin'],
        ];
        $data         = [
            'product_id_origin'    => $this->paramsTransform['product_id_origin'],
            'marketplace_code'     => $this->store->marketplace_code,
            'marketplace_store_id' => $this->store->marketplace_store_id,
            'code'                 => $sku->code,
            'product_id'           => $sku->product->id,
        ];

        $this->store->storeSkus()->updateOrCreate($query, $data);
    }

    /**
     * Make Sku Price Record
     *
     * @param Sku $sku
     * @return void
     */
    public function makeSkuPrice(Sku $sku)
    {
        $retailPrice = $this->paramsTransform['price'];
        if (!$retailPrice) {
            $retailPrice = $this->paramsTransform['original_price'];
        }

        $sku->prices()->updateOrCreate(['merchant_id' => $sku->merchant_id], ['retail_price' => $retailPrice]);
    }
}
