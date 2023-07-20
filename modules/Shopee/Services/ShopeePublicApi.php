<?php

namespace Modules\Shopee\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

class ShopeePublicApi extends ShopeeApiV2 implements ShopeePublicApiInterface
{
    /**
     * Make common params
     *
     * @param string $apiPath
     * @return array
     */
    protected function makeCommonParams($apiPath)
    {
        $time = time();

        return [
            'partner_id' => $this->partnerId,
            'timestamp' => $time,
            'sign' => $this->makeSign($this->partnerId.$apiPath.$time),
        ];
    }

    /**
     * Get access token
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccessToken(array $input)
    {
        $input['partner_id'] = $this->partnerId;

        return $this->postRequest('/api/v2/auth/token/get', $input);
    }

    /**
     * Refresh access token
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function refreshAccessToken(array $input)
    {
        $input['partner_id'] = $this->partnerId;

        return $this->postRequest('/api/v2/auth/access_token/get', $input);
    }

    /**
     * Developer can get refresh_token for existing authorized shop by upgrade_code.
     * Help original developer to get access_token and start to call open api V2.0
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getRefreshTokenByUpgradeCode(array $input)
    {
        return $this->postRequest('/api/v2/public/get_refresh_token_by_upgrade_code', $input);
    }
}
