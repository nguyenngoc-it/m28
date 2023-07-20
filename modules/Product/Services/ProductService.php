<?php

namespace Modules\Product\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\OrderSku;
use Modules\Product\Commands\ActiveProductPrice;
use Modules\Product\Commands\AutoUpdateSkuServicePrice;
use Modules\Product\Commands\CancelProductPrice;
use Modules\Product\Commands\CreateProductPrice;
use Modules\Product\Commands\CreateSellerSKU;
use Modules\Product\Commands\CreateSKU;
use Modules\Product\Commands\CreateSkuCombo;
use Modules\Product\Commands\DownloadRefSkuByFilter;
use Modules\Product\Commands\ImportPrice;
use Modules\Product\Commands\ImportRefSku;
use Modules\Product\Commands\ImportSellerProduct;
use Modules\Product\Commands\ImportSKUs;
use Modules\Product\Commands\ListProduct;
use Modules\Product\Commands\ListProductPrices;
use Modules\Product\Commands\ListSellerProduct;
use Modules\Product\Commands\ListSellerSKUs;
use Modules\Product\Commands\ListSKUs;
use Modules\Product\Commands\MerchantCreateProduct;
use Modules\Product\Commands\MerchantCreateProductDropShip;
use Modules\Product\Commands\MerchantDownloadStockIO;
use Modules\Product\Commands\MerchantExportSku;
use Modules\Product\Commands\MerchantUpdateProductDropShip;
use Modules\Product\Commands\UpdateListSku;
use Modules\Product\Commands\UpdateMerchantProduct;
use Modules\Product\Commands\UpdateProduct;
use Modules\Product\Commands\UpdateSKU;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuOptionValue;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\Stock\Models\Stock;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Store\Models\StoreSku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Product\Commands\ImportFobizSKUCode;
use Modules\Product\Commands\ImportProducts;
use Gobiz\Activity\ActivityService;
use Gobiz\Transformer\TransformerService;
use Modules\Product\Commands\CreateProductFrom3rdPartner;
use Modules\Product\Commands\UpdateSkuCombo;
use Modules\Product\Commands\UploadImageSkuCombo;
use Modules\Store\Models\Store;

class ProductService implements ProductServiceInterface
{
    /**
     * Khởi tạo đối tượng query product
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new ProductQuery())->query($filter);
    }

    /**
     * Khởi tạo đối tượng query sku
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function skuQuery(array $filter)
    {
        return (new SKUQuery())->query($filter);
    }

    /**
     * Khởi tạo đối tượng query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function productPriceQuery(array $filter)
    {
        return (new ProductPriceQuery())->query($filter);
    }

    /**
     * Get list products
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listProduct(array $filter, User $user)
    {
        return (new ListProduct($filter, $user))->handle();
    }

    /**
     * Get list skus
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listSKUs(array $filter, User $user)
    {
        return (new ListSKUs($filter, $user))->handle();
    }


    /**
     * Lấy danh sách bảng giá sản phẩm
     *
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listProductPrices(array $filter, User $user)
    {
        return (new ListProductPrices($filter, $user))->handle();
    }

    /**
     * Lấy danh sách sku của seller (bao gồm sku do seller tạo ra và sku hệ thống được gán cho seller)
     *
     * @param array $filter
     * @param User $user
     * @return Builder|LengthAwarePaginator|object
     */
    public function listSellerSKUs(array $filter, User $user)
    {
        return (new ListSellerSKUs($filter, $user))->handle();
    }

    /**
     * Lấy danh sách product của seller (bao gồm product do seller tạo ra và product hệ thống được gán cho seller)
     *
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listSellerProducts(array $filter, User $user)
    {
        return (new ListSellerProduct($filter, $user))->handle();
    }

    /**
     * Import SKUs from file
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importSKUs(Tenant $tenant, $filePath, User $creator)
    {
        return (new ImportSKUs($tenant, $filePath, $creator))->handle();
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws Exception
     */
    public function getRealPathFile(UploadedFile $file)
    {
        $ext      = $file->getClientOriginalExtension();
        $fileName = Str::uuid();

        return $file->move('/tmp', $fileName . '.' . $ext)->getRealPath();
    }

    /**
     * @param User $user
     * @param string $file
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function importPrice(User $user, $file)
    {
        return (new ImportPrice($user, $file))->handle();
    }

    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return Sku
     */
    public function createSKU(Product $product, array $input, User $user)
    {
        return (new CreateSKU($product, $input, $user))->handle();
    }

    /**
     * @param Product $product
     * @param array $dataSku
     * @param User $user
     * @return Sku|mixed
     */
    public function createSellerSKU(Product $product, array $dataSku, User $user)
    {
        return (new CreateSellerSKU($product, $dataSku, $user))->handle();
    }

    /**
     * @param Sku $sku
     * @param array $input
     * @param User $creator
     * @return mixed|Sku|null
     */
    public function updateSKU(Sku $sku, array $input, User $creator)
    {
        return (new UpdateSKU($sku, $input, $creator))->handle();
    }

