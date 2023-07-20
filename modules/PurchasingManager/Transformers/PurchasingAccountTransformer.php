<?php

namespace Modules\PurchasingManager\Transformers;

use App\Base\Transformer;
use Modules\PurchasingManager\Models\PurchasingAccount;

class PurchasingAccountTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingAccount $purchasingAccount
     * @return mixed
     */
    public function transform($purchasingAccount)
    {
        return array_merge($purchasingAccount->attributesToArray(), [
            'password' => openssl_decrypt($purchasingAccount->password, 'AES-128-ECB', env('purchase.secret_password')),
            'pin_code' => openssl_decrypt($purchasingAccount->pin_code, 'AES-128-ECB', env('purchase.secret_password')),
            'creator' => $purchasingAccount->creator ? $purchasingAccount->creator->only(['username', 'name']) : null,
            'merchant' => $purchasingAccount->merchant ? $purchasingAccount->merchant->only(['code', 'name']) : null,
            'purchasing_service' => $purchasingAccount->purchasingService ? $purchasingAccount->purchasingService->only(['code', 'name']) : null,
        ]);
    }
}
