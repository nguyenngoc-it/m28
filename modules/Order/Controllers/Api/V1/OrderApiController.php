<?php

namespace Modules\Order\Controllers\Api\V1;

use App\Base\ExternalController;
use Carbon\Carbon;
use Gobiz\Log\LoggerFactory;
use Gobiz\Log\LogService;
use Illuminate\Http\Request;
use Modules\Merchant\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Order\Transformers\OrderTransformer;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Commands\UpdateOrders;
use Modules\Order\Resource\Data3rdResource;
use Modules\Order\Resource\DataResource;
use Modules\Order\Validators\CancelOrderValidator;
use Modules\Order\Validators\CreateOrderFromApiValidator;
use Modules\Order\Validators\UpdateOrderApiValidator;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Illuminate\Support\Str;

class OrderApiController extends ExternalController
{
    /**
     * Listing Order By Merchant
     *
     * @return JsonResponse
     */
    public function index()
    {
        $request = $this->request()->all();

        $merchantCode   = data_get($request, 'merchant_code');
        $perPage        = data_get($request, 'per_page', 20);
        $code           = data_get($request, 'code', '');
        $skuCode        = data_get($request, 'sku_code', '');
        $trackingNumber = data_get($request, 'tracking_number', '');
        $status         = data_get($request, 'status', '');
        $campaign       = data_get($request, 'campaign', '');
        $receiverName   = data_get($request, 'receiver_name', '');
        $receiverPhone  = data_get($request, 'receiver_phone', '');
        $campaign       = data_get($request, 'campaign', '');
        $paymentType    = data_get($request, 'payment_type', '');
        $createdAt      = [
            'from' => data_get($request, 'created_from'),
            'to' => data_get($request, 'created_to'),
        ];

        if ($perPage > 100) {
            $perPage = 100;
        }
        // Lấy danh sách orders của merchant này
        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();
        // dd($merchant);
        $dataReturn = [];
        if ($merchant) {
            $paginator = Order::select('orders.*')
                ->merchant($merchant->id)
                ->code($code)
                ->skuCode($skuCode)
                ->campaign($campaign)
                ->receiverName($receiverName)
                ->receiverPhone($receiverPhone)
                ->paymentType($paymentType)
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
     * @param int $orderId
     * @return JsonResponse
     */
    public function detail($orderId)
    {
        $request = $this->request()->all();

        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();
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
     * Create Order
     *
     * @return JsonResponse
     */
    public function create()
    {
        $request = $this->request()->all();
        LogService::logger('3rd-api-inputs-order')->info('raw-inputs', $request);
        $dataReturn           = [];
        $merchantCode         = data_get($request, 'merchant_code');
        $receiverName         = data_get($request, 'receiver_name');
        $receiverPhone        = data_get($request, 'receiver_phone');
        $receiverAddress      = data_get($request, 'receiver_address');
        $receiverCountryCode  = data_get($request, 'receiver_country_code');
        $receiverProvinceCode = data_get($request, 'receiver_province_code');
        $receiverDistrictCode = data_get($request, 'receiver_district_code');
        $receiverWardCode     = data_get($request, 'receiver_ward_code');
        $receiverPostalCode   = data_get($request, 'receiver_postal_code');
        $refCode              = data_get($request, 'ref_code');
        $campaign             = data_get($request, 'campaign');
        $freightBill          = data_get($request, 'freight_bill_code');
        $totalAmount          = (float)data_get($request, 'total_amount');
        $discountAmount       = (float)data_get($request, 'discount_amount');
        $shippingAmount       = (float)data_get($request, 'shipping_amount');
        $description          = data_get($request, 'description');
        $warehouseId          = data_get($request, 'warehouse_id');
        $shippingPartnerCode  = data_get($request, 'shipping_partner_code');
        $intendedDeliveryAt   = data_get($request, 'intended_delivery_at', null);
        $skus                 = data_get($request, 'skus', []);
        $skuCombos            = data_get($request, 'sku_combos', []);
        $payment              = data_get($request, 'payment', []);

        $validator = new CreateOrderFromApiValidator($request);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $merchant = Merchant::where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();

        if ($merchant) {

            $locations = $this->getReceiverLocations([$receiverCountryCode, $receiverProvinceCode, $receiverDistrictCode, $receiverWardCode]);

            $shippingPartner = ShippingPartner::where('code', $shippingPartnerCode)
                ->where('tenant_id', $merchant->tenant_id)
                ->first();
            if ($shippingPartner) {
                $shippingPartnerId = $shippingPartner->id;
            } else {
                $shippingPartnerId = 0;
            }

            $orderAmount = 0;

            $itemSkus      = [];
            $itemSkuCombos = [];

            foreach ($skus as $sku) {

                $skuId          = data_get($sku, 'id');
                $skuCode        = data_get($sku, 'code', '');
                $price          = data_get($sku, 'price');
                $quantity       = data_get($sku, 'quantity');
                $totalAmountSku = (float)$price * (int)$quantity;
                $orderAmount    += $totalAmountSku;

                $discountAmountSku = (float)data_get($sku, 'discount_amount');

                // Check Sku Đã tồn tại trên hệ thống chưa
                if ($skuCode) {
                    $skuCheck = Sku::select('skus.*')
                        ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                        ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                        ->where(function ($query) use ($skuCode) {
                            return $query->where('store_skus.code', $skuCode)
                                ->orWhere('skus.code', $skuCode);
                        })
                        ->where(function ($query) use ($merchant) {
                            return $query->where('skus.merchant_id', $merchant->id)
                                ->orWhere('product_merchants.merchant_id', $merchant->id);
                        })
                        ->where('skus.status', Sku::STATUS_ON_SELL)
                        ->first();
                    $skuData  = $skuCheck;
                } else {
                    $skuData = Sku::find($skuId);
                }


                if ($skuData) {
                    $itemSkus[] = [
                        'sku_id' => $skuData->id,
                        'code' => $skuData->code,
                        'discount_amount' => $discountAmountSku,
                        'price' => $price,
                        'quantity' => $quantity,
                    ];
                }
            }

            foreach ($skuCombos as $skuCombo) {
                $skuComboCode     = data_get($skuCombo, 'code');
                $skuComboPrice    = data_get($skuCombo, 'price');
                $skuComboQuantity = data_get($skuCombo, 'quantity');

                $skuComboCheck = SkuCombo::where('code', $skuComboCode)->where('merchant_id', $merchant->id)->first();
                if ($skuComboCheck) {
                    $itemSkuCombos[] = [
                        'sku_id' => 0,
                        'code' => $skuComboCode,
                        'quantity' => $skuComboQuantity,
                        'discount_amount' => 0,
                        'price' => $skuComboPrice,
                    ];
                }

                $totalAmountSkuCombo = $skuComboPrice * $skuComboQuantity;
                $orderAmount         += $totalAmountSkuCombo;
            }
            // thông tin thanh toán
            $paymentType = data_get($payment, 'payment_type');
            if ($paymentType && $paymentType == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
                $dataPayment = [
                    'payment_type' => data_get($payment, 'payment_type'),
                    'payment_time' => data_get($payment, 'payment_time'),
                    'payment_note' => data_get($payment, 'payment_note'),
                    'payment_method' => data_get($payment, 'payment_method'),
                    'payment_amount' => data_get($payment, 'payment_amount'),
                ];
                $paymentMethod = data_get($payment, 'payment_method');
                if ($paymentMethod == OrderTransaction::METHOD_BANK_TRANSFER){
                    $dataPayment = array_merge($dataPayment, [
                        'bank_account' => data_get($payment, 'bank_account'),
                        'bank_name' => data_get($payment, 'bank_name'),
                        'standard_code' => data_get($payment, 'standard_code')
                    ]);
                }
            } else {
                $dataPayment = [
                    'payment_type' => Order::PAYMENT_TYPE_COD
                ];
            }

            // Make Order
            $dataResource = new Data3rdResource();
            // thông tin thanh toán
            $dataResource->payment = $dataPayment;

            $dataResource->receiver             = [
                'name' => $receiverName,
                'phone' => $receiverPhone,
                'address' => $receiverAddress,
                'country_id' => data_get($locations, "country.id"),
                'province_id' => data_get($locations, "province.id"),
                'district_id' => data_get($locations, "district.id"),
                'ward_id' => data_get($locations, "ward.id"),
                'postal_code' => $receiverPostalCode,
            ];
            $dataResource->marketplace_code     = Marketplace::CODE_VELAONE;
            $dataResource->code                 = $refCode ? $refCode : $this->makeCode();
            $dataResource->refCode              = $refCode;
            $dataResource->campaign             = $campaign;
            $dataResource->freight_bill         = $freightBill;
            $dataResource->warehouse_id         = $warehouseId;
            $dataResource->merchant_id          = $merchant->id;
            $dataResource->creator_id           = auth()->user()->id;
            $dataResource->shipping_partner_id  = $shippingPartnerId;
            $dataResource->order_amount         = $orderAmount;
            $dataResource->discount_amount      = abs($discountAmount);
            $dataResource->total_amount         = $totalAmount;
            $dataResource->shipping_amount      = $shippingAmount;
            $dataResource->using_cod            = true;
            $dataResource->status               = Order::STATUS_WAITING_INSPECTION;
            $dataResource->description          = $description;
            $dataResource->intended_delivery_at = $intendedDeliveryAt ? Carbon::parse($intendedDeliveryAt) : null;
            $dataResource->items                = $itemSkus;
            $dataResource->itemCombos           = $itemSkuCombos;

            // dd($dataResource);
            $store     = Store::where('marketplace_code', Marketplace::CODE_VELAONE)->first();
            $orderData = Service::order()->createOrderFrom3rdPartner($store, $dataResource);

            $fractal    = new FractalManager();
            $resource   = new FractalItem($orderData, new OrderTransformer);
            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);
    }

    public function update()
    {
        $input = $this->request()->all();

        $merchantCode = data_get($input, 'merchant_code');
        $refCode      = data_get($input, 'ref_code');
        $code         = data_get($input, 'code');

        // dd($input);

        $merchant = Merchant::where(['code' => $merchantCode])
            ->where('tenant_id', $this->user->tenant->id)
            ->first();

        $dataReturn = ['message' => 'Merchant Code Not Exist'];

        if ($merchant) {

            $order = null;

            if ($code && $refCode) {
                $order = Order::query()
                    ->merchant($merchant->id)
                    ->code($code)
                    ->refCode($refCode)
                    ->first();
            }

            if (!$order) {
                return $this->response()->error(['message' => 'Order Not Exist']);
            }

            $receiverCountryCodeDb = $order->receiverCountry ? $order->receiverCountry->code : null;
            // $receiverProvinceCodeDb = $order->receiverProvince ? $order->receiverProvince->code: null;
            // $receiverDistrictCodeDb = $order->receiverDistrict ? $order->receiverDistrict->code: null;
            // $receiverWardCodeDb     = $order->receiverWard ? $order->receiverWard->code        : null;

            $receiverName         = data_get($input, 'receiver_name', $order->receiver_name);
            $receiverPhone        = data_get($input, 'receiver_phone', $order->receiver_phone);
            $receiverAddress      = data_get($input, 'receiver_address', $order->receiver_address);
            $receiverCountryCode  = data_get($input, 'receiver_country_code', $receiverCountryCodeDb);
            $receiverProvinceCode = data_get($input, 'receiver_province_code');
            $receiverDistrictCode = data_get($input, 'receiver_district_code');
            $receiverWardCode     = data_get($input, 'receiver_ward_code');
            $receiverPostalCode   = data_get($input, 'receiver_postal_code', $order->receiver_postal_code);

            $status       = data_get($input, 'status', $order->status);
            $cancelReason = data_get($input, 'cancel_reason', $order->cancel_reason);

            $validator = new UpdateOrderApiValidator($order, $input);

            if ($validator->fails()) {
                return $this->response()->error($validator);
            }

            $locations = $this->getReceiverLocations([$receiverCountryCode, $receiverProvinceCode, $receiverDistrictCode, $receiverWardCode]);

            $orderAmount = $order->order_amount;

            $itemSkus = [];

            $skus = data_get($input, "skus", []);

            // dd($skus);
            if ($skus) {
                $orderAmount = 0;
                foreach ($skus as $sku) {

                    $skuId          = data_get($sku, 'id');
                    $skuCode        = data_get($sku, 'code', '');
                    $price          = data_get($sku, 'price');
                    $quantity       = data_get($sku, 'quantity');
                    $totalAmountSku = (float)$price * (int)$quantity;
                    $orderAmount    += $totalAmountSku;

                    $discountAmountSku = (float)data_get($sku, 'discount_amount');

                    // Check Sku Đã tồn tại trên hệ thống chưa
                    if ($skuCode) {
                        $skuCheck = Sku::select('skus.*')
                        ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                        ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                        ->where(function ($query) use ($skuCode) {
                            return $query->where('store_skus.code', $skuCode)
                                ->orWhere('skus.code', $skuCode);
                        })
                        ->where(function ($query) use ($merchant) {
                            return $query->where('skus.merchant_id', $merchant->id)
                                ->orWhere('product_merchants.merchant_id', $merchant->id);
                        })
                        ->where('skus.status', Sku::STATUS_ON_SELL)
                        ->first();
                        $skuData  = $skuCheck;
                    } else {
                        $skuData = Sku::find($skuId);
                    }


                    if ($skuData) {
                        $itemSkus[] = [
                            'id' => $skuData->id,
                            'code' => $skuData->code,
                            'discount_amount' => $discountAmountSku,
                            'price' => $price,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            $totalAmount    = (float)data_get($input, 'total_amount', $order->total_amount);
            $discountAmount = (float)data_get($input, 'discount_amount', $order->discount_amount);

            $dataResource = new DataResource();

            $dataResource->order_amount       = $orderAmount;
            $dataResource->discount_amount    = $discountAmount;
            $dataResource->total_amount       = $totalAmount;
            $dataResource->status             = $status;
            $dataResource->cancelReason       = $cancelReason;
            $dataResource->description        = data_get($input, 'description');
            $dataResource->receiverPostalCode = data_get($input, 'receiver_postal_code');
            $dataResource->receiver           = [
                'name' => $receiverName,
                'phone' => $receiverPhone,
                'address' => $receiverAddress,
                'country_id' => data_get($locations, "country.id"),
                'province_id' => data_get($locations, "province.id"),
                'district_id' => data_get($locations, "district.id"),
                'ward_id' => data_get($locations, "ward.id"),
                'postal_code' => $receiverPostalCode,
            ];
            $dataResource->items              = $itemSkus;

            // dd($dataResource);

            $orderUpdated = (new UpdateOrders($order, $dataResource))->handle();

            $fractal    = new FractalManager();
            $resource   = new FractalItem($orderUpdated, new OrderTransformer);
            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $dataReturn;
    }

    /**
     * Get Receiver Locations
     *
     * @param array $locationCodes
     * @return array
     */
    protected function getReceiverLocations(array $locationCodes)
    {
        $dataReturn = [
            'country' => null,
            'province' => null,
            'district' => null,
            'ward' => null,
        ];

        $locations = Location::whereIn('code', $locationCodes)->get();
        if ($locations) {
            foreach ($locations as $location) {
                switch ($location->type) {
                    case Location::TYPE_COUNTRY:
                        $dataReturn['country'] = $location->toArray();
                        break;

                    case Location::TYPE_PROVINCE:
                        $dataReturn['province'] = $location->toArray();
                        break;

                    case Location::TYPE_DISTRICT:
                        $dataReturn['district'] = $location->toArray();
                        break;

                    case Location::TYPE_WARD:
                        $dataReturn['ward'] = $location->toArray();
                        break;

                    default:
                        # code...
                        break;
                }
            }
        }

        return $dataReturn;

    }

    public function cancel($orderId)
    {
        $merchantCode   = data_get($this->request()->all(), 'merchant_code');
        $merchant = Merchant::query()->where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        if (!$merchant) {
            return $this->response()->error('merchant_not_found', [
                'message' => sprintf('Merchant %s not found in tenant %s', $merchantCode, $this->user->tenant->code)
            ]);
        }

        /* @var Order $order */
        $order = Order::query()->where('id', $orderId)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (!$order) {
            return $this->response()->error('order_not_found', [
                'message' => sprintf('Order %s not found in merchant %s', $orderId, $merchantCode)
            ]);
        }

        $input = $this->request()->only(['cancel_note', 'cancel_reason']);
        $validator = new CancelOrderValidator($order, $this->request()->user(), $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order->cancel_reason = trim($input['cancel_reason']);
        $order->cancel_note   = (isset($input['cancel_note'])) ? trim($input['cancel_note']) : '';
        $order->changeStatus(Order::STATUS_CANCELED, $this->request()->user());

        return $this->response()->success(compact('order'));
    }

    /**
     * Make Order Code Ramdom
     *
     * @return void
     */
    protected function makeCode()
    {
        $lastOrder = Order::orderBy('created_at', 'desc')->first();
        return Marketplace::CODE_VELAONE . '-' . time() . $lastOrder->id;
    }
}
