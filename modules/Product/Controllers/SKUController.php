<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use App\Base\Validator as BaseValidator;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Currency\Models\Currency;
use Modules\Merchant\Models\Merchant;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Product\Transformers\SelectedSkuItemTransformer;
use Modules\Product\Transformers\SkuDetailTransformer;
use Modules\Product\Transformers\SkuListItemTransformer;
use Modules\Product\Validators\CreateSKUValidator;
use Modules\Product\Validators\DetailSKUValidator;
use Modules\Product\Validators\ListSkuValidator;
use Modules\Product\Validators\UpdateListSkuValidator;
use Modules\Product\Validators\UpdateSKUStatusValidator;
use Modules\Product\Validators\UpdateSKUValidator;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\WarehouseArea;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SKUController extends Controller
{
    /**
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

        $user = $this->getAuthUser();

        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = Service::product()->importSKUs($user->tenant, $path, $user);

        return $this->response()->success(compact('errors'));
    }


    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        $results = Service::product()->listSKUs($filter, $this->getAuthUser());

        return $this->response()->success([
            'skus' => array_map(function (Sku $product) {
                return (new SkuListItemTransformer())->transform($product);
            }, $results->items()),
            'pagination' => $results
        ]);
    }


    /**
     * @return JsonResponse
     */
    public function suggest()
    {
        $user      = $this->getAuthUser();
        $filter    = $this->request()->only(['keyword', 'limit', 'merchant_id']);
        $validator = Validator::make($filter, [
            'keyword' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $filter['tenant_id'] = $user->tenant_id;
        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $filter['supplier_id'] = $this->user->suppliers->pluck('id')->all();
        }
        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            if (empty($filter['merchant_id'])) {
                $filter['merchant_id'] = $this->user->merchants->pluck('id')->all();
            }
        }

        $results             = Service::product()->listSKUs($filter, $user);
        $skus                = $results->items();

        return $this->response()->success([
            'skus' => array_map(function (Sku $product) {
                return (new SkuListItemTransformer())->transform($product);
            }, $skus)
        ]);
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function detail(Sku $sku)
    {
        $validator = new DetailSKUValidator($sku);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $data        = (new SkuDetailTransformer())->transform($sku);
        $prices      = $data['prices'];
        $merchantIds = $this->getMerchantIdByUser();
        foreach ($prices as $key => $price) {
            if (!in_array($price->merchant_id, $merchantIds)) {
                unset($prices[$key]);
            }
        }
        $data['prices'] = $prices;
        return $this->response()->success($data);
    }

    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListSkuValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
        }

        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $filter['supplier_id'] = $this->user->suppliers->pluck('id')->all();
        }

        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            if (empty($filter['merchant_id'])) {
                $filter['merchant_id'] = $this->user->merchants->pluck('id')->all();
            }
        }
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function getStatuses()
    {
        $statuses = Sku::$statusList;
        return $this->response()->success(compact('statuses'));
    }

    /**
     * @return JsonResponse
     */
    public function importPrice()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $errors = Service::product()->importPrice($user, $input['file']);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(CreateSKUValidator::$acceptKeys);
        $validator = new CreateSKUValidator($input, $user->tenant);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $sku = Service::product()->createSKU($input, $user);
        $sku = (new SkuDetailTransformer())->transform($sku);
        return $this->response()->success(compact('sku'));
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function update(Sku $sku)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(UpdateSKUValidator::$acceptKeys);
        $validator = new UpdateSKUValidator($input, $sku);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        if (isset($input['fobiz_code']) && !$user->can(Permission::SKU_CONFIG_EXTERNAL_CODE)) {
            return $this->response()->error(403, ['code' => BaseValidator::ERROR_403], 403);
        }

        $sku = Service::product()->updateSKU($sku, $input, $user);
        $sku = (new SkuDetailTransformer())->transform($sku);
        return $this->response()->success(compact('sku'));
    }

    /**
     * @return JsonResponse
     */
    public function updateListSku()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(["skus"]);
        $validator = new UpdateListSkuValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::product()->updateListSku($input, $user);
        return $this->response()->success(true);
    }

    /**
     * @return JsonResponse
     */
    public function updateStatus()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(['ids', 'status']);
        $validator = new UpdateSKUStatusValidator($user->tenant, $input);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $skus = $validator->getSkus();
        foreach ($skus as $sku) {
            if ($sku->status != $input['status']) {
                $skuOld      = clone $sku;
                $sku->status = $input['status'];
                $sku->save();

                $sku->logActivity(SkuEvent::SKU_UPDATE_STATUS, $user, [
                    'from' => $skuOld->status,
                    'to' => $sku->status,
                ]);

                $sku->product->logActivity(ProductEvent::SKU_UPDATE_STATUS, $user, [
                    'sku' => $sku->only(['id', 'code', 'name']),
                    'from' => $skuOld->status,
                    'to' => $sku->status,
                ]);
            }
        }

        return $this->response()->success($skus);
    }

    /**
     * @return array
     */
    protected function getMerchantIdByUser()
    {
        $user        = $this->getAuthUser();
        $merchantIds = $user->merchants()->pluck('merchants.id')->toArray();

        return $merchantIds;
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function getStocks(Sku $sku)
    {
        $stocks = Stock::query()
            ->where('sku_id', $sku->id)
            ->get()
            ->map(function (Stock $stock) {
                return [
                    'stock' => $stock,
                    'warehouse' => $stock->warehouse,
                    'warehouseArea' => $stock->warehouseArea
                ];
            })
            ->filter(function (array $stock) {
                if ($stock['warehouse']->status == 0 || $stock['warehouseArea']->status == WarehouseArea::STATUS_HIDDEN) {
                    return false;
                }
                return true;
            })
            ->values()
            ->all();

        return $this->response()->success(compact('stocks'));
    }

    /**
     * @return JsonResponse
     */
    public function updatePrices()
    {
        $user           = $this->getAuthUser();
        $input          = $this->request()->only(['merchant_id', 'sku_prices']);
        $inputValidator = UpdateSKUValidator::removeNullPrice($input);

        $validator = Validator::make($inputValidator, [
            'merchant_id' => 'required|exists:merchants,id',

            'sku_prices' => 'required|array|min:1',
            'sku_prices.*.sku_id' => 'required|exists:skus,id',
            'sku_prices.*.cost_price' => 'numeric|gte:0',
            'sku_prices.*.wholesale_price' => 'numeric|gte:0',
            'sku_prices.*.retail_price' => 'numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchantId = $input['merchant_id'];
        $skuPrices  = $input['sku_prices'];

        foreach ($skuPrices as $skuPriceInput) {
            $skuId = Arr::pull($skuPriceInput, 'sku_id');
            $sku   = Sku::find($skuId);

            $dataOld  = [];
            $skuPrice = SkuPrice::query()->where('merchant_id', $merchantId)
                ->where('sku_id', $skuId)->first();

            $changePrice = false;

            if (!$skuPrice instanceof SkuPrice) {
                $skuPrice = SkuPrice::create(array_merge($skuPriceInput, ['merchant_id' => $merchantId, 'sku_id' => $skuId]));

            } else {
                $skuPriceOld = clone $skuPrice;
                $dataOld     = $skuPriceOld->attributesToArray();

                $skuPrice->update($skuPriceInput);
            }

            foreach (['cost_price', 'wholesale_price', 'retail_price'] as $price) {
                $priceNew = floatval($skuPriceInput[$price]);
                $priceOld = Arr::get($dataOld, $price, 0);

                if (floatval($priceNew) != floatval($priceOld)) {
                    $changePrice = true;
                }
            }

            if (!$changePrice) continue;

            $merchant = $skuPrice->merchant;
            $currency = ($merchant instanceof Merchant) ? $merchant->getCurrency() : null;

            $sku->logActivity(SkuEvent::SKU_UPDATE_PRICE, $user, [
                'from' => $dataOld,
                'to' => $skuPrice->attributesToArray(),
                'merchant' => ($merchant instanceof Merchant) ? $merchant->only(['id', 'name', 'code']) : null,
                'currency' => ($currency instanceof Currency) ? $currency->attributesToArray() : null,
            ]);

            $sku->product->logActivity(ProductEvent::SKU_UPDATE_PRICE, $user, [
                'sku' => $sku->only(['id', 'code', 'name']),
                'merchant' => ($merchant instanceof Merchant) ? $merchant->only(['id', 'name', 'code']) : null,
                'currency' => ($currency instanceof Currency) ? $currency->attributesToArray() : null,
                'from' => $dataOld,
                'to' => $skuPrice->attributesToArray(),
            ]);
        }

        return $this->response()->success();
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function orderPackings(Sku $sku)
    {
        $orderPackings = OrderPacking::query()
            ->whereHas('orderPackingItems', function ($query) use ($sku) {
                $query->where('sku_id', $sku->id);
            })
            ->whereIn('status', [OrderPacking::STATUS_WAITING_PROCESSING, OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING])
            ->with(['order', 'merchant', 'shippingPartner', 'freightBill'])
            ->get()
            ->map(function (OrderPacking $orderPacking) {
                return [
                    'order_packing' => $orderPacking,
                    'freightBill' => $orderPacking->freightBill,
                    'order' => $orderPacking->order,
                    'merchant' => $orderPacking->merchant,
                    'shipping_partner' => $orderPacking->shippingPartner
                ];
            })
            ->values()
            ->all();

        return $this->response()->success($orderPackings);
    }

    /**
     * @return JsonResponse
     */
    public function selectedSkus()
    {
        $input = $this->request()->only(["sku_ids"]);

        $validator = Validator::make($input, [
            'sku_ids' => 'required|array',
            'sku_ids.*' => 'numeric|gte:0'
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $results = Service::product()->skuQuery(['id' => $input["sku_ids"]])->getQuery();
        $results->with([
            'product',
            'product.merchants'
        ]);

        return $this->response()->success([
            'skus' => $results->get()->map(function (Sku $sku) {
                return (new SelectedSkuItemTransformer())->transform($sku);
            })
        ]);
    }

    /**
     * [barcodeRender]
     * @return [type] [PDF data]
     */
    public function barcodeRender()
    {
        $input    = $this->request()->collect();
        $column   = $input->get('column', 2);
        $dataSkus = collect($input->get('data'));
        $dataRow  = '';
        $size     = 100;

        if ($column == 3) $size = 93;
        $width    = round($size / $column, 2);
        $pageSize = 104;

        if ($column == 2) {
            $pageSize = 70;
        }

        $dataSkusFiltered = $dataSkus->filter(function ($value, $key) {
            $code = data_get($value, 'code', '');
            return $code != '';
        });

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $html      = view('barcode', ['data' => [
            'column' => $column,
            'width' => $width,
            'page_size' => $pageSize,
            'data_skus_filtered' => $dataSkusFiltered,
            'generator' => $generator
        ]])->render();
        return PDF::loadHTML($html)
            ->setOptions(['dpi' => 203])
            // ->setPaper([ 0 , 0 , 226.77 , 226.77 ])
            ->setWarnings(false)
            ->save('barcode.pdf')
            ->download('barcode_' . Carbon::now()->toDateTimeString() . '.pdf');
    }

}
