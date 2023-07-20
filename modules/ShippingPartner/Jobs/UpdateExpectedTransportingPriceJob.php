<?php

namespace Modules\ShippingPartner\Jobs;

use App\Base\Job;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;

class UpdateExpectedTransportingPriceJob extends Job
{
    protected $inputs;
    protected $shippingPartnerId;
    protected $warehouseId;
    protected $user;

    /**
     * UpdateExpectedTransportingPriceJob constructor.
     * @param array $inputs
     * @param int $shippingPartnerId
     * @param int $warehouseId
     */
    public function __construct(array $inputs, int $shippingPartnerId, int $warehouseId)
    {
        $this->inputs            = $inputs;
        $this->shippingPartnerId = $shippingPartnerId;
        $this->warehouseId       = $warehouseId;
    }

    /**
     * @throws ExpectedTransportingPriceException
     */
    public function handle()
    {
        $shippingPartner = ShippingPartner::find($this->shippingPartnerId);
        foreach ($this->inputs as $input) {
            $input['shipping_partner_id'] = $this->shippingPartnerId;
            $input['warehouse_id'] = $this->warehouseId;
            $input['tenant_id'] = $this->shippingPartnerId;
            $shippingPartner->expectedTransporting()->makeTablePrice($input);
        }
    }
}
