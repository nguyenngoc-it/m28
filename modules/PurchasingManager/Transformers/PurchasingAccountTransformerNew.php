<?php

namespace Modules\PurchasingManager\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\PurchasingManager\Models\PurchasingAccount;

class PurchasingAccountTransformerNew extends TransformerAbstract
{
    public function __construct()
    {
        $this->setDefaultIncludes([]);
        $this->setAvailableIncludes(['purchasing_service']);
    }

    public function transform(PurchasingAccount $purchasingAccount)
    {
        return [
            'id' => $purchasingAccount->id,
            'tenant_id' => $purchasingAccount->tenant_id,
            'merchant_id' => $purchasingAccount->merchant_id,
            'purchasing_service_id' => $purchasingAccount->purchasing_service_id,
            'username' => $purchasingAccount->username,
            'status' => $purchasingAccount->status,
            'creator_id' => $purchasingAccount->creator_id
        ];
    }

    /**
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includePurchasingService(PurchasingAccount $purchasingAccount)
    {
        $PurchasingService = $purchasingAccount->purchasingService;
		
        return $this->item($PurchasingService, new PurchasingServiceTransformer);
    }

}
