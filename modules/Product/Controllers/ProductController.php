<?php /** @noinspection ALL */

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Modules\Auth\Services\Permission;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Transformers\ProductDetailTransformer;
use Modules\Product\Transformers\ProductListItemTransformer;
use Modules\Product\Validators\ConfirmWeightVolumeSKUValidator;
use Modules\Product\Validators\UpdateProductValidator;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    /**
     * Tạo filter để query product
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'id',
            'code',
            'name',
            'status',
            'merchant_id',
            'category_id',
            'supplier_id',
            'unit_id',
            'ubox_product_code',
            'created_at',
            'page',
            'per_page',
            'sort',
            'sortBy',
        ];
        $filter              = $this->request()->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        $filter['dropship']  = false;
        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $filter['merchant_ids'] = $this->user->merchants->pluck('id')->all();
        }
        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $filter['supplier_id'] = $this->user->suppliers->pluck('id')->all();
        }
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();

        $results = Service::product()->listProduct($filter, $this->user);

        return $this->response()->success([
            'products' => array_map(function (Product $product) {
                return (new ProductListItemTransformer())->transform($product);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @return BinaryFileResponse
     */
    public function downloadRefSkus()
    {
        $filter   = $this->getQueryFilter(
            [
                'ids',
                'code',
                'name',
                'status',
                'merchant_id',
                'category_id',
                'unit_id',
                'ubox_product_code',
                'created_at',
                'sort',
                'sortBy',
            ]
        );
        $pathFile = Service::product()->downloadRefSkusByFilter(empty($filter['ids']) ? $filter : ['ids' => $filter['ids']]);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     */
    public function importRefSkus()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::product()->importRefSkus($input['file'], $this->user);

        return $this->response()->success(compact('errors'));
    }


    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function detail(Product $product)
    {
        if (
            !$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT) &&
            !in_array($product->supplier_id, $this->user->suppliers->pluck('id')->toArray())
        ) {
            return $this->response()->error('INPUT_VALID', ['supplier_id' => 'invalid']);
            return;
        }

        $data                     = (new ProductDetailTransformer())->transform($product);
        $data['can_create_price'] = ($product->canCreatePrice() && $this->getAuthUser()->can(Permission::QUOTATION_CREATE));
        return $this->response()->success($data);
    }

    /**
     * @return JsonResponse
     */
    public function getCategories()
    {
        $categories = $this->getAuthUser()->tenant->categories;
        return $this->response()->success(compact('categories'));
    }

    /**
     * @return JsonResponse
     */
    public function getUnits()
    {
        $units = $this->getAuthUser()->tenant->units;
        return $this->response()->success(compact('units'));
    }


    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function update(Product $product)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(UpdateProductValidator::$acceptKeys);
        $validator = new UpdateProductValidator($input, $product);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $product = Service::product()->updateProduct($product, $input, $user);
        $product = (new ProductDetailTransformer())->transform($product);
        return $this->response()->success(compact('product'));
    }


    /**
     * @return JsonResponse
     */
    public function checkDeleteOptionValue()
    {
        $optionsValueIds = $this->request()->get('optionsValueIds');
        if (empty($optionsValueIds)) {
            return $this->response()->error('INPUT_INVALID', ['optionsValueIds' => 'required']);
        }

        if (!is_array($optionsValueIds)) {
            $optionsValueIds = array($optionsValueIds);
        }

        if (!Service::product()->canDeleteOptionValue($optionsValueIds)) {
            return $this->response()->error('INPUT_INVALID', ['delete' => false]);
        }

        return $this->response()->success();
    }

    /**
     * Vận hành tạo sản phẩm qua file
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function import()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = Service::product()->importProducts($user, $path);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     * @throws Exception
     */
    public function getMerchants(Product $product)
    {
        $userMerchantIds = $this->user->merchants->pluck('id')->toArray();
        $merchantQuery   = $product->merchants();
        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $merchantQuery->whereIn('merchants.id', $userMerchantIds);
        }
        $merchants = $merchantQuery->get()
            ->map(function (Merchant $merchant) {
                $location = $merchant->location;
                $currency = $location instanceof Location && $location->currency_id ? $location->currency : null;
                return [
                    'merchant' => $merchant,
                    'currency' => $currency
                ];
            });

        return $this->response()->success(compact('merchants'));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     * @throws Exception
     */
    public function getSkuPrices(Product $product)
    {
        $merchantId = $this->request()->get('merchant_id');

        $query = $product->skus()
            ->select([
                'skus.*',
                'sku_prices.cost_price as cost_price',
                'sku_prices.wholesale_price as wholesale_price',
                'sku_prices.retail_price as retail_price'
            ])
            ->leftJoin('sku_prices', function ($leftJoin) use ($merchantId) {
                $leftJoin->on('skus.id', '=', 'sku_prices.sku_id');

                if (!empty($merchantId)) {
                    $leftJoin->where('sku_prices.merchant_id', $merchantId);
                }
            });

        $skuPrices = $query->get();

        return $this->response()->success(compact('skuPrices'));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     * @throws Exception
     */
    public function updateMerchant(Product $product)
    {
        $merchantIds      = $this->request()->get('merchant_ids');
        $productMerchants = $product->merchants;

        foreach ($productMerchants as $merchant) {
            if (!in_array($merchant->id, $merchantIds)) {
                SkuPrice::query()
                    ->where('merchant_id', $merchant->id)
                    ->whereIn('sku_id', $product->skus->pluck('id'))
                    ->delete();

                ProductMerchant::query()
                    ->where('product_id', $product->id)
                    ->where('merchant_id', $merchant->id)
                    ->delete();
            }
        }

        foreach ($merchantIds as $merchantId) {
            ProductMerchant::updateOrCreate([
                'product_id' => $product->id,
                'merchant_id' => $merchantId
            ]);
        }

        return $this->response()->success();
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function confirmWeightVolume(Product $product)
    {
        $input     = $this->request()->only(['sku_ids']);
        $validator = new ConfirmWeightVolumeSKUValidator($product, $input);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $skus = Service::product()->confirmWeightVolumeSKU($product, $validator->getSkuIds(), $this->user);

        return $this->response()->success(['skus' => $skus]);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function getLogs(Product $product)
    {
        $logs = Service::product()->getLogs($product);

        return $this->response()->success(compact('logs'));
    }

}
