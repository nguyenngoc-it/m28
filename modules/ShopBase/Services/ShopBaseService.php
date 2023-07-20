<?php

namespace Modules\ShopBase\Services;

use Modules\Merchant\Models\Merchant;

class ShopBaseService implements ShopBaseServiceInterface
{
    /**
     * @param $data
     * @param $hmac_header
     * @param $shop_base_secret
     * @return bool
     */
    public function verifyWebhook($data, $hmac_header, $shop_base_secret) {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $shop_base_secret, true));
        return hash_equals($hmac_header, $calculated_hmac);
    }


    /**
     * @param Merchant $merchant
     * @param $topic
     * @return \Gobiz\Support\RestApiResponse
     */
    public function createWebhook(Merchant $merchant, $topic)
    {
        $gobizDomainApi = config('app.url');
        $shopBaseApi = new ShopBaseApi($merchant->shop_base_account, $merchant->shop_base_app_key, $merchant->shop_base_password);
        $payload = [
            'webhook' => [
                "address" => $gobizDomainApi."/shopbase/order/create/".$merchant->id,
                "format" => "json",
                "topic" => $topic
            ]
        ];

        return $shopBaseApi->createWebhook($payload);
    }

    /**
     * @param Merchant $merchant
     * @return \Gobiz\Support\RestApiResponse
     */
    public function deleteWebhook(Merchant $merchant)
    {
        $shopBaseApi = new ShopBaseApi($merchant->shop_base_account, $merchant->shop_base_app_key, $merchant->shop_base_password);
        return $shopBaseApi->deleteWebhook($merchant->shop_base_webhook_id);
    }
}
