<?php

namespace Modules\Order\Controllers;

use App\Base\Controller;
use Exception;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Auth\Services\Permission;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Transformers\ListFinanceTransformer;
use Modules\Order\Transformers\MerchantOrderDetailTransformer;
use Modules\Order\Transformers\OrderListItemTransformer;
use Modules\Order\Validators\ImportingBashOrderValidator;
use Modules\Order\Validators\MerchantCreateOrderValidator;
use Modules\Product\Models\Sku;
use Modules\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Commands\UpdateOrders;
use Modules\Order\Resource\DataResource;
use Modules\Order\Validators\UpdateOrderMerchantValidator;
use Modules\Order\Validators\UpdateOrderValidator;
use Modules\Product\Models\SkuCombo;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\Warehouse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MerchantOrderController extends Controller
{
    /**
     * Tạo filter để query order
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs                = $inputs ?: [
            'code',
            'sku_code',
            'sku_id',
            'warehouse_id',
            'receiver_name',
            'status',
            'finance_status',
            'payment_type',
            'payment_method',
            'freight_bill',
            'created_at',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'name_store'
        ];
        $filter                = $this->requests->only($inputs);
        $filter                = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id']   = $this->user->tenant_id;
        $filter['merchant_id'] = $this->user->merchant->id;
        if (!empty($filter['sku_code'])) {
            $filter = $this->makeFilterSkuCode($filter);
        }
        return $filter;
    }


    /**
     * Xuất danh sách orders
     *
     * @return BinaryFileResponse
     */
    public function export()
    {
        $filter   = $this->getQueryFilter();
        $pathFile = Service::order()->export($filter, $this->user, false);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilterSkuCode($filter)
    {
        $SkuCode = Arr::pull($filter, 'sku_code');
        $query   = $this->user->tenant->skus()->select(['skus.*'])
            ->join('products', 'products.id', '=', 'skus.product_id')
            ->join('product_merchants', 'products.id', '=', 'product_merchants.product_id')
            ->where('product_merchants.merchant_id', $this->user->merchant->id);

        if (is_array($SkuCode)) {
            $query->whereIn('skus.code', $SkuCode);
        } else {
            $query->where('skus.code', trim($SkuCode));
        }

        $filter['sku_id'] = $query->pluck('skus.id')->toArray();

        unset($filter['sku_code']);
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter          = $this->getQueryFilter();
        $queryAllResults = Service::order()->listOrder(array_merge($filter, ['export' => true]));
        $querySumSku     = OrderSku::query()->whereIn('order_id', $queryAllResults->select('orders.id'));
        $results         = Service::order()->listOrder($filter);

        $canViewCustomer = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        return $this->response()->success([
            'orders' => array_map(function ($order) {
                $orderData = (new OrderListItemTransformer())->transform($order);
                return $orderData;
            }, $results->items()),
            'pagination' => $results,
            'can_view_customer' => $canViewCustomer,
            'sku_sum' => $querySumSku->sum('quantity'),
        ]);
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function import()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(['file', 'warehouse_id']);
        $validator = Validator::make($input, [
            'warehouse_id' => 'required',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouse = $user->tenant->warehouses()->firstWhere('warehouses.id', $input['warehouse_id']);
        if (!$warehouse instanceof Warehouse) {
            return $this->response()->error('INPUT_INVALID', ['warehouse_id' => \App\Base\Validator::ERROR_NOT_EXIST]);
        }

        $path   = Service::order()->getRealPathFile($input['file']);
        $result = Service::order()->merchantImportOrders($path, $user, $warehouse);

        return $this->response()->success(compact('result'));
    }

    /**
     * @return JsonResponse
     */
    public function importBashOrder()
    {
        $input     = $this->request()->only(['bash']);
        $validator = (new ImportingBashOrderValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $result = Service::order()->importBashOrder($validator->getCachedOrders(), $this->user);
        return $this->response()->success(compact('result'));
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importFreightBill()
    {
        $input     = $this->request()->only(['file', 'replace']);
        $validator = Validator::make($input, [
            'replace' => 'required|boolean',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::order()->importMerchantFreightBill($input['file'], $this->user->merchant, $this->user, (boolean)$input['replace']);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     */
    public function checkingBeforeCreate()
    {
        $user                  = $this->getAuthUser();
        $input                 = $this->request()->all();
        $input['merchant_id']  = $user->merchant->id;
        $input['payment_type'] = Order::PAYMENT_TYPE_COD;
        if (empty($input['code'])) {
            $input['code'] = Str::random(16);
        }
        $validator = (new MerchantCreateOrderValidator($user->merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        /**
         * Kiểm tra tồn sku
         */
        $warningOrders = [];
        $orderSkus      = $validator->getOrderSkus();
        $orderSkuCombos = $validator->getOrderSkuCombos();

        if ($orderSkuCombos) {
            foreach ($orderSkuCombos as $orderSkuCombo) {
                $skuComboId       = data_get($orderSkuCombo, 'id', 0);
                $skuComboQuantity = data_get($orderSkuCombo, 'quantity', 0);

                $skuCombo = SkuCombo::find($skuComboId);
                if ($skuCombo) {
                    $skuComboSkus = $skuCombo->skuComboSkus;
                    if ($skuComboSkus) {
                        foreach ($skuComboSkus as $skuComboSku) {
                            if (isset($orderSkus[$skuComboSku->sku_id])) {
                                $orderSkus[$skuComboSku->sku_id]['quantity'] += ($skuComboSku->quantity * $skuComboQuantity);
                            } else {
                                $orderSkus[$skuComboSku->sku_id] = [
                                    'sku_id'   => $skuComboSku->sku_id,
                                    'quantity' => ($skuComboSku->quantity * $skuComboQuantity),
                                ];   
                            }
                        }
                    }
                }
            }
        }

        if ($orderSkus) {
            foreach ($orderSkus as $orderSku) {
                $sku      = Sku::find($orderSku['sku_id']);
                $quantity = $orderSku['quantity'];
                /** @var Stock|null $stock */
                $stock = $sku->stocks->where('warehouse_id', $input['warehouse_id'])->sortByDesc('quantity')->first();
                if (empty($stock) || $stock->quantity < $quantity) {
                    $warningOrders[] = [
                        'sku_code' => $sku->code,
                        'warehouse_area_code' => $stock ? $stock->warehouseArea->code : '',
                        'warnings' => [
                            [
                                'message' => 'lack_of_stock',
                                'quantity' => $quantity,
                                'quantity_stock' => $stock ? $stock->quantity : 0
                            ]
                        ],
                    ];
                }
            }
        }

        return $this->response()->success(['warning_orders' => $warningOrders]);
    }


    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function create()
    {
        $user                  = $this->getAuthUser();
        $input                 = $this->request()->all();
        $input['merchant_id']  = $user->merchant->id;
        if (empty($input['code'])) {
            $input['code'] = Str::random(16);
        }
        $validator = (new MerchantCreateOrderValidator($user->merchant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $order = (new CreateOrder(array_merge($input, [
            'marketplace_code' => Marketplace::CODE_MANUAL,
            'creator' => $user,
            'merchant' => $user->merchant,
            'orderSkus' => $validator->getOrderSkus(),
            'orderSkuCombos' => $validator->getOrderSkuCombos(),
            'receiverCountry' => $validator->getReceiverCountry(),
            'receiverProvince' => $validator->getReceiverProvince(),
            'receiverDistrict' => $validator->getReceiverDistrict(),
            'receiverWard' => $validator->getReceiverWard(),
            'orderAmount' => $validator->getOrderAmount(),
            'totalAmount' => $validator->getTotalAmount(),
            'extraServices' => [],
            'shippingPartner' => $validator->getShippingPartner(),
        ])))->handle();

        $orderSkus         = $order->orderSkus;
        $orderTransactions = $order->orderTransactions;

        return $this->response()->success(compact('order', 'orderSkus', 'orderTransactions'));
    }

    public function update(Order $order)
    {
        $input = $this->request()->all();
        // dd($input);
        $validator = new UpdateOrderMerchantValidator($order, $input);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orderAmount = 0;

        $itemSkus = [];

        $items = data_get($input, "orderSkus", []);

        // dd($items);

        foreach ($items as $item) {

            $price    = data_get($item, 'price');
            $quantity = data_get($item, 'quantity');
            $skuId    = data_get($item, 'id');

            $totalAmount = (float)$price * (int)$quantity;
            $orderAmount += $totalAmount;

            $discountAmount = (float)data_get($item, 'discount_amount');

            $itemSkus[] = [
                'id' => $skuId,
                'discount_amount' => $discountAmount,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $totalAmount    = (float)data_get($input, 'total_amount', 0);
        $discountAmount = (float)data_get($input, 'discount_amount', 0);

        $dataResource = new DataResource();

        $dataResource->order_amount       = $orderAmount;
        $dataResource->discount_amount    = $discountAmount;
        $dataResource->total_amount       = $totalAmount;
        $dataResource->description        = data_get($input, 'description');
        $dataResource->receiverPostalCode = data_get($input, 'receiver_postal_code');
        $dataResource->receiver           = [
            'name' => data_get($input, 'receiver_name', ''),
            'phone' => data_get($input, 'receiver_phone'),
            'address' => data_get($input, 'receiver_address', ''),
            'province_id' => data_get($input, 'receiver_province_id', 0),
            'district_id' => data_get($input, 'receiver_district_id', 0),
            'ward_id' => data_get($input, 'receiver_ward_id', 0),
        ];
        $dataResource->items              = $itemSkus;

        // dd($dataResource);

        $orderUpdated = (new UpdateOrders($order, $dataResource))->handle();

        return $orderUpdated;
    }


    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function detail(Order $order)
    {
        $user = $this->getAuthUser();
        if ($order->merchant_id != $user->merchant->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $data = (new MerchantOrderDetailTransformer($user))->transform($order);

        return $this->response()->success($data);
    }


    /**
     * @return JsonResponse
     */
    public function finance()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::order()->merchantListFinance($filter);

        return $this->response()->success([
            'orders' => array_map(function ($order) {
                return (new ListFinanceTransformer())->transform($order);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function stats()
    {
        $filter = $this->getQueryFilter([
            'code',
            'sku_code',
            'status',
            'finance_status',
            'created_at',
        ]);
        $stats  = Service::order()->stats($filter, $this->getAuthUser());

        return $this->response()->success(array_merge($stats, ['currency' => $this->user->merchant->getCurrency()]));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function getLogs(Order $order)
    {
        $user = $this->getAuthUser();
        if ($order->merchant_id != $user->merchant->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $logs = Service::order()->getLogs($order);

        return $this->response()->success(compact('logs'));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     * @throws WorkflowException
     */
    public function cancel(Order $order)
    {
        if (!Service::order()->sellerCanCancel($order, $this->getAuthUser())) {
            return $this->response()->error('INPUT_INVALID', ['status' => 'invalid']);
        }

        $order->cancel_reason = Order::CANCEL_REASON_SELLER;
        $order->changeStatus(Order::STATUS_CANCELED, $this->getAuthUser());

        return $this->response()->success(compact('order'));
    }

}
