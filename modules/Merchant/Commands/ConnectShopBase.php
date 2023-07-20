<?php

namespace Modules\Merchant\Commands;

use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\ShopBase\Models\ShopBase;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Merchant\Services\MerchantEvent;
use Gobiz\Log\LogService;

class ConnectShopBase
{
    /**
     * @var array
     */
    protected $input;

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
     * ConnectShopBase constructor.
     * @param Merchant $merchant
     * @param User $creator
     * @param array $input
     */
    public function __construct(Merchant $merchant, User $creator, array $input)
    {
        $this->merchant = $merchant;
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Merchant|false
     */
    public function handle()
    {
        foreach (Merchant::$shopBaseParams as $key) {
            $this->merchant->{$key} = isset($this->input[$key]) ? $this->input[$key] : '';
        }

        $result = Service::appLog()->logTimeExecute(function () {
           return Service::shopBase()->createWebhook($this->merchant, ShopBase::TOPIC_ORDER_CREATE);
        }, LogService::logger('shop-base-api-time'), 'merchant: '.$this->merchant->code);

        if(!isset($result->webhook->id)) {
            return false;
        }

        $this->merchant->shop_base_webhook_id = $result->webhook->id;
        $this->merchant->save();

        $this->merchant->logActivity(MerchantEvent::CONNECT_SHOP_BASE, $this->creator, []);

        return $this->merchant;
    }
}