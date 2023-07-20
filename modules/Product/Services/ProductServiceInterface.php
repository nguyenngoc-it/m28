<?php

namespace Modules\Product\Services;

use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\Sku;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\Stock\Models\Stock;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\Product\Models\SkuCombo;
use Modules\Store\Models\Store;

interface ProductServiceInterface
{
    /**
     * Khởi tạo đối tượng query product
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Khởi tạo đối tượng query sku
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function skuQuery(array $filter);

    /**
     * Khởi tạo đối tượng query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function productPriceQuery(array $filter);

    /**
     * Get list products
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listProduct(array $filter, User $user);

    /**
     * Get list skus
     *
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listSKUs(array $filter, User $user);

    /**
     * Lấy danh sách sku của seller (bao gồm sku do seller tạo ra và sku hệ thống được gán cho seller)
     *
     * @param array $filter
     * @param User $user
     * @return Builder|LengthAwarePaginator|object
     */
    public function listSellerSKUs(array $filter, User $user);

    /**
     * Lấy danh sách bảng giá sản phẩm
     *
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listProductPrices(array $filter, User $user);

    /**
     * Lấy danh sách product của seller (bao gồm product do seller tạo ra và product hệ thống được gán cho seller)
     *
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listSellerProducts(array $filter, User $user);


    /**
     * Import SKUs from file
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importSKUs(Tenant $tenant, $filePath, User $creator);

    /**
     * @param UploadedFile $file
     * @return string
     * @throws Exception
     */
    public function getRealPathFile(UploadedFile $file);

    /**
     * Import price SKU from file
     *
     * @param User $user
     * @param string $file
     */
    public function importPrice(User $user, $file);

    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return Sku
     */
    public function createSKU(Product $product, array $input, User $user);

    /**
     * @param Product $product
     * @param array $dataSku
     * @param User $user
     * @return Sku|mixed
     */
    public function createSellerSKU(Product $product, array $dataSku, User $user);

    /**
     * @param Sku $sku
     * @param array $input
     * @param User $creator
     * @return mixed
     */
    public function updateSKU(Sku $sku, array $input, User $creator);

    /**
     * @param array $input
     * @param User $creator
     * @return void
     */
    public function updateListSku(array $input, User $creator);

    /**
     * Áp dụng logic tự động cập nhật đơn giá dịch vụ cho Product
     *
     * @param Sku $sku
     * @param Service $service
     * @param User|null $user
     * @param bool $autoSaveServicePrice
     * @return ServicePrice|null
     */
    public function autoGetSkuServicePrice(Sku $sku, Service $service, User $user = null, bool $autoSaveServicePrice = true): ?ServicePrice;

    /**
     * @param Product $product
     * @param array $getSkuIds
     * @param User $user
     * @return Collection
     */
    public function confirmWeightVolumeSKU(Product $product, array $getSkuIds, User $user);

    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return mixed
     */
    public function updateProduct(Product $product, array $input, User $user);

    /**
     * @param Product $merchantProduct
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function updateMerchantProduct(Product $merchantProduct, array $input, User $user);

    /**
     * @param Product $merchantProduct
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function merchantUpdateProductDropShip(Product $merchantProduct, array $input, User $user);

    /**
     * @param Product $product
     * @param array $input
     * @param User $user
     * @return ProductPrice
     */
    public function createProductPrice(Product $product, array $input, User $user);

    /**
     * @param ProductPrice $productPrice
     * @param User $user
     * @return ProductPrice
     */
    public function cancelProductPrice(ProductPrice $productPrice, User $user);

    /**
     * @param ProductPrice $productPrice
     * @param User $user
     * @return ProductPrice
     */
    public function activeProductPrice(ProductPrice $productPrice, User $user);

    /**
     * @param array $input
     * @param User $user
     * @param Merchant|null $merchant
     * @return Product
     */
    public function merchantCreateProduct(array $input, User $user, Merchant $merchant = null);

    /**
     * @param array $input
     * @param User $user
     * @return Product
     */
    public function merchantCreateProductDropShip(array $input, User $user);

    /**
     * @param $optionValueIds
     * @return bool
     */
    public function canDeleteOptionValue($optionValueIds);

    /**
     * @param array $skuIds
     * @return bool
     */
    public function canDeleteSkus($skuIds = []);

    /**
     * Import product from file
     *
     * @param User $creator
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public function importProducts(User $creator, $filePath);

    /**
     * Import fobiz sku code from file
     *
     * @param User $user
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function importFobizSkuCode(User $user, $file);

    /**
     * Download danh sách mã loại sản phẩm
     *
     * @param array $filter
     * @return string
     */
    public function downloadRefSkusByFilter(array $filter);

    /**
     * Import danh sách mã loại sản phẩm
     *
     * @param $file
     * @param User $user
     * @return array
     */
    public function importRefSkus(UploadedFile $file, User $user);

    /**
     * Nhập sản phẩm (sku) của 1 seller theo file
     *
     * @param UploadedFile $file
     * @param User $user
     * @return array
     */
    public function importSellerProducts(UploadedFile $file, User $user);

    /**
     * Cập nhật số lượng Sku vào stock
     * quantity (-) thì xuất kho
     * quantity (+) thì nhập kho
     *
     * @param Sku $sku
     * @param int $quantity
     * @param Stock $skuStockWarehouseArea
     * @param StockObjectInterface $object
     * @param User $user
     * @param array $payload
     * @return void
     */
    public function updateSkuStocks(Sku $sku, int $quantity, Stock $skuStockWarehouseArea, User $user, StockObjectInterface $object, array $payload = []);

    /**
     * @param Product $product
     * @return array
     */
    public function getLogs(Product $product);

    /**
     * @param SkuCombo $skuCombo
     * @return array
     */
    public function getSkuComboLogs(SkuCombo $skuCombo);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantExportSku(array $filter, User $user);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function merchantDownloadStockIO(array $filter, User $user);

    /**
     * Lấy tồn kho của 1 sản phẩm seller
     *
     * @param Product $product
     * @return Collection
     */
    public function gettingMerchantProductStocks(Product $product);

    /**
     *
     * @param Store $store
     * @param array $paramsRequest
     * @return Product
     */
    public function createProductFrom3rdPartner(Store $store, array $paramsRequest);

    /**
     * Lấy sku từ sku_id_origin của 1 store
     *
     * @param Store $store
     * @param $externalSkuCode
     * @return Sku|null
     */
    public function getSkuByStore(Store $store, $externalSkuCode);

    /**
     * @param array $input
     * @param User $user
     * @return mixed
     */
    public function createSkuCombo(array $input, User $user);

    /**
     * @param array $input
     * @param SkuCombo $skuCombo
     * @param User $user
     * @return Model|mixed
     */
    public function updateSkuCombo(SkuCombo $skuCombo, array $input, User $user);

    /**
     * @param array $input
     * @param SkuCombo $skuCombo
     * @param User $user
     * @return Model|mixed
     */
    public function uploadImages(SkuCombo $skuCombo, array $input, User $user);
}