    /**
     * @param array $input
     * @param User $creator
     * @return void
     */
    public function updateListSku(array $input, User $creator)
    {
        (new UpdateListSku($input, $creator))->handle();
    }

    /**
     * Áp dụng logic tự động cập nhật đơn giá dịch vụ cho Sku
     *
     * @param Sku $sku
     * @param Service $service
     * @param User|null $user
     * @param bool $autoSaveServicePrice
     * @return ServicePrice|null
     */
    public function autoGetSkuServicePrice(Sku $sku, Service $service, User $user = null, bool $autoSaveServicePrice = true): ?ServicePrice
    {
        return (new AutoUpdateSkuServicePrice($sku, $service, $user, $autoSaveServicePrice))->handle();
    }

    /**
     * @param Product $product
     * @param array $skuIds
     * @param User $user
     * @return void
     */
    public function confirmWeightVolumeSKU(Product $product, array $skuIds, User $user)
    {
        $confirmSkus = $product->skus()->whereIn('id', $skuIds);
        $product->skus()->update(['confirm_weight_volume' => false]);
        $confirmSkus->update(['confirm_weight_volume' => true]);
        $product->logActivity(ProductEvent::CONFIRM_WEIGHT_VOLUME_FOR_SKUS, $user, ['skus' => $confirmSkus->get()->pluck('code')->all()]);
    }


    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return mixed|Product|null
     * @throws Exception
     */
    public function updateProduct(Product $product, array $input, User $user)
    {
        return (new UpdateProduct($product, $input, $user))->handle();
    }

    /**
     * @param Product $merchantProduct
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function updateMerchantProduct(Product $merchantProduct, array $input, User $user)
    {
        return (new UpdateMerchantProduct($merchantProduct, $input, $user))->handle();
    }

    /**
     * @param Product $merchantProduct
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function merchantUpdateProductDropShip(Product $merchantProduct, array $input, User $user)
    {
        return (new MerchantUpdateProductDropShip($merchantProduct, $input, $user))->handle();
    }

    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return ProductPrice
     */
    public function createProductPrice(Product $product, array $input, User $user)
    {
        return (new CreateProductPrice($product, $input, $user))->handle();
    }

    /**
     * @param ProductPrice $productPrice
     * @param User $user
     * @return ProductPrice
     */
    public function cancelProductPrice(ProductPrice $productPrice, User $user)
    {
        return (new CancelProductPrice($productPrice, $user))->handle();
    }

    /**
     * @param ProductPrice $productPrice
     * @param User $user
     * @return ProductPrice
     */
    public function activeProductPrice(ProductPrice $productPrice, User $user)
    {
        return (new ActiveProductPrice($productPrice, $user))->handle();
    }

    /**
     * @param array $input
     * @param User $user
     * @param Merchant|null $merchant
     * @return Product
     */
    public function merchantCreateProduct(array $input, User $user, Merchant $merchant = null)
    {
        $product              = new Product();
        $product->creator_id  = $user->id;
        $product->tenant_id   = $user->tenant_id;
        $product->merchant_id = $merchant ? $merchant->id : $user->merchant->id;
        $product->status      = Product::STATUS_ON_SELL;

        return (new MerchantCreateProduct($product, $input, $user, $merchant))->handle();
    }

