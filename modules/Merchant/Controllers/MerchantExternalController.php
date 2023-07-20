<?php

namespace Modules\Merchant\Controllers;

use App\Base\ExternalController;
use Gobiz\Support\Helper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Merchant\Commands\RegisterMerchant;
use Modules\Merchant\Commands\UpdateMerchantExternal;
use Modules\Merchant\ExternalTransformers\MerchantTransformer;
use Modules\Merchant\ExternalTransformers\MerchantTransformerNew;
use Modules\Merchant\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Validators\CreatingExternalMerchantOrderValidator;
use Modules\Merchant\Validators\CreatingExternalMerchantProductValidator;
use Modules\Merchant\Validators\DetailExternalMerchantOrderValidator;
use Modules\Merchant\Validators\DetailExternalMerchantProductValidator;
use Modules\Merchant\Validators\GettingExternalProductStocksValidator;
use Modules\Merchant\Validators\RegisterMerchantExternalValidator;
use Modules\Merchant\Validators\RegisterMerchantExternalValidatorNew;
use Modules\Merchant\Validators\UpdateMerchantExternalValidator;
use Modules\Order\Models\Order;
use Modules\Order\Transformers\OrderTransformer;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class MerchantExternalController extends ExternalController
{
    /**
     * @param Order $order
     * @return array
     */
    protected function transformDetailOrder(Order $order)
    {
        $result['order']            = $order;
        $result['products']         = $order->skus;
        $result['services']         = $order->exportServices;
        $result['shipping_partner'] = $order->shippingPartner;
        $result['freight_bills']    = $order->freightBills;
        $result['warehouse']        = $order->warehouse;
        return $result;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function transformDetailProduct(Product $product)
    {
        /** @var Sku $sku */
        $sku      = $product->skus->first();
        $response = ['product' => $product];
        if ($sku) {
            $response['stocks'] = $sku->stocks;
        }
        return $response;
    }

    /**
     * @param Stock $stock
     * @return array
     */
    protected function transformStock(Stock $stock)
    {
        $warehouse     = $stock->warehouse;
        $warehouseArea = $stock->warehouseArea;
        $result        = [
            'stock' => $stock->only([
                'quantity',
                'real_quantity',
                'total_storage_fee',
                'created_at',
                'updated_at'
            ])
        ];
        if ($warehouse) {
            $result['warehouse'] = $stock->warehouse;
        }
        if ($warehouseArea) {
            $result['warehouse_area'] = $stock->warehouseArea;
        }
        return $result;
    }

    /**
     * Tạo seller
     *
     * @return JsonResponse
     */
    public function create()
    {
        $input                         = $this->request()->only([
            'code',
            'password',
            'email',
            'location',
            'phone'
        ]);
        $input['username']             = $input['code'];
        $input['free_days_of_storage'] = Merchant::FREE_DAYS_OF_STORAGE;
        $input['re_password']          = $input['password'];
        $validator                     = (new RegisterMerchantExternalValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $tenant              = $validator->getTenant();
        $input['creator_id'] = $this->user->id;
        $merchant            = (new RegisterMerchant($this->user, $validator->getLocation(), $input))->handle();
        if (!$merchant instanceof Merchant) {
            return $this->response()->error('INPUT_INVALID', ['error' => $merchant]);
        }

        return $this->response()->success(['merchant' => $merchant->refresh()]);
    }

    /**
     * Tạo sản phẩm
     *
     * @param $merchantCode
     * @return JsonResponse
     */
    public function createProduct($merchantCode)
    {
        $input                  = $this->request()->only([
            'name',
            'code',
            'image',
            'weight',
            'height',
            'width',
            'length'
        ]);
        $input['merchant_code'] = $merchantCode;
        if (empty($input['code'])) {
            $input['code'] = Helper::quickRandom(10, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }
        $validator = new CreatingExternalMerchantProductValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        if (!empty($input['image'])) {
            $input['files'] = [$validator->getFileUpload()];
            unset($input['image']);
        }
        $product = Service::product()->merchantCreateProduct($input, $this->user, $validator->getMerchant());

        return $this->response()->success($this->transformDetailProduct($product));
    }

    /**
     * Chỉ tiết sp
     *
     * @param $merchantCode
     * @param $productCode
     * @return JsonResponse
     */
    public function detailProduct($merchantCode, $productCode)
    {
        $input     = [
            'merchant_code' => trim($merchantCode),
            'product_code' => trim($productCode),
        ];
        $validator = new DetailExternalMerchantProductValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success($this->transformDetailProduct($validator->getProduct()));
    }

    /**
     * Lấy danh sách tồn kho của 1 sản phẩm
     *
     * @param $merchantCode
     * @param $productCode
     * @return JsonResponse
     */
    public function stocksOfProduct($merchantCode, $productCode)
    {
        $input     = [
            'merchant_code' => trim($merchantCode),
            'product_code' => trim($productCode)
        ];
        $validator = new GettingExternalProductStocksValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $stocks = Service::product()->gettingMerchantProductStocks($validator->getProduct());

        return $this->response()->success(['stocks' => $stocks->map(function ($stock) {
            return $this->transformStock($stock);
        })]);
    }

    /**
     * Listing Order By Merchant
     *
     * @param string $merchantCode
     * @return JsonResponse
     */
    public function listingOrder($merchantCode)
    {
        $request = $this->request()->all();

        $perPage        = data_get($request, 'per_page', 20);
        $code           = data_get($request, 'code', '');
        $skuCode        = data_get($request, 'sku_code', '');
        $trackingNumber = data_get($request, 'tracking_number', '');
        $status         = data_get($request, 'status', '');
        $createdAt      = [
            'from' => data_get($request, 'created_from'),
            'to' => data_get($request, 'created_to'),
        ];

        if ($perPage > 100) {
            $perPage = 100;
        }
        // Lấy danh sách orders của merchant này
        $merchant = Merchant::where('code', $merchantCode)->first();
        // dd($merchant);
        $dataReturn = [];
        if ($merchant) {
            $paginator = Order::select('orders.*')
                ->merchant($merchant->id)
                ->code($code)
                ->skuCode($skuCode)
                ->trackingNumber($trackingNumber)
                ->status($status)
                ->createdAt($createdAt)
                ->orderBy('orders.id', 'DESC')
                ->paginate($perPage);
            $orders    = $paginator->getCollection();

            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($orders, new OrderTransformer);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * Get Order Detail
     *
     * @param string $merchantCode
     * @param int $orderId
     * @return JsonResponse
     */
    public function detailOrderNew($merchantCode, $orderId)
    {
        $request = $this->request()->all();

        $merchant = Merchant::where('code', $merchantCode)->first();
        // dd($merchant);
        $dataReturn = [];
        if ($merchant) {
            $order = Order::select('orders.*')
                ->merchant($merchant->id)
                ->where('id', $orderId)
                ->first();

            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalItem($order, new OrderTransformer);

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    /**
     * Tạo đơn
     *
     * @param $merchantCode
     * @return JsonResponse
     */
    public function createOrder($merchantCode)
    {
        $input                  = $this->request()->only([
            'code',
            'description',
            'discount_amount',
            'total_amount',
            'freight_bill',
            'product_quantity',
            'receiver_address',
            'receiver_district_code',
            'receiver_name',
            'receiver_phone',
            'receiver_province_code',
            'receiver_ward_code',
            'shipping_partner_code',
            'warehouse_code',
            'products'
        ]);
        $input['merchant_code'] = $merchantCode;
        $input['payment_type']  = Order::PAYMENT_TYPE_COD;
        if (empty($input['code'])) {
            $input['code'] = Str::random(16);
        }
        $validator = new CreatingExternalMerchantOrderValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $input['warehouse_id'] = $validator->getWarehouse()->id;
        $order                 = Service::order()->create(array_merge($input, [
            'creator' => $this->user,
            'merchant' => $validator->getMerchant(),
            'orderSkus' => $validator->getOrderSkus(),
            'receiverCountry' => $validator->getReceiverCountry(),
            'receiverProvince' => $validator->getReceiverProvince(),
            'receiverDistrict' => $validator->getReceiverDistrict(),
            'receiverWard' => $validator->getReceiverWard(),
            'orderAmount' => $validator->getOrderAmount(),
            'totalAmount' => $validator->getTotalAmount(),
            'extraServices' => [],
            'shippingPartner' => $validator->getShippingPartner(),
        ]));

        return $this->response()->success($this->transformDetailOrder($order));
    }

    /**
     * Chỉ tiết đơn
     *
     * @param $merchantCode
     * @param $orderCode
     * @return JsonResponse
     */
    public function detailOrder($merchantCode, $orderCode)
    {
        $input     = [
            'merchant_code' => trim($merchantCode),
            'order_code' => trim($orderCode),
        ];
        $validator = new DetailExternalMerchantOrderValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success($this->transformDetailOrder($validator->getOrder()));
    }

    /** tạo seller từ vela one
     * @return JsonResponse
     */
    public function createSeller()
    {
        $inputs               = $this->request()->only([
            'location',
            'phone',
            'user_name',
            'code',
            'address',
            'description',
            'name',
            'status'
        ]);
        $tenantId             = $this->user->tenant_id;
        $creatorId            = $this->user->id;
        $inputs['creator_id'] = $creatorId;
        $inputs['tenant_id']  = $tenantId;
        $validator            = (new RegisterMerchantExternalValidatorNew($inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchant = Service::merchant()->createMerchant($inputs, $this->user);
        $dataReturn = [];
        $fractal    = new FractalManager();
        $resource   = new FractalItem($merchant, new MerchantTransformerNew());
        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);
    }

    /** update seller tu vela one
     * @param Merchant $merchant
     * @return JsonResponse
     */
    public function updateSeller($id)
    {
        $user  = $this->user;
        $input = $this->request()->all();
        if (isset($inputs['free_days_of_storage']) && $inputs['free_days_of_storage'] === '') {
            $inputs['free_days_of_storage'] = null;
        }
        $merchant = Merchant::query()->where([
            'tenant_id' => $user->tenant_id,
            'id' => $id,
            'creator_id' => $user->id
        ])->first();
        if (!$merchant) {
            return Service::app()->response()->error(403, ['message' => 'authorization'], 403);
        }
        $validator = new UpdateMerchantExternalValidator($merchant, $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchant = (new UpdateMerchantExternal($merchant, $user, $input))->handel();

        $dataReturn = [];
        $fractal    = new FractalManager();
        $resource   = new FractalItem($merchant, new MerchantTransformerNew());
        $dataReturn = $fractal->createData($resource)->toArray();
        return $this->response()->success($dataReturn);
    }

    /** chi tiết seller
     * @param $id
     * @return JsonResponse
     */
    public function sellerDetail($id)
    {
        $user     = $this->user;
        $merchant = Merchant::query()->where([
            'tenant_id' => $user->tenant_id,
            'id' => $id,
            'creator_id' => $user->id
        ])->first();
        if (!$merchant) {
            return Service::app()->response()->error(403, ['message' => 'authorization'], 403);
        }
        $dataReturn = [];
        $fractal    = new FractalManager();
        $resource   = new FractalItem($merchant, new MerchantTransformerNew());
        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);
    }

    public function listSellers()
    {
        $request = $this->request()->all();
        $user    = $this->user;

        $perPage    = data_get($request, 'per_page', 20);
        $status     = data_get($request, 'status');
        $locationId = data_get($request, 'location_id');
        $code       = data_get($request, 'code');
        $username   = data_get($request, 'username');
        $name       = data_get($request, 'name');
        $filterAll  = data_get($request, 'filter_all');
        $ref        = data_get($request, 'ref');

        if ($perPage > 200) {
            $perPage = 200;
        }
        $dataReturn = [];

        $creatorId = $user->id;
        $tenantId  = $user->tenant_id;

        if ($filterAll) {
            $creatorId = 0;
            $tenantId  = 0;
        }

        if ($username) {
            $usernameExploded = $this->cleanUserNameFiler($username);
        } else {
            $usernameExploded = [];
        }

        $paginator  = Merchant::query()
            ->tenantId($tenantId)
            ->creatorId($creatorId)
            ->status($status)
            ->location($locationId)
            ->code($code)
            ->userName($usernameExploded)
            ->name($name)
            ->ref($ref)
            ->orderBy('merchants.id', 'DESC')
            ->paginate($perPage);
        $merchants  = $paginator->getCollection();
        $fractal    = new FractalManager();
        $resource   = new FractalCollection($merchants, new MerchantTransformerNew());
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);
    }

    protected function cleanUserNameFiler(string $username)
    {
        $username = strtolower($username);
        $usernameExploded = explode(' ', $username);

        return $usernameExploded;
    }

}
