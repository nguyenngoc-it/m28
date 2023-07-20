<?php

namespace Modules\OrderIntegration\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\OrderIntegration\Validators\ImportingExpectedTransportingPriceValidator;
use Modules\Service;

class ShippingPartnerManualFixController extends Controller
{
    /**
     * Update bảng phí vận chuyển dự kiến
     *
     * @param $shippingPartnerCode
     * @return JsonResponse
     */
    public function importExpectedTransportingPrice($shippingPartnerCode)
    {
        $input                          = $this->request()->only(['tenant_code', 'file']);
        $input['shipping_partner_code'] = trim($shippingPartnerCode);
        $validator                      = (new ImportingExpectedTransportingPriceValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::shippingPartner()->importExpectedTransportingPrice($validator->getShippingPartner(), $validator->getFile());

        return $this->response()->success(['message' => 'success']);
    }
}
