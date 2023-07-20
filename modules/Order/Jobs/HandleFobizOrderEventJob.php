<?php /** @noinspection ALL */

namespace Modules\Order\Jobs;

use App\Base\Job;
use Exception;
use Gobiz\Log\LogService;
use Gobiz\Transformer\TransformerService;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Commands\ChangeDeliveryFee;
use Modules\Order\Commands\ChangeShippingPartner;
use Modules\Order\Commands\RelateObjects\InputOrderByFile;
use Modules\Order\Commands\UpdateOrder;
use Modules\Order\Commands\UpdateOrderByFile;
use Modules\Order\Models\Order;
use Modules\Order\Validators\ChangeShippingPartnerValidator;
use Modules\Order\Validators\ImportedForUpdateValidator;
use Modules\Order\Validators\UpdateOrderValidator;
use Modules\OrderIntegration\Commands\ProcessCreatingOrder;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class HandleFobizOrderEventJob extends Job
{
    const EVENT_ORDER_CREATED                 = "ORDER_CREATED";
    const EVENT_ORDER_CHANGE_STATUS           = "ORDER_CHANGE_STATUS";
    const EVENT_ORDER_COD_CHANGED             = "ORDER_COD_CHANGED";
    const EVENT_ORDER_UPDATED                 = "ORDER_UPDATED";
    const EVENT_ORDER_ITEM_CHANGED            = "ORDER_ITEM_CHANGED";
    const EVENT_ORDER_COURIER_SERVICE_CHANGED = "ORDER_COURIER_SERVICE_CHANGED";

    public $connection = 'redis';

    public $queue = 'fobiz_order_event';

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Order
     */
    protected $fobizOrder = [];

    /**
     * HandleFobizOrderEventJob constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return mixed|void
     * @throws WorkflowException
     * @throws Exception
     */
    public function handle()
    {
        LogService::logger('fobiz_order_job')->info('debug', $this->payload);

        $event            = Arr::get($this->payload, 'event');
        $this->payload    = Arr::get($this->payload, 'payload', []);
        $this->fobizOrder = Arr::get($this->payload, 'order', []);
        $tenantCode       = Arr::get($this->payload, 'tenant_code', "");
        $merchantCode     = Arr::get($this->payload, 'merchant_code', "");
        $orderCode        = Arr::get($this->fobizOrder, 'code', '');
        $fobizStatus      = Arr::get($this->fobizOrder, 'status', '');

        if (empty($orderCode) || empty($tenantCode) || empty($merchantCode)) {
            LogService::logger('fobiz_order_job')->error('Empty data', [$orderCode]);
            return;
        }

        if ($fobizStatus == 'DRAFT') {
            LogService::logger('fobiz_order_job')->error('Order DRAFT', [$orderCode]);
            return;
        }

        $this->tenant = Tenant::query()->firstWhere('code', $tenantCode);
        if (!$this->tenant instanceof Tenant) {
            LogService::logger('fobiz_order_job')->error('Tenant invalid ' . $tenantCode);
            return;
        }

        $this->merchant = $this->tenant->merchants()->firstWhere('code', $merchantCode);
        if (!$this->merchant instanceof Merchant) {
            LogService::logger('fobiz_order_job')->error('Merchant invalid ' . $merchantCode);
            return;
        }

        $this->creator = Service::user()->getUserFobiz();
        if (!$this->creator instanceof User) {
            LogService::logger('fobiz_order_job')->error('creator not found');
            return;
        }
        if (empty($this->creator->tenant_id)) {
            $this->creator->tenant_id = $this->tenant->id;
        }

        /**
         * Tạo mới đơn nếu gặp ev tạo hoặc chưa tồn tại đơn trước đó
         */
        if (
            $event == self::EVENT_ORDER_CREATED ||
            !$this->order = $this->merchant->orders()->firstWhere('code', $orderCode)
        ) {
            return $this->create();
        }

        switch ($event) {
            case self::EVENT_ORDER_UPDATED :
            case self::EVENT_ORDER_ITEM_CHANGED :
            case self::EVENT_ORDER_COD_CHANGED:
            {
                $this->updateOrder();
                break;
            }
            case self::EVENT_ORDER_COURIER_SERVICE_CHANGED :
            {
                $this->changeShippingPartner();
                break;
            }
            case self::EVENT_ORDER_CHANGE_STATUS :
            {
                $this->changeStatus();
                break;
            }
            default:
                break;
        }

        $this->updateDeliveryFee();
    }

    /**
     * @return void
     */
    protected function updateDeliveryFee()
    {
        $order = $this->order->refresh();
        $deliveryFee = Arr::get($this->fobizOrder, 'deliveryFee', 0);
        return (new ChangeDeliveryFee($this->order, $deliveryFee, $this->creator))->handle();
    }

    /**
     * @return mixed
     */
    protected function create()
    {
        $input = $this->makeInputCreateOrder();
        return (new ProcessCreatingOrder($input, $this->creator))->dispatch();
    }

    /**
     * @return array
     */
    protected function makeInputOrderSkus(): array
    {
        $orderSkus  = [];
        $orderItems = Arr::get($this->fobizOrder, 'orderItems', []);
        foreach ($orderItems as $orderItem) {
            $orderSkus[] = [
                "sku_code" => Arr::get($orderItem, 'sku'),
                "price" => Arr::get($orderItem, 'unitPrice'),
                "quantity" => Arr::get($orderItem, 'quantity'),
                "discount_amount" => Arr::get($orderItem, 'discountAmount', 0),
            ];
        }

        return $orderSkus;
    }

    /**
     * @return array
     */
    protected function makeInputCreateOrder(): array
    {
        $input = [
            "tenant" => $this->tenant->code,
            "merchant" => $this->merchant->code,
            "code" => Arr::get($this->fobizOrder, 'code', ''),
            "receiver_postal_code" => Arr::get($this->fobizOrder, 'postalCode', ''),
            "freight_bill" => Arr::get($this->fobizOrder, 'freightBill'),
            "shipping_partner_code" => Arr::get($this->fobizOrder, 'courierService.code'),
            "campaign" => Arr::get($this->fobizOrder, 'campaign.name'),
            "created_at_origin" => Arr::get($this->fobizOrder, 'createdAt'),
            "description" => Arr::get($this->fobizOrder, 'note'),
            "receiver_name" => Arr::get($this->fobizOrder, 'contactName'),
            "receiver_phone" => Arr::get($this->fobizOrder, 'contactPhone'),
            "receiver_address" => Arr::get($this->fobizOrder, 'address'),
            "receiver_province_code" => Arr::get($this->fobizOrder, 'province.m28Code'),
            "receiver_district_code" => Arr::get($this->fobizOrder, 'city.m28Code'),
            "receiver_ward_code" => Arr::get($this->fobizOrder, 'district.m28Code'),
            "discount_amount" => Arr::get($this->fobizOrder, 'discountAmount', 0),
            "payment_type" => Arr::get($this->fobizOrder, 'paymentType', "COD"),
            "intended_delivery_at" => Arr::get($this->fobizOrder, 'deliveryDate'),
            "shipping_amount" => Arr::get($this->fobizOrder, 'shippingAmount', 0),
            "cod" => Arr::get($this->fobizOrder, 'collectedCod', 0),
            "delivery_fee" => Arr::get($this->fobizOrder, 'deliveryFee', 0),
            "orderSkus" => $this->makeInputOrderSkus()
        ];

        $this->createLocation($input);

        return $input;
    }


    /**
     * tạo location nếu chưa có
     * @param $input
     */
    protected function createLocation($input)
    {
        $countryFobiz  = Arr::get($this->fobizOrder, 'country', []);
        $provinceFobiz = Arr::get($this->fobizOrder, 'province', []);
        $cityFobiz     = Arr::get($this->fobizOrder, 'city', []);
        $districtFobiz = Arr::get($this->fobizOrder, 'district', []);

        $receiverCountryCode = Arr::get($countryFobiz, 'id');
        if (!empty($receiverCountryCode)) {
            $country = Location::query()->firstOrCreate([
                'code' => "F" . $receiverCountryCode,
                'type' => Location::TYPE_COUNTRY
            ], [
                'label' => Arr::get($countryFobiz, 'name'),
                'active' => true,
                'priority' => 1
            ]);
        } else {
            $country = $this->merchant->getCountry();
        }

        if (!empty($input['receiver_province_code']) && isset($country) && $country instanceof Location) {
            $province = Location::query()->firstOrCreate([
                'code' => $input['receiver_province_code'],
                'type' => Location::TYPE_PROVINCE
            ], [
                'label' => Arr::get($provinceFobiz, 'name'),
                'active' => true,
                'parent_code' => $country->code,
                'priority' => 1
            ]);
        }

        if (!empty($input['receiver_district_code']) && isset($province) && $province instanceof Location) {
            $district = Location::query()->firstOrCreate([
                'code' => $input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ], [
                'label' => Arr::get($cityFobiz, 'name'),
                'active' => true,
                'parent_code' => $province->code,
                'priority' => 1
            ]);
        }

        if (!empty($input['receiver_ward_code']) && isset($district) && $district instanceof Location) {
            Location::query()->firstOrCreate([
                'code' => $input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ], [
                'label' => Arr::get($districtFobiz, 'name'),
                'active' => true,
                'parent_code' => $district->code,
                'priority' => 1
            ]);
        }
    }


    /**
     * @return Order|void
     */
    protected function changeAddress()
    {
        $input     = [
            "campaign" => Arr::get($this->fobizOrder, 'campaign.name'),
            "description" => Arr::get($this->fobizOrder, 'note'),
            "receiver_name" => Arr::get($this->fobizOrder, 'contactName'),
            "receiver_phone" => Arr::get($this->fobizOrder, 'contactPhone'),
            "receiver_address" => Arr::get($this->fobizOrder, 'address'),
        ];
        $validator = (new UpdateOrderValidator($this->order, $input));
        if ($validator->fails()) {
            LogService::logger('fobiz_order_job')->error('validate changeAddress error', ['validate' => $validator->errors(), 'input' => $this->payload]);
            return;
        }

        return (new UpdateOrder($this->order, $input, $this->creator))->handle();
    }


    /**
     * @return Order|void
     */
    protected function changeShippingPartner()
    {
        $shippingPartnerCode = Arr::get($this->fobizOrder, 'courierService.code', '');
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner = $this->tenant->shippingPartners()->firstWhere('code', $shippingPartnerCode);
        if (!$shippingPartner) {
            LogService::logger('fobiz_order_job')->error('shippingPartner not found ' . $shippingPartnerCode);
            return;
        }

        $validator = new ChangeShippingPartnerValidator($this->order, $this->creator, ['shipping_partner_id' => $shippingPartner->id]);
        if ($validator->fails()) {
            LogService::logger('fobiz_order_job')->error('validate changeShippingPartner error', ['validate' => $validator->errors(), 'input' => $this->payload]);
            return;
        }

        return (new ChangeShippingPartner($this->order, $shippingPartner, $this->creator))->handle();
    }

    /**
     * Map Fobiz status
     *
     * @param string $fobizStatus
     * @return string|null
     */
    public function mapStatus(string $fobizStatus): ?string
    {
        return Arr::get([
            'NEW' => Order::STATUS_WAITING_INSPECTION,
            'READY' => Order::STATUS_WAITING_INSPECTION,
            'HOLD' => Order::STATUS_WAITING_INSPECTION,
            'PICKED_UP' => Order::STATUS_WAITING_INSPECTION,
            'DELIVERING' => Order::STATUS_DELIVERING,
            'DELIVERED' => Order::STATUS_DELIVERED,
            'CANCELLED' => Order::STATUS_CANCELED
        ], $fobizStatus, '');
    }

    /**
     * Thay đổi trạng thái đơn
     * @throws WorkflowException
     */
    protected function changeStatus()
    {
        $order       = $this->order;
        $orderCode   = $order->code;
        $fobizStatus = Arr::get($this->fobizOrder, 'status', '');

        $status = $this->mapStatus($fobizStatus);
        if (
            empty($status) ||
            $order->status == $status ||
            !$order->canChangeStatus($status)
        ) {
            LogService::logger('fobiz_order_job')->error('Not can change ' . $orderCode . ' fobiz status:' . $fobizStatus . ' from ' . $order->status . ' to ' . $status);
            return;
        }

        if ($status == Order::STATUS_CANCELED) {
            $order->cancel_note = Arr::get($this->fobizOrder, 'cancelledReason', '');
        }

        $order->changeStatus($status, $this->creator);
    }

    /**
     * @throws Exception
     */
    protected function updateOrder()
    {
        $transInputs = $this->getTransformInputs();
        LogService::logger('fobiz_order_job')->info('INPUT_UPDATE', $transInputs);
        $validator = new ImportedforUpdateValidator($this->creator, $transInputs, $this->order->tenant);
        if ($validator->fails()) {
            LogService::logger('fobiz_order_job')->error('exception '.$this->order->code, ['errors' => TransformerService::transform($validator)]);
            throw new Exception('EXCEPTION VALIDATOR');
        }
        $inputOrderByFile = new InputOrderByFile($transInputs);
        $inputOrderByFile->shippingPartner = $validator->getShippingPartner();
        (new UpdateOrderByFile($this->order, $inputOrderByFile, $this->creator))->handle();
    }

    /**
     * Lấy thông tin đầu vào tạo/cập nhật đơn
     */
    protected function getTransformInputs(): array
    {
        /** @var Store|null $fobizStore */
        $fobizStore = Store::query()->where([
            'tenant_id' => $this->tenant->id,
            'marketplace_code' => Marketplace::CODE_FOBIZ
        ])->first();
        $orderSkus  = [];
        $orderItems = Arr::get($this->payload, 'order.orderItems', []);
        foreach ($orderItems as $orderItem) {
            $skuCode     = Arr::get($orderItem, 'sku');
            $sku         = $fobizStore ? Service::product()->getSkuByStore($fobizStore, $skuCode) : null;
            $orderSkus[] = [
                'sku_code' => $sku ? $sku->code : $skuCode,
                'sku_price' => Arr::get($orderItem, 'unitPrice'),
                'sku_quantity' => Arr::get($orderItem, 'quantity'),
                'sku_discount' => Arr::get($orderItem, 'discountAmount', 0),
            ];
        }

        return [
            'order_code' => Arr::get($this->payload, 'order.code'),
            'receiver_postal_code' => Arr::get($this->payload, 'order.postalCode'),
            'receiver_name' => Arr::get($this->payload, 'order.contactName'),
            'receiver_phone' => Arr::get($this->payload, 'order.contactPhone'),
            'receiver_country' => Arr::get($this->payload, 'order.country.m28Code'),
            'receiver_province' => Arr::get($this->payload, 'order.province.m28Code'),
            'receiver_district' => Arr::get($this->payload, 'order.city.m28Code'),
            'receiver_ward' => Arr::get($this->payload, 'order.district.m28Code'),
            'receiver_address' => Arr::get($this->payload, 'order.address'),
            'skus' => $orderSkus,
            'cod' => $this->getCodUpdate(),
            'shipping_partner_code' => Arr::get($this->fobizOrder, 'courierService.code'),
        ];
    }

    protected function getCodUpdate()
    {
        $cod = floatval(Arr::get($this->payload, 'order.collectedCod', 0));
        $cod = $cod - $this->order->orderTransactions()->sum('amount');
        return max($cod, 0);
    }
}
