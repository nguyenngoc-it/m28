<?php

namespace Modules\Shopee\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface ShopeePublicApiInterface
{
    /**
     * Get access token
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccessToken(array $input);

    /**
     * Refresh access token
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function refreshAccessToken(array $input);

    /**
     * Developer can get refresh_token for existing authorized shop by upgrade_code.
     * Help original developer to get access_token and start to call open api V2.0
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getRefreshTokenByUpgradeCode(array $input);
}
