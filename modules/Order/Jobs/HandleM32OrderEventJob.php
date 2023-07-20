<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantSetting;

class HandleM32OrderEventJob extends Job
{
    public $connection = 'redis';

    public $queue = 'm32_order_event';

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * HandleM32OrderEventJob constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    protected function logger()
    {
        return LogService::logger('m32_order_job');
    }

    public function handle()
    {
        $event   = Arr::get($this->payload, 'event');
        $payload = Arr::get($this->payload, 'payload', []);
        $this->logger()->debug('INPUT', $payload);

        switch ($event) {
            case "ORDER_CHANGE_STATUS":
            {
                $this->changeStatus($payload);
                break;
            }
            default:
                break;
        }
    }

    /**
     * @param $m32Status
     * @return mixed
     */
    protected function mapFreightBillStatus($m32Status)
    {
        return Arr::get([
            'CREATING' => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            'READY_TO_PICK' => FreightBill::STATUS_PICKED_UP,
            'PICKED_UP' => FreightBill::STATUS_CONFIRMED_PICKED_UP,
            'DELIVERING' => FreightBill::STATUS_DELIVERING,
            'DELIVERED' => FreightBill::STATUS_DELIVERED,
            'RETURNING' => FreightBill::STATUS_RETURN,
            'RETURNED' => FreightBill::STATUS_RETURN,
            'ERROR' => FreightBill::STATUS_FAILED_DELIVERY,
            'CANCEL' => FreightBill::STATUS_CANCELLED,
        ], $m32Status, '');
    }

    /**
     * @param $payload
     */
    protected function changeStatus($payload)
    {
        $order               = Arr::get($payload, 'order', []);
        $shippingPartner     = Arr::get($payload, 'shipping_partner', []);
        $application         = Arr::get($payload, 'application', []);
        $freightBillCode     = Arr::get($order, 'tracking_no', '');
        $shippingPartnerCode = Arr::get($shippingPartner, 'code', '');
        $shippingCarrierCode = Arr::get($order, 'shipping_carrier_code', '');
        $applicationCode     = Arr::get($application, 'code', '');

        $status = Arr::get($order, 'status', '');
        if (empty($freightBillCode) || empty($shippingPartnerCode) || empty($applicationCode)) {
            $this->logger()->error('Empty data');
            return;
        }

        $tenant         = null;
        $tenantSettings = TenantSetting::query()
            ->where('key', TenantSetting::M32_APP_CODE)
            ->get();
        foreach ($tenantSettings as $tenantSetting) {
            if ($tenantSetting->value == trim($applicationCode)) {
                $tenant = $tenantSetting->tenant;
            }
        }
        if (!$tenant instanceof Tenant) {
            $this->logger()->error('app_code_invalid ' . $applicationCode . ' - ' . $freightBillCode . ' - ' . $shippingPartnerCode);
            return;
        }

        $connectCode     = json_encode(['connect_code' => $shippingCarrierCode]);
        $shipping_partner = ShippingPartner::query()->where('tenant_id', $tenant->id)
            ->whereRaw("json_contains(`settings`, '$connectCode')")->first();
        if (!$shipping_partner instanceof ShippingPartner) {
            $shipping_partner = ShippingPartner::query()->firstWhere([
                'code' => $shippingPartnerCode,
                'tenant_id' => $tenant->id
            ]);
        }

        if (!$shipping_partner instanceof ShippingPartner) {
            $this->logger()->error('shipping_partner_code_invalid ' . $freightBillCode . ' - ' . $shippingPartnerCode .' - Carrier '. $shippingCarrierCode);
            return;
        }

        $freightBill = FreightBill::query()->firstWhere([
            'shipping_partner_id' => $shipping_partner->id,
            'freight_bill_code' => $freightBillCode
        ]);
        if (!$freightBill instanceof FreightBill) {
            $this->logger()->error('freight_bill_invalid ' . $freightBillCode . ' - ' .$shipping_partner->id. ' - '. $shipping_partner->code);
            return;
        }

        $newStatus = $this->mapFreightBillStatus($status);
        if (
            (!empty($newStatus)) &&
            $freightBill->status != $newStatus &&
            $freightBill->status != FreightBill::STATUS_CANCELLED // nếu vận đơn đã bị hủy bên m28 thì không đổi lại trạng thái #2727
        ) {
            $this->logger()->info('change_status from ' . $freightBill->status . ' - to ' . $newStatus);

            $creator            = Service::user()->getSystemUserDefault();
            $creator->tenant_id = $tenant->id;
            Service::freightBill()->changeStatus($freightBill, $newStatus, $creator);
        }
    }
}
