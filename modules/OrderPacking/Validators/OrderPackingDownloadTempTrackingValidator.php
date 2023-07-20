<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;

class OrderPackingDownloadTempTrackingValidator extends Validator
{
    /** @var ShippingPartner */
    protected $shippingPartner;
    /** @var array $orderPackingIds */
    protected $orderPackingIds = [];

    /**
     * OrderPackingScanValidator constructor.
     * @param array|null $orderPackingIds
     * @param array $input
     */
    public function __construct(array $input = [], $orderPackingIds = [])
    {
        $this->orderPackingIds = (array)$orderPackingIds;
        parent::__construct($input);
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner(): ShippingPartner
    {
        return $this->shippingPartner;
    }

    protected function customValidate()
    {
        if ($this->orderPackingIds) {
            $orderPackings = OrderPacking::query()->whereIn('id', $this->orderPackingIds)->get();
            if ($orderPackings->count()) {
                /** @var OrderPacking $orderPacking */
                foreach ($orderPackings as $orderPacking) {
                    if (empty($this->shippingPartner)) {
                        $this->shippingPartner = $orderPacking->shippingPartner;
                    } else {
                        if ($this->shippingPartner->id != $orderPacking->shipping_partner_id) {
                            $this->errors()->add('shipping_partner', static::ERROR_INVALID);
                            return;
                        }
                    }
                }
            }
        } else {
            $shippingPartnerId = $this->input('shipping_partner_id');
            if (empty($shippingPartnerId)) {
                $this->errors()->add('shipping_partner', static::ERROR_REQUIRED);
                return;
            }
            $this->shippingPartner = ShippingPartner::find($shippingPartnerId);
        }

        if (!$this->shippingPartner) {
            $this->errors()->add('shipping_partner', static::ERROR_REQUIRED);
            return;
        }
        if (!$this->shippingPartner->temp_tracking) {
            $this->errors()->add('shipping_partner', 'not_data_temp_tracking');
            return;
        }
    }
}
