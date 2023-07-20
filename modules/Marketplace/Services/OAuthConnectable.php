<?php

namespace Modules\Marketplace\Services;

use Illuminate\Http\Request;

interface OAuthConnectable
{
    /**
     * Tạo authorization redirect url
     *
     * @param string $callbackUrl
     * @param string $state
     * @return string
     */
    public function makeOAuthUrl($callbackUrl, $state);

    /**
     * Xử lý sau khi authorization
     *
     * @param Request $request
     * @return OAuthResponse
     */
    public function handleOAuthCallback(Request $request);
}
