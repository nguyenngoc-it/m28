<?php

namespace Modules\Order\Commands;

use Carbon\Carbon;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Models\OrderSkuCombo;
use Modules\Order\Models\OrderTransaction;
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
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderSkuComboSku;
use Modules\Order\Resource\Data3rdResource;
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
class CreateOrderFrom3rdPartner
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
        $this->logger = LogService::logger("{$channel}-sync-order", [
            'context' => ['shop_id' => $this->store->id, 'store' => $this->store->id, 'item_id' => $this->paramsTransform['id']],
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
         * Tạo đơn
         */
        $this->logger->info('sync-order-inputs', $this->paramsTransform);
        $order = $this->makeOrder();

        // Xoá log đơn tạo bị lỗi nếu có
        Service::invalidOrder()->remove($this->paramsTransform['marketplace_code'], $order);

        // Nếu là đơn tạo mới
        if ($order->wasRecentlyCreated) {
            (new OrderCreated($order, $order->status))->queue();
        } else {
            $this->updateOrderStatus($order, $this->paramsTransform['status']);
        }

        return $order;
    }

    /**
     * Transform data from 3rd partner resource
     * @param Data3rdResource $dataResource data from 3rd partner resource
     * @return array $dataTransform
     */
    protected function transformData(Data3rdResource $dataResource)
    {
        $merchantId  = $dataResource->merchant_id ? $dataResource->merchant_id : $this->store->merchant->id;
        $warehouseId = $dataResource->warehouse_id ? $dataResource->warehouse_id : $this->store->warehouse_id;
        $creatorId   = $dataResource->creator_id ? $dataResource->creator_id : $this->creator->id;

        $dataTransform = [
            "payment" => [
                'payment_type' => data_get($dataResource->payment, 'payment_type'),
                'payment_time' => data_get($dataResource->payment, 'payment_time'),
                'payment_note' => data_get($dataResource->payment, 'payment_note'),
                'payment_method' => data_get($dataResource->payment, 'payment_method'),
                'payment_amount' => data_get($dataResource->payment, 'payment_amount'),
                'bank_account' => data_get($dataResource->payment, 'bank_account'),
                'bank_name' => data_get($dataResource->payment, 'bank_name'),
                'standard_code' => data_get($dataResource->payment, 'standard_code')
            ],
            "receiver" => [
                'name' => data_get($dataResource->receiver, 'name'),
                'phone' => data_get($dataResource->receiver, 'phone'),
                'address' => data_get($dataResource->receiver, 'address'),
                'country_id' => data_get($dataResource->receiver, 'country_id'),
                'province_id' => data_get($dataResource->receiver, 'province_id'),
                'district_id' => data_get($dataResource->receiver, 'district_id'),
                'ward_id' => data_get($dataResource->receiver, 'ward_id'),
                'postal_code' => data_get($dataResource->receiver, 'postal_code'),
            ],
            "creator_id" => $creatorId,
            "tenant_id" => $this->store->merchant->tenant_id,
            "merchant_id" => (int)$merchantId,
            "warehouse_id" => (int)$warehouseId,
            "store_id" => $this->store->id,
            "marketplace_store_id" => $this->store->marketplace_store_id,
            "marketplace_code" => $dataResource->marketplace_code,
            "id" => $dataResource->id,
            "code" => (string)$dataResource->code,
            "ref_code" => $dataResource->refCode,
            "campaign" => $dataResource->campaign,
            "order_amount" => $dataResource->order_amount,
            "shipping_amount" => $dataResource->shipping_amount,
            "discount_amount" => $dataResource->discount_amount,
            "total_amount" => $dataResource->total_amount,
            "currency_id" => $dataResource->currency_id,
            "freight_bill" => $dataResource->freight_bill,
            "intended_delivery_at" => $dataResource->intended_delivery_at,
            'created_at_origin' => $dataResource->created_at_origin,
            "using_cod" => $dataResource->using_cod,
            "status" => $dataResource->status,
            "shipping_partner_id" => $dataResource->shipping_partner_id,
            "description" => $dataResource->description,
            "shipping_partner" => [
                'id' => data_get($dataResource->shipping_partner, 'id'),
                'code' => data_get($dataResource->shipping_partner, 'code'),
                'name' => data_get($dataResource->shipping_partner, 'name'),
                'provider' => data_get($dataResource->shipping_partner, 'provider'),
            ],
            'items' => $dataResource->items,
            'itemCombos' => $dataResource->itemCombos,
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
        $this->validateDataTransform($transformData);

        $usingCod        = data_get($transformData, 'using_cod');
        $payment         = data_get($transformData, 'payment');
        $distCountAmount = data_get($transformData, 'discount_amount');
        $totalAmount     = data_get($transformData, 'total_amount');
        if ($usingCod) {
            $paidAmount  = 0;
            $debitAmount = $totalAmount;
            $paymentType = Order::PAYMENT_TYPE_COD;
        } else {
            $paidAmount  = $totalAmount;
            $debitAmount = 0;
            $paymentType = Order::PAYMENT_TYPE_ADVANCE_PAYMENT;
        }
        if ($payment) {
            $paymentType = data_get($transformData, 'payment.payment_type');
            $paidAmount  = data_get($transformData, 'payment.payment_amount');
        }

        $totalAmount     = floatval($totalAmount);
        $paidAmount      = floatval($paidAmount);
        $distCountAmount = floatval($distCountAmount);

        $shippingPartnerId = data_get($transformData, 'shipping_partner_id', 0);
        $shippingPartner   = $this->makeShippingPartner();
        if ($shippingPartner instanceof ShippingPartner) {
            $shippingPartnerId = $shippingPartner->id;
        }

        $receiverCountryId = 0;
        if (data_get($transformData, 'receiver.country_id')) {
            $receiverCountryId = data_get($transformData, 'receiver.country_id');
        }

        $receiverCountry = $this->getCountryOrder($receiverCountryId);

        $currencyId = data_get($transformData, 'currency_id');
        if (!$currencyId) {
            $currencyId = $receiverCountry->currency_id;
        }
        $intendedDeliveryAt = data_get($transformData, 'intended_delivery_at');

        $totalAmount = (double) $totalAmount;
        $paidAmount = (double) $paidAmount;
        $distCountAmount = (double) $distCountAmount;

        $dataCommon = [
            "receiver_name" => data_get($transformData, 'receiver.name'),
            "receiver_phone" => data_get($transformData, 'receiver.phone'),
            "receiver_address" => data_get($transformData, 'receiver.address'),
            "tenant_id" => data_get($transformData, 'tenant_id'),
            // "merchant_id" => data_get($transformData, 'merchant_id'),
            "warehouse_id" => data_get($transformData, 'warehouse_id'),
            // "creator_id" => data_get($transformData, 'creator_id'),
            "store_id" => data_get($transformData, 'store_id'),
            "marketplace_code" => data_get($transformData, 'marketplace_code'),
            "marketplace_store_id" => data_get($transformData, 'marketplace_store_id'),
            "code" => $this->makeCode(),
            "ref_code" => data_get($transformData, 'ref_code'),
            "order_amount" => data_get($transformData, 'order_amount'),
            "shipping_amount" => data_get($transformData, 'shipping_amount'),
            "discount_amount" => $distCountAmount,
            "total_amount" => $totalAmount - $distCountAmount,
            "receiver_country_id" => $receiverCountryId,
            "receiver_province_id" => data_get($transformData, 'receiver.province_id'),
            "receiver_district_id" => data_get($transformData, 'receiver.district_id'),
            "receiver_ward_id" => data_get($transformData, 'receiver.ward_id'),
            "receiver_postal_code" => data_get($transformData, 'receiver.postal_code'),
            "currency_id" => $currencyId,
            "intended_delivery_at" => $intendedDeliveryAt ? Carbon::parse($intendedDeliveryAt) : null,
            'created_at_origin' => Carbon::parse(data_get($transformData, 'created_at_origin')),
            'extra_services' => [],
            "paid_amount" => $paidAmount,
            "debit_amount" => $totalAmount - $paidAmount - $distCountAmount,
            "payment_type" => $paymentType,
            "cod" => $totalAmount - $paidAmount - $distCountAmount,
            "shipping_partner_id" => $shippingPartnerId,
            "description" => data_get($transformData, 'description'),
            "name_store" => $this->store->name ?: $this->store->marketplace_code,
            "payment_time" => data_get($transformData, 'payment.payment_time') ? Service::order()->formatDateTime(data_get($transformData, 'payment.payment_time')) : null,
            "payment_note" => data_get($transformData, 'payment.payment_note'),
            "payment_method" => data_get($transformData, 'payment.payment_method'),
            "standard_code" => data_get($transformData, 'payment.standard_code'),
        ];

        $freightBill = data_get($transformData, 'freight_bill');
        if ($freightBill) {
            $dataCommon['freight_bill'] = $freightBill;
        }

        $campaign = data_get($transformData, 'campaign', NULL);
        if (!is_null($campaign)) {
            $dataCommon['campaign'] = $campaign;
        }

        // Kiểm tra xem order có phải update
        $query      = [
            'code' => $dataCommon['code'],
            'store_id' => $dataCommon['store_id'],
            'marketplace_code' => $dataCommon['marketplace_code'],
        ];
        $orderCheck = Order::where($query)->first();
        if (!$orderCheck) {
            $dataCommon['merchant_id'] = data_get($transformData, 'merchant_id');
            $dataCommon['creator_id']  = data_get($transformData, 'creator_id');
        }

        return $dataCommon;
    }

    /**
     * Make Order Code With Logic For 3rd Resource
     *
     * @return string $code
     */
    protected function makeCode()
    {
        $code = $this->paramsTransform['code'];
        if ($this->store->marketplace_code == Marketplace::CODE_KIOTVIET) {
            $code = $this->paramsTransform['code'] . '_' . $this->paramsTransform['id'];
        }
        return $code;
    }

    /**
     * Make Order From 3rd Resource
     *
     * @return Order $orderData
     */
    protected function makeOrder()
    {
        return DB::transaction(function () {

            // Tạo dữ liệu chung, mặc định phải có cho việc tạo bản ghi Product
            $dataCommon = $this->makeCommonData($this->paramsTransform);

            $query = [
                'code' => $dataCommon['code'],
                'store_id' => $dataCommon['store_id'],
                'marketplace_code' => $dataCommon['marketplace_code'],
            ];

            /**
             * Nếu đơn tạo lần đầu mà không phải chờ chọn kho xuất thì tạo đơn ở trạng thái WAITING_INSPECTION
             * Sau khi kết thúc event tạo đơn sẽ cập nhật trạng thái tương ứng
             */
            $orderCheck = Order::query()->where($query)->first();
            if (!$orderCheck) {
                $dataCommon['status'] = Order::STATUS_WAITING_INSPECTION;
            }

            $this->logger->info('data-commo-order-insert', $dataCommon);

            // Tạo bản ghi Order
            $order       = Order::updateOrCreate($query, $dataCommon);
            $paidAmount  = $order->paid_amount;
            $debitAmount = $order->debit_amount;
            // Tạo các bản ghi quan hệ
            $this->makeOrderRelation($order);
            // Lấy paid_amount trong bảng order chứ ko + amount trong order_transactions
            $order->paid_amount  = $paidAmount;
            $order->debit_amount = $debitAmount;
            $order->save();
            return $order;
        });
    }

    /**
     * Make Data Order's Relationship
     *
     * @param Order $order
     * @return void
     */
    protected function makeOrderRelation(Order $order)
    {
        $this->makeFreightBillCode($order);
        $this->makeOrderSkuCombo($order);
        $this->makeOrderTransaction($order);
    }

    protected function makeOrderTransaction(Order $order)
    {
        $payment = $this->paramsTransform['payment'];
        (new CreateOrderTransaction($order, $payment, $this->creator))->handle();
    }

    protected function makeOrderSkuCombo(Order $order)
    {
        // Danh sách Sku của Order
        $items      = $this->paramsTransform['items'];
        $itemCombos = $this->paramsTransform['itemCombos'];

        if ($itemCombos) {
            $items = $itemCombos;
        }

        foreach ($items as $key => $item) {

            $itemCode           = data_get($item, 'code');
            $itemQuantity       = data_get($item, 'quantity', 0);
            $itemPrice          = data_get($item, 'price', 0);
            $itemDiscountAmount = data_get($item, 'discount_amount', 0);

            if ($itemCode) {
                $skuCombo = SkuCombo::query()->where('code', $itemCode)->first();
                if ($skuCombo) {
                    // if (!$itemPrice) {
                    //     $itemPrice = $skuCombo->price;
                    // }
                    OrderSkuCombo::updateOrCreate([
                        'order_id' => $order->id,
                        'sku_combo_id' => $skuCombo->id,
                    ], [
                        'quantity' => $itemQuantity,
                        'price' => $itemPrice
                    ]);

                    // unset($this->paramsTransform['items'][$key]);

                    $skus        = $skuCombo->skus;
                    $snapShotSku = [];
                    foreach ($skus as $sku) {
                        $skuId       = 0;
                        $skuIdOrigin = 0;

                        $storeSku = StoreSku::query()
                            ->join('skus', 'store_skus.sku_id', 'skus.id')
                            ->leftJoin('products', 'skus.product_id', 'products.id')
                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'products.id')
                            ->where('store_skus.store_id', $this->paramsTransform['store_id'])
                            ->where('sku_id', $sku->id)
                            ->where('store_skus.code', $itemCode)
                            ->where(function ($query) {
                                return $query->where('skus.merchant_id', $this->paramsTransform['merchant_id'])
                                    ->orWhere('product_merchants.merchant_id', $this->paramsTransform['merchant_id']);
                            })
                            ->first();

                        if ($storeSku) {
                            $skuIdOrigin = $storeSku->sku_id_origin;
                        } else {
                            $skuId = $sku->id;
                        }
                        $skuComboSku = SkuComboSku::query()->where('sku_id', $sku->id)
                            ->where('sku_combo_id', $skuCombo->id)->first();
                        /**
                         * snapshot lại sku mỗi lần đồng bộ từ bên thứ 3 về
                         */
                        // $snapShotSku[] = [
                        //     'id' => $sku->id,
                        //     'code' => $sku->code,
                        //     'name' => $sku->name,
                        //     'price' => $sku->price,
                        //     'time' => Carbon::now()->toDateTimeString()
                        // ];
                        // $skuCombo->snap_sku = $snapShotSku;
                        // $skuCombo->save();

                        $this->paramsTransform['items'][] = [
                            'id_origin' => $skuIdOrigin,
                            'sku_id' => $skuId,
                            'code' => $sku->code,
                            'discount_amount' => (double)$itemDiscountAmount,
                            'price' => (int)($sku->retail_price * $itemQuantity),
                            'quantity' => (double)($skuComboSku->quantity * $itemQuantity),
                            'from_sku_combo' => OrderSku::FROM_SKU_COMBO_TRUE
                        ];

                        $dataCreate = [
                            'order_id' => $order->id,
                            'sku_id' => $sku->id,
                            'sku_combo_id' => $skuCombo->id,
                            'quantity' => (int)($skuComboSku->quantity * $itemQuantity),
                            'price' => (double)$sku->retail_price,
                        ];

                        OrderSkuComboSku::create($dataCreate);
                    }

                    // unset($this->paramsTransform['items'][$key]);
                }
            }
        }

        // dd($this->paramsTransform['items']);

        $this->makeOrderSkus($order);
    }

    /**
     * Make Order Sku Record
     *
     * @param Order $order
     * @return void
     */
    protected function makeOrderSkus(Order $order)
    {
        // Danh sách Sku của Order
        $items = $this->paramsTransform['items'];
        /**
         * $item [
         *    'id_origin'       => Sku Id Origin From 3rd Partner
         *    'code'            => Sku Code,
         *    'discount_amount' => Order Sku Item Discount Amount,
         *    'price'           => Base Price Of Sku Item,
         *    'quantity'        => Quantity Of Sku Item,
         * ]
         */

        foreach ($items as $item) {

            $itemId             = data_get($item, 'sku_id');
            $itemIdOrigin       = data_get($item, 'id_origin');
            $itemCode           = data_get($item, 'code');
            $itemDiscountAmount = data_get($item, 'discount_amount');
            $itemPrice          = data_get($item, 'price');
            $itemQuantity       = data_get($item, 'quantity');
            $itemFromSkuCombo   = data_get($item, 'from_sku_combo', false);

            // Get Sku data
            $sku = null;
            if ($itemId) {
                $sku = Sku::find($itemId);
            } else {
                // $storeSku = StoreSku::query()
                //     ->join('skus', 'store_skus.sku_id', 'skus.id')
                //     ->leftJoin('products', 'skus.product_id', 'products.id')
                //     ->leftJoin('product_merchants', 'product_merchants.product_id', 'products.id')
                //     ->where('store_skus.store_id', $this->paramsTransform['store_id'])
                //     ->where('store_skus.sku_id_origin', $itemIdOrigin)
                //     ->where('store_skus.code', $itemCode)
                //     ->where(function($query){
                //         return $query->where('skus.merchant_id', $this->paramsTransform['merchant_id'])
                //                      ->orWhere('product_merchants.merchant_id', $this->paramsTransform['merchant_id']);
                //     })
                //     ->first();

                $sku = Sku::select('skus.*')
                    ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                    ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                    ->where(function ($query) use ($itemCode) {
                        return $query->where('store_skus.code', $itemCode)
                            ->orWhere('skus.code', $itemCode);
                    })
                    ->where(function ($query) {
                        return $query->where('skus.merchant_id', $this->paramsTransform['merchant_id'])
                            ->orWhere('product_merchants.merchant_id', $this->paramsTransform['merchant_id']);
                    })
                    ->first();
                // if ($storeSku instanceof StoreSku) {
                //     $sku = $storeSku->sku;
                // }

                // dd($sku, $this->paramsTransform['merchant_id'], $itemCode);
            }

            if ($sku instanceof Sku) {
                $totalAmount    = (float)$itemPrice * (int)$itemQuantity;
                $discountAmount = (float)$itemDiscountAmount;
                $orderAmount    = max(($totalAmount - $discountAmount), 0);

                $data  = [
                    'tenant_id' => $order->tenant_id,
                    'sku_id' => $sku->id,
                    'tax' => 0,
                    'price' => $itemPrice,
                    'quantity' => $itemQuantity,
                    'order_amount' => $orderAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'from_sku_combo' => $itemFromSkuCombo,
                ];
                $query = [
                    'order_id' => $order->id,
                    'sku_id' => $sku->id,
                    'from_sku_combo' => $itemFromSkuCombo,
                ];

                $order->orderSkus()->updateOrCreate($query, $data);
            }
        }
    }


    /**
     * Make Freight Bill Code
     *
     * @param Order $order
     * @return void
     */
    protected function makeFreightBillCode(Order $order)
    {
        if ($order->freight_bill) {
            if ($order->marketplace_code == Marketplace::CODE_KIOTVIET) {
                dispatch(new SyncKiotVietFreightBillJob($this->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_LAZADA) {
                dispatch(new SyncLazadaFreightBillJob($this->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_TIKTOKSHOP) {
                dispatch(new SyncTikTokShopFreightBillJob($this->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_SHOPBASE) {
                dispatch(new SyncShopBaseUsFreightBillJob($this->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_SAPO) {
                dispatch(new SyncSapoFreightBillJob($this->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_SHOPEE) {
                dispatch(new SyncShopeeFreightBillJob($this->store->marketplace_store_id, $order->code, $order->freight_bill));
            }
        }
    }

    /**
     * Make Shipping Partner From 3rd Resource
     *
     * @return ShippingPartner|null $shippingPartner
     */
    protected function makeShippingPartner()
    {
        $shippingPartner = $this->paramsTransform['shipping_partner'];
        $logistic        = [
            'logistic_id' => $shippingPartner['id'],
            'code' => $shippingPartner['code'],
            'logistic_name' => $shippingPartner['name'],
            'provider' => !empty($shippingPartner['provider']) ? $shippingPartner['provider'] : $this->paramsTransform['marketplace_code']
        ];

        if ($logistic['logistic_id']) {
            $code  = (!empty($logistic['code'])) ? $logistic['code'] : $this->paramsTransform['marketplace_code'] . $logistic['logistic_id'];
            $query = [
                'tenant_id' => $this->paramsTransform['tenant_id'],
                'provider' => $logistic['provider'],
                'code' => $code,
            ];

            $data = [
                'name' => $logistic['logistic_name'],
                'settings' => Arr::only($logistic, ['logistic_id']),
                'alias' => [$code, strtolower($code)],
            ];

            /** @var ShippingPartner $shippingPartner */
            $shippingPartner = ShippingPartner::query()->firstOrCreate($query, $data);
        } else {
            $shippingPartner = null;
        }

        return $shippingPartner;
    }

    /**
     * @param Order $order
     * @param string $status
     * @return bool
     * @throws WorkflowException
     */
    protected function updateOrderStatus(Order $order, string $status)
    {
        if (!$status) {
            return false;
        }

        // Nếu đơn đang ở trạng thái chờ nhặt hàng và đã có mã vận đơn
        // thì không cho thay đổi trạng thái đơn về chờ xử lý.
        if (($order->status == Order::STATUS_WAITING_PICKING && $status == Order::STATUS_WAITING_PROCESSING && $order->freight_bill)
            || !$order->canChangeStatus($status)) {
            $this->logger->error('CANT_CHANGE_STATUS', [
                'order' => $order->code,
                'status' => $order->status,
                'next_status' => $status,
            ]);

            return false;
        }
        $order->changeStatus($status, $this->creator);

        return true;
    }

    /**
     * Get Defaut Country For Order
     *
     * @return Location|mixed $receiverCountry
     */
    protected function getCountryOrder($receiverCountryId)
    {
        if ($receiverCountryId) {
            $country = Location::find($receiverCountryId);
            if ($country) {
                return $country;
            }
        }
        return Location::query()->firstWhere('code', Location::COUNTRY_VIETNAM);
    }

    /**
     * Validate Data Transform
     * @param array $dataTransform
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateDataTransform(array $dataTransform)
    {
        $validation = new CreateOrderFrom3rdPartnerValidator($dataTransform);
        if ($validation->fails()) {
            $this->logger->error('VALIDATE_DATA_COMMON_FAILS', $validation->errors()->all());
            throw (new \Illuminate\Validation\ValidationException($validation));
        }
    }
}
