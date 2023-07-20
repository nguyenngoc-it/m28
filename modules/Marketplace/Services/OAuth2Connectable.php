<?php

namespace Modules\Marketplace\Services;

use Modules\Store\Models\Store;

interface OAuth2Connectable extends OAuthConnectable
{
    /**
     * Get oauth2 token
     *
     * @param Store $store
     * @return OAuth2Token
     */
    public function getOAuth2Token(Store $store);
}
