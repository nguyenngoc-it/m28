<?php

namespace Modules\Merchant\Commands;

use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Merchant\Services\MerchantEvent;

class DisconnectShopBase
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var User
     */
    protected $merchant;

    /**
     * DisconnectShopBase constructor.
     * @param Merchant $merchant
     * @param User $creator
     */
    public function __construct(Merchant $merchant, User $creator)
    {
        $this->merchant = $merchant;
        $this->creator = $creator;
    }


    /**
     * @return Merchant
     */
    public function handle()
    {
        Service::shopBase()->deleteWebhook($this->merchant);

        $this->merchant->update([
            'shop_base_webhook_id' => '',
            'shop_base_account' => '',
            'shop_base_app_key' => '',
            'shop_base_password' => '',
            'shop_base_secret' => '',
        ]);

        $this->merchant->logActivity(MerchantEvent::DISCONNECT_SHOP_BASE, $this->creator, []);

        return $this->merchant;
    }
}