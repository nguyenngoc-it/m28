<?php

namespace Modules\Shopee\Commands;

use App\Base\CommandBus;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Transformer\TransformerService;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;
use Modules\Order\Resource\Data3rdResource;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Shopee\Jobs\SyncShopeeFreightBillJob;
use Modules\Shopee\Jobs\WaitForOrderShippingPartnerJob;
use Modules\Shopee\Services\Shopee;
use Modules\Shopee\Validators\SyncShopeeOrderValidator;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;
use Psr\Log\LoggerInterface;

class SyncShopeeOrder extends CommandBus
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncShopeeOrder constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của shopee api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input, User $creator)
    {
        $this->store = $store;
        $this->input = $input;
        $this->input['items'] = $this->makeInputItems($input['item_list']);
        $this->creator = $creator;

        $this->logger = LogService::logger('shopee', [
            'context' => ['shop_id' => $store->marketplace_store_id, 'order_code' => $input['order_sn']],
        ]);
    }

    /**
     * @param $items
     * @return mixed
     */
    protected function makeInputItems($items)
    {
        foreach ($items as $i => $item) {
            if(empty($item['model_id'])) {
                $items[$i]['model_id'] = $item['item_id'];
            }
            if(empty($item['model_name'])) {
                $items[$i]['model_name'] = $item['item_name'];
            }
            if(empty($item['model_sku'])) {
                $items[$i]['model_sku'] = $item['item_sku'];
            }

            if(empty($items[$i]['model_sku'])) {
                $items[$i]['model_sku'] = $items[$i]['model_id'];
            }
        }

        return $items;
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws WorkflowException
     */
    public function handle()
    {
        $validator = new SyncShopeeOrderValidator($this->store, $this->input);

        if ($validator->fails()) {
            $this->makeInvalidOrder($validator);
            throw new ValidationException($validator);
        }

        return $this->syncOrder();
    }

    /**
     * @return Order|object
     * @throws WorkflowException
     */
    protected function syncOrder()
    {
        $this->logger->info('SYNC_ORDER', $this->input);

        $order = Service::shopee()->findOrder($this->store->id, $this->input['order_sn']);
        $order = $order ? $this->updateOrder($order) : $this->createOrder();

        if (empty($this->input['shipping_carrier']) && $order) {
            dispatch(new WaitForOrderShippingPartnerJob($this->store->id, $this->input['order_sn']));
        }

        return $order;
    }

    /**
     * @return Order|object|null
     */
    protected function findOrder()
    {
        return Order::query()->firstWhere([
            'merchant_id' => $this->store->merchant_id,
            'code' => $this->input['order_sn'],
        ]);
    }

    /**
     * @param Order $order
     * @return Order
     * @throws WorkflowException
     */
    protected function updateOrder(Order $order)
    {
        $this->updateOrderShippingPartner($order);
        $this->updateOrderStatus($order);
        $this->updateOrderInfo($order);

        return $order;
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function updateOrderShippingPartner(Order $order)
    {
        if (!$shippingPartner = $this->makeShippingPartner()) {
            return false;
        }

        return Service::shopee()->updateOrderShippingPartner($order, $shippingPartner, $this->creator);
    }

    /**
     * @param Order $order
     * @return bool
     * @throws WorkflowException
     */
    protected function updateOrderStatus(Order $order)
    {
        $status = Arr::get([
            Shopee::ORDER_STATUS_CANCELLED => Order::STATUS_CANCELED,
            Shopee::ORDER_STATUS_COMPLETED => Order::STATUS_DELIVERED,
        ], $this->input['order_status']);

        if (!$status) {
            return false;
        }

        if (!$order->canChangeStatus($status)) {
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

    protected function updateOrderInfo(Order $order)
    {
        $data = [];

        $intendedDeliveryAt = $order->intended_delivery_at ? $order->intended_delivery_at->timestamp : 0;
        if ($this->input['ship_by_date'] && (int)$this->input['ship_by_date'] !== $intendedDeliveryAt) {
            $data['intended_delivery_at'] = Carbon::createFromTimestamp((int)$this->input['ship_by_date']);
        }

        if (!empty($data)) {
            $order->update($data);
        }
    }

    /**
     * @return Builder|Model|null
     */
    protected function getCountry()
    {
        if ($this->store->merchant->location instanceof Location) {
            return $this->store->merchant->location;
        }

        $countryCodeMapping = [
            Location::COUNTRY_CODE_VIETNAM => Location::REGION_VIETNAM
        ];
        $countryCodeShopee = $this->input['region'];
        $countryCodeM28 = isset($countryCodeMapping[$countryCodeShopee]) ? $countryCodeMapping[$countryCodeShopee] : '';

        return ($countryCodeM28) ? Location::query()->firstWhere('code', $countryCodeM28) : null;
    }

    /**
     * @return Order|void
     */
    protected function createOrder()
    {
        $dataResource = $this->makeOrderData3rdResource();
        if($dataResource->status == Order::STATUS_CANCELED) {
            //có tường hợp đơn shopee chưa thanh toán, sẽ không tạo bên m28, sau đó khách k mua nữa hủy đi thì cũng sẽ k tạo trên m28
            $this->logger->info('NOT_SYNC_ORDER_CANCELED', $this->input);
            return;
        }
        $dataResource->status = Order::STATUS_WAITING_INSPECTION; //mặc định tạo đơn shopee sẽ là chờ chọn kho, k map theo trạng thái shopee
        return  Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);
    }

    protected function makeOrderData3rdResource()
    {
        // Make Order
        $dataResource = new Data3rdResource();

        $orderAmount    = $this->makeOrderAmount();
        $shippingAmount = $this->makeShippingAmount();
        $totalAmount    = (float)$this->input['total_amount'];
        $discountAmount = ($orderAmount + $shippingAmount) - $totalAmount;

        $dataResource->receiver             = $this->makeReceiver();
        $dataResource->marketplace_code     = Marketplace::CODE_SHOPEE;
        $dataResource->id                   = data_get($this->input, 'order_sn');
        $dataResource->code                 = data_get($this->input, "order_sn");
        $dataResource->description          = $this->input['message_to_seller'];
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->freight_bill         = isset($this->input['tracking_no']) ? $this->input['tracking_no'] : '';
        $dataResource->intended_delivery_at = ($this->input['ship_by_date']) ?  Carbon::createFromTimestamp((int)$this->input['ship_by_date']) : '';
        $dataResource->created_at_origin    = Carbon::createFromTimestamp($this->input['create_time']);
        $dataResource->using_cod            = $this->input['cod'];
        $dataResource->status               = $this->makeOrderStatus();
        $dataResource->items                = $this->makeOrderItems();
        $dataResource->shipping_partner     = $this->makeShippingPartnerData();

        return $dataResource;
    }

    /**
     * @return array
     */
    protected function makeReceiver()
    {
        $recipientAddress = $this->input['recipient_address'];
        return [
            'name' => data_get($recipientAddress, "name"),
            'phone' => data_get($recipientAddress, "phone"),
            'address' => data_get($recipientAddress, "full_address"),
        ];
    }

    /**
     * @return array
     */
    protected function makeShippingPartnerData()
    {
        $logistic = Arr::get($this->input, 'logistic');
        if(empty($logistic)) {
            return [];
        }
        return [
            'id' => $logistic['logistics_channel_id'],
            'code' => Marketplace::CODE_SHOPEE.'_'.$logistic['logistics_channel_id'],
            'name' => $logistic['logistics_channel_name'],
            'provider' => ShippingPartner::PROVIDER_SHOPEE
        ];;
    }

    /**
     * @return float|int
     */
    protected function makeShippingAmount()
    {
        $shippingAmount = 0;
        if(isset($this->input['actual_shipping_fee'])) {
            $shippingAmount = (float)$this->input['actual_shipping_fee'];
        }

        return $shippingAmount;
    }

    /**
     * @return float|int
     */
    protected function makeOrderAmount()
    {
        $orderAmount = 0;
        foreach ($this->input['items'] as $item) {
            $orderAmount += (float)$item['model_discounted_price'] * (int)$item['model_quantity_purchased'];
        }

        return $orderAmount;
    }

    /**
     * @return mixed
     */
    protected function makeOrderStatus()
    {
        $shopeeStatus = !empty($this->input['order_status']) ? $this->input['order_status'] : Order::STATUS_WAITING_INSPECTION;
        $status       = Arr::get([
            Shopee::ORDER_STATUS_CANCELLED => Order::STATUS_CANCELED,
            Shopee::ORDER_STATUS_COMPLETED => Order::STATUS_DELIVERED,
        ], $shopeeStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }

    /**
     * @return array
     */
    protected function makeOrderItems()
    {
        $items = $this->input['items'];
        $skus  = Service::shopee()->findSkusByVariations($this->store, $items);

        $itemSkus = [];
        foreach ($items as $item) {
            $code     = (!empty($item['model_sku'])) ? trim($item['model_sku']) : $item['model_id'];
            $skuCombo = SkuCombo::query()->where('code', $code)->first();
            if($skuCombo) {
                $skuId    = $item['model_id'];
            } else {
                $sku      = $skus[$item['model_id']];
                $skuId    = $sku->id;
                $itemSkus[$skuId]['sku_id'] = $skuId;
            }

            $quantity = (int)$item['model_quantity_purchased'];
            $price    = (float)$item['model_discounted_price'];

            $itemSkus[$skuId]['id_origin'] = $item['model_id'];
            $itemSkus[$skuId]['code']      = $code;
            $itemSkus[$skuId]['price']     = $price;

            if (!isset($itemSkus[$skuId]['quantity'])) {
                $itemSkus[$skuId]['quantity'] = $quantity;
            } else {
                $itemSkus[$skuId]['quantity'] += $quantity;
            }

            $itemSkus[$skuId]['discount_amount'] = 0;
        }

        return $itemSkus;
    }

    /**
     * @return ShippingPartner|object|null
     */
    protected function makeShippingPartner()
    {
        return ($logistic = Arr::get($this->input, 'logistic'))
            ? Service::shopee()->makeShippingPartner($this->store->tenant_id, $logistic)
            : null;
    }

    /**
     * @param string $shopeeOrderStatus
     * @return bool
     */
    protected function shouldAutoInspection($shopeeOrderStatus)
    {
        return in_array($shopeeOrderStatus, [Shopee::ORDER_STATUS_READY_TO_SHIP, Shopee::ORDER_STATUS_RETRY_SHIP], true);
    }

    /**
     * @param Validator $validator
     * @return InvalidOrder|object|null
     */
    protected function makeInvalidOrder(Validator $validator)
    {
        $errors = TransformerService::transform($validator);

        $invalidOrder = InvalidOrder::query()->updateOrCreate([
            'tenant_id' => $this->store->tenant_id,
            'source' => InvalidOrder::SOURCE_SHOPEE,
            'code' => $this->input['order_sn'],
        ], [
            'payload' => [
                'store_id' => $this->store->id,
                'input' => $this->input,
            ],
            'error_code' => $this->getErrorCode($errors),
            'errors' => $errors,
            'creator_id' => $this->creator->id,
        ]);

        $this->logger->error($invalidOrder->error_code, $invalidOrder->errors);

        return $invalidOrder;
    }

    /**
     * @param array $errors
     * @return string
     */
    protected function getErrorCode(array $errors)
    {
        if (isset($errors['invalid_items'])) {
            return InvalidOrder::ERROR_SKU_UNMAPPED;
        }

        return InvalidOrder::ERROR_TECHNICAL;
    }
}
