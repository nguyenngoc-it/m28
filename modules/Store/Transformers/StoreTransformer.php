<?php

namespace Modules\Store\Transformers;

use App\Base\Transformer;
use Modules\Marketplace\Services\OAuth2Connectable;
use Modules\Store\Models\Store;

class StoreTransformer extends Transformer
{
    /**
     * @param Store $store
     * @return array
     */
    public function transform($store)
    {
        $marketplace = $store->marketplace();

        return array_merge($store->attributesToArray(), [
            'connection_expired_at' => $marketplace instanceof OAuth2Connectable ? $marketplace->getOAuth2Token($store)->refreshTokenExpiredAt : null,
            'can_reconnect' => $marketplace instanceof OAuth2Connectable,
        ]);
    }
}