    /**
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function merchantCreateProductDropShip(array $input, User $user)
    {
        $product              = new Product();
        $product->creator_id  = $user->id;
        $product->tenant_id   = $user->tenant_id;
        $product->merchant_id = $user->merchant->id;
        $product->status      = Product::STATUS_NEW;
        $product->dropship    = true;

        return (new MerchantCreateProductDropShip($product, $input, $user))->handle();
    }

    /**
     * @param $optionValueIds
     * @return bool
     */
    public function canDeleteOptionValue($optionValueIds)
    {
        if (empty($optionValueIds)) return true;

        $query = SkuOptionValue::query();
        if (is_array($optionValueIds)) {
            $query->whereIn('product_option_value_id', $optionValueIds);
        } else {
            $query->where('product_option_value_id', $optionValueIds);
        }

        $skuIds = $query->pluck('sku_id')->toArray();
        if (!$this->canDeleteSkus($skuIds)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $skuIds
     * @return bool
     */
    public function canDeleteSkus($skuIds = [])
    {
        if (!empty($skuIds)) {
            $skuIds = array_unique($skuIds);

            if (OrderSku::query()->whereIn('sku_id', $skuIds)->count() > 0) {
                return false;
            }

            if (Stock::query()->whereIn('sku_id', $skuIds)->count() > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Import product from file
     *
     * @param User $creator
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public function importProducts(User $creator, $filePath)
    {
        return (new ImportProducts($creator, $filePath))->handle();
    }

    /**
     * Import fobiz sku code from file
     *
     * @param User $user
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function importFobizSkuCode(User $user, $file)
    {
        return (new ImportFobizSKUCode($user, $file))->handle();
    }

    /**
     * Download danh sách mã loại sản phẩm
     *
     * @param array $filter
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadRefSkusByFilter(array $filter)
    {
        return (new DownloadRefSkuByFilter($filter))->handle();
    }

    /**
     * Import danh sách mã loại sản phẩm
     *
     * @param UploadedFile $file
     * @param User $user
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function importRefSkus(UploadedFile $file, User $user)
    {
        return (new ImportRefSku($file, $user))->handle();
    }

    /**
     * Nhập sản phẩm (sku) của 1 seller theo file
     *
     * @param UploadedFile $file
     * @param User $user
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function importSellerProducts(UploadedFile $file, User $user)
    {
        return (new ImportSellerProduct($file, $user))->handle();
    }

    /**
     * Cập nhật số lượng Sku vào stock
     * quantity (-) thì xuất kho
     * quantity (+) thì nhập kho
     *
     * @param Sku $sku
     * @param int $quantity
     * @param Stock $skuStockWarehouseArea
     * @param User $user
     * @param StockObjectInterface $object
     * @param array $payload
     * @return void
     */
    public function updateSkuStocks(Sku $sku, int $quantity, Stock $skuStockWarehouseArea, User $user, StockObjectInterface $object, array $payload = [])
    {
        $actionStock = $quantity < 0 ? Stock::ACTION_EXPORT : Stock::ACTION_IMPORT;
        $quantity    = abs($quantity);
        $skuStockWarehouseArea->do($actionStock, $quantity, $user)
            ->with($payload)
            ->for($object)
            ->run();
    }


    /**
     * @param Product $product
     * @return array
     */
    public function getLogs(Product $product)
    {
        $logs     = ActivityService::logger()->get('product', (int)$product->id);
        $creators = User::query()->whereIn('id', array_map(function ($log) {
            return $log['creator']['id'];
        }, $logs))->get()->all();

        $logs = array_map(function ($log) use ($creators) {
            $creatorIndex = array_search($log['creator']['id'], array_column($creators, 'id'));
            $creator      = $creators[$creatorIndex];
            $created_at   = $log['created_at']->format('Y-m-d H:i:s');
            return array_merge(TransformerService::transform($log), ['creator' => $creator, 'created_at' => $created_at]);
        }, $logs);

        return $logs;
    }

    /**
     * @param SkuCombo $skuCombo
     * @return array
     */
    public function getSkuComboLogs(SkuCombo $skuCombo)
    {
        $logs     = ActivityService::logger()->get('sku_combo', (int)$skuCombo->id);
        $creators = User::query()->whereIn('id', array_map(function ($log) {
            return $log['creator']['id'];
        }, $logs))->get()->all();

        $logs = array_map(function ($log) use ($creators) {
            $creatorIndex = array_search($log['creator']['id'], array_column($creators, 'id'));
            $creator      = $creators[$creatorIndex];
            $created_at   = $log['created_at']->format('Y-m-d H:i:s');
            return array_merge(TransformerService::transform($log), ['creator' => $creator, 'created_at' => $created_at]);
        }, $logs);

        return $logs;
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantExportSku(array $filter, User $user)
    {
        return (new MerchantExportSku($filter, $user))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantDownloadStockIO(array $filter, User $user)
    {
        return (new MerchantDownloadStockIO($filter, $user))->handle();
    }

    /**
     * Lấy tồn kho của 1 sản phẩm seller
     *
     * @param Product $product
     * @return Collection
     */
    public function gettingMerchantProductStocks(Product $product)
    {
        /** @var Sku $sku */
        $sku = $product->skus->first();
        return $sku->stocks;
    }

    /**
     *
     * @param Store $store
     * @param array $paramsRequest
     * @return Product
     */
    public function createProductFrom3rdPartner(Store $store, array $paramsRequest)
    {
        return (new CreateProductFrom3rdPartner($store, $paramsRequest))->handle();
    }

    /**
     * Lấy sku từ sku_id_origin của 1 store
     *
     * @param Store $store
     * @param $externalSkuCode
     * @return Sku|null
     */
    public function getSkuByStore(Store $store, $externalSkuCode)
    {
        /** @var StoreSku|null $storeSku */
        $storeSku = $store->storeSkus()->firstWhere('sku_id_origin', $externalSkuCode);
        return ($storeSku) ? $storeSku->sku : null;
    }

    /**
     * @param array $input
     * @param User $user
     * @return Model|mixed
     */
    public function createSkuCombo(array $input, User $user)
    {
        $skuCombo              = new SkuCombo();
        $skuCombo->merchant_id = $user->merchant->id;
        $skuCombo->tenant_id   = $user->tenant->id;
        $skuCombo->status      = SkuCombo::STATUS_ON_SELL;
        return (new CreateSkuCombo($skuCombo, $input, $user))->handle();
    }

    /**
     * @param array $input
     * @param SkuCombo $skuCombo
     * @param User $user
     * @return Model|mixed
     */
    public function updateSkuCombo(SkuCombo $skuCombo, array $input, User $user)
    {
        return (new UpdateSkuCombo($skuCombo, $input, $user))->handle();
    }

    /**
     * @param array $input
     * @param SkuCombo $skuCombo
     * @param User $user
     * @return Model|mixed
     */
    public function uploadImages(SkuCombo $skuCombo, array $input, User $user)
    {
        return (new UploadImageSkuCombo($skuCombo, $input, $user))->handle();
    }
}
