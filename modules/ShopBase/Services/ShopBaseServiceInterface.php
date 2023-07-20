<?php

namespace Modules\ShopBase\Services;

use Modules\Merchant\Models\Merchant;

interface ShopBaseServiceInterface
{
    /**
     * @param $data
     * @param $hmac_header
     * @param $shop_base_secret
     * @return bool
     */
    public function verifyWebhook($data, $hmac_header, $shop_base_secret);

    /**
     * @param Merchant $merchant
     * @param $topic
     */
    public function createWebhook(Merchant $merchant, $topic);

    /**
     * @param Merchant $merchant
     */
    public function deleteWebhook(Merchant $merchant);
}
