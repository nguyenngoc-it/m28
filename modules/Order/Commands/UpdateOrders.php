<?php
namespace Modules\Order\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\Order\Resource\DataResource;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Modules\Location\Models\Location;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Events\OrderSkusChanged;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\OrderEvent;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\User\Models\User;

class UpdateOrders
{

    /**
     * @var Order
     */
    protected $order;

     /**
     * @var User
     */
    protected $creator;

     /**
     * @var DataResource
     */
    protected $dataResource;

     /**
     * @var array
     */
    protected $paramsTransform;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Order $order, DataResource $dataResource)
    {
        $this->order           = $order;
        $this->creator         = Auth::user();
        $this->dataResource    = $dataResource;
        $this->paramsTransform = $this->transformData($dataResource);
        // Lưu log update theo resource
        $this->logger = LogService::logger("update-order", [
            'context' => ['order_id' => $order->id],
        ]);
    }

    public function handle()
    {
        /**
         * Update đơn
         */
        $this->logger->info('update-order-inputs', $this->paramsTransform);

        $dataOrderOriginal = $this->order->getOriginal();

        $order = $this->updateOrder();
        if ($this->order->wasChanged()) {
            $changedAtts = $this->order->getChanges();
            if (isset($changedAtts['updated_at'])) unset($changedAtts['updated_at']);

            // Transform data logger for locations
            $dataCompare = Arr::only($dataOrderOriginal, array_keys($changedAtts));

            $this->makeLogOrderAttribute($dataCompare, $changedAtts);
        }

        return $order;
    }

    /**
     * Transform data from resource
     * @param DataResource $dataResource data from resource
     * @return array $dataTransform
     */
    protected function transformData(DataResource $dataResource)
    {
        $dataTransform = [
            "receiver" => [
                'name'        => data_get($dataResource->receiver, 'name'),
                'phone'       => data_get($dataResource->receiver, 'phone'),
                'address'     => data_get($dataResource->receiver, 'address'),
                'province_id' => data_get($dataResource->receiver, 'province_id', 0),
                'district_id' => data_get($dataResource->receiver, 'district_id', 0),
                'ward_id'     => data_get($dataResource->receiver, 'ward_id', 0),
            ],
            "order_amount"         => $dataResource->order_amount,
            "discount_amount"      => $dataResource->discount_amount,
            "total_amount"         => $dataResource->total_amount,
            "description"          => $dataResource->description,
            "status"               => (string) $dataResource->status,
            "cancel_reason"        => $dataResource->cancelReason,
            'items'                => $dataResource->items,
            'receiver_postal_code' => $dataResource->receiverPostalCode
        ];

        return $dataTransform;
    }

    /**
     * Make Common Data For Make Order Record
     *
     * @param array $transformData
     * @return array $dataCommon
     */
    protected function makeCommonData(array $transformData)
    {
        $receiverProvinceId = data_get($transformData, 'receiver.province_id', 0);
        $receiverDistrictId = data_get($transformData, 'receiver.district_id', 0);
        $receiverWardId     = data_get($transformData, 'receiver.ward_id', 0);


        $dataCommon = [
            "receiver_name"        => data_get($transformData, 'receiver.name'),
            "receiver_phone"       => data_get($transformData, 'receiver.phone'),
            "receiver_address"     => data_get($transformData, 'receiver.address'),
            "order_amount"         => data_get($transformData, 'order_amount'),
            "discount_amount"      => data_get($transformData, 'discount_amount'),
            "total_amount"         => data_get($transformData, 'total_amount'),
            "description"          => data_get($transformData, 'description'),
            "receiver_postal_code" => data_get($transformData, 'receiver_postal_code'),
            "receiver_province_id" => $receiverProvinceId,
            "receiver_district_id" => $receiverDistrictId,
            "receiver_ward_id"     => $receiverWardId,
        ];

        return $dataCommon;
    }

     /**
     * Update Order From Resource
     *
     * @return Order $orderData
     */
    protected function updateOrder()
    {
        $orderUpdated = DB::transaction(function () {

            // Tạo dữ liệu chung, mặc định phải có cho việc tạo bản ghi Product
            $dataCommon = $this->makeCommonData($this->paramsTransform);

            // Update Order
            $this->order->update($dataCommon);

            // Update bản ghi quan hệ
            $this->updateOrderRelation($this->order);

            $this->updateOrderStatus($this->order, $this->paramsTransform['status']);

            return $this->order;
        });

        return $orderUpdated;
    }

    /**
     * Update Data Order's Relationship
     *
     * @param Order $order
     * @return void
     */
    protected function updateOrderRelation(Order $order)
    {
        $this->updateOrderSkus($order);
    }

    /**
     * Update Order Sku Record
     *
     * @param Order $order
     * @return void
     */
    protected function updateOrderSkus(Order $order)
    {
        // Danh sách Sku của Order
        $items = $this->paramsTransform['items'];

        $syncSkusData = [];

        if ($items) {
            foreach ($items as $item) {

                $itemId             = data_get($item, 'id');
                $itemDiscountAmount = data_get($item, 'discount_amount');
                $itemPrice          = data_get($item, 'price');
                $itemQuantity       = data_get($item, 'quantity');
    
                // Get Sku data
                $sku = Sku::query()->where('id', $itemId)
                    ->first();
    
                if ($sku instanceof Sku) {
    
                    $totalAmount    = (float)$itemPrice * (int)$itemQuantity;
                    $discountAmount = (float)$itemDiscountAmount;
                    $orderAmount    = $totalAmount - $discountAmount;
    
                    $data  = [
                        'order_id'        => $order->id,
                        'tenant_id'       => $order->tenant_id,
                        'sku_id'          => $sku->id,
                        'price'           => $itemPrice,
                        'quantity'        => $itemQuantity,
                        'order_amount'    => $orderAmount,
                        'discount_amount' => $discountAmount,
                        'total_amount'    => $totalAmount,
                    ];
    
                    $syncSkusData[$sku->id] = $data;
                }
            }
    
            $this->calculateSkuChangedInfo($syncSkusData);
        }

    }

    /**
     * Transform data locations log info
     *
     * @param string $key
     * @param string $value
     * @return array
     */
    protected function transformLocationLogInfo($key, $value)
    {
        $keyReturn    = '';
        $dataReturn   = [];
        switch ($key) {
            case 'receiver_province_id':
                $keyReturn    = 'receiver_province';
                break;

            case 'receiver_district_id':
                $keyReturn    = 'receiver_district';
                break;

            case 'receiver_ward_id':
                $keyReturn    = 'receiver_ward';
                break;

            default:
                $keyReturn    = '';
                $typeLocation = '';
                break;
        }
        // Get Location
        if ($keyReturn) {
            $location = Location::find(intval($value));
            if ($location) {
                $dataReturn[$key] = [
                    'new_key'   => $keyReturn,
                    'new_value' => $location->label,
                ];
            }
        }

        return $dataReturn;
    }

    /**
     * Make Log For Order Attribute Changed
     *
     * @param array $dataBefore Dữ liệu trước khi thay đổi
     * @param array $dataAfter Dữ liệu sau khi thay đổi
     * @return void
     */
    protected function makeLogOrderAttribute(array $dataBefore, array $dataAfter)
    {
        foreach ($dataBefore as $key => $value) {
            $dataTransform = $this->transformLocationLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataBefore[$key]);
                $dataBefore[$dataTransform[$key]['new_key']] = $dataTransform[$key]['new_value'];
            }
        }

        foreach ($dataAfter as $key => $value) {
            $dataTransform = $this->transformLocationLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataAfter[$key]);
                $dataAfter[$dataTransform[$key]['new_key']] = $dataTransform[$key]['new_value'];
            }
        }

        (new OrderAttributesChanged($this->order, $this->creator, $dataBefore, $dataAfter))->queue();
    }

    public function calculateSkuChangedInfo(array $syncSkusData)
    {
        /**
         * Insert order_skus
         */
        $dataOrderSku   = $this->order->orderSkus;
        $dataSync       = $this->order->skus()->sync($syncSkusData);
        $dataAttached   = data_get($dataSync, 'attached', []);
        $dataDetached   = data_get($dataSync, 'detached', []);
        $dataUpdated    = data_get($dataSync, 'updated', []);
        $autoInspection = false;

        // Sku thêm mới
        if ($dataAttached) {
            foreach ($dataAttached as $skuId) {
                if (isset($syncSkusData[$skuId])) {
                    $syncSkusData[$skuId]['action'] = OrderEvent::ADD_SKUS;
                }
            }
            $autoInspection = true;
        }

        // Sku bị xoá
        if ($dataDetached) {
            foreach ($dataDetached as $skuId) {
                $orderSku = $dataOrderSku->first(function($item) use ($skuId) {
                    return $item->sku_id == $skuId;
                });

                $syncSkusData[$skuId] = [
                    'order_id'        => $orderSku->order_id,
                    'tenant_id'       => $orderSku->order->tenant_id,
                    'sku_id'          => $orderSku->sku_id,
                    'price'           => $orderSku->price,
                    'quantity'        => $orderSku->quantity,
                    'order_amount'    => $orderSku->order_amount,
                    'discount_amount' => $orderSku->discount_amount,
                    'total_amount'    => $orderSku->total_amount,
                    'action'          => OrderEvent::REMOVE_SKUS,
                ];
            }
        }

        // Sku Update
        if ($dataUpdated) {
            foreach ($dataUpdated as $skuId) {
                if (isset($syncSkusData[$skuId])) {
                    $syncSkusData[$skuId]['action'] = OrderEvent::UPDATE_SKUS;
                    // Kiêm tra xem có thay đổi thông tin số lượng hay không
                    $orderSku = $dataOrderSku->first(function($item) use ($skuId) {
                        return $item->sku_id == $skuId;
                    });
                    if ($orderSku->quantity != $syncSkusData[$skuId]['quantity']) {
                        $autoInspection = true;
                    }
                }
            }
        }

        // Tự động chọn lại kho xuất cho đơn khi thay đổi thông tin Sku
        if ($autoInspection || !empty($dataDetached)) {
            // Huỷ chọn kho xuất
            Service::order()->removeStockOrder($this->order, $this->creator);
            // Chọn lại kho xuất
            if ($autoInspection) {
                Service::order()->autoInspection($this->order, $this->creator);
            }
        }
        // dd($syncSkusData);

        (new OrderSkusChanged($this->order, $this->creator, $syncSkusData))->queue();
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

        // Nếu huỷ đơn thì update thông tin lý do huỷ
        if ($status == Order::STATUS_CANCELED) {
            $order->cancel_reason = $this->paramsTransform['cancel_reason'];
            $order->save();
        }

        return true;
    }
}
