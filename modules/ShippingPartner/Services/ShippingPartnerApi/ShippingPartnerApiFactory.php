<?php

namespace Modules\ShippingPartner\Services\ShippingPartnerApi;

use Modules\ShippingPartner\Provider\M32Provider;
use Modules\ShippingPartner\Provider\ManualProvider;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Provider\ShopeeProvider;
use Modules\ShippingPartner\Provider\TikTokShopProvider;
use Modules\ShippingPartner\Provider\TopshipProvider;
use Modules\Tenant\Models\TenantSetting;

class ShippingPartnerApiFactory implements ShippingPartnerApiFactoryInterface
{
    /**
     * @var ShippingPartnerApiInterface[]
     */
    protected $apis = [];

    /**
     * @param ShippingPartner $shippingPartner
     * @return ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function make(ShippingPartner $shippingPartner)
    {
        if (!isset($this->apis[$shippingPartner->id])) {
            $this->apis[$shippingPartner->id] = $this->makeShippingPartnerProviderApi($shippingPartner);
        }

        return $this->apis[$shippingPartner->id];
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function makeShippingPartnerProviderApi(ShippingPartner $shippingPartner)
    {
        switch ($provider = strtolower($shippingPartner->provider)) {
            case ShippingPartner::PROVIDER_M32: {
                    $tenant    = $shippingPartner->tenant;
                    $appCode   = $tenant->getSetting(TenantSetting::M32_APP_CODE, '');
                    $appSecret = $tenant->getSetting(TenantSetting::M32_APP_SECRET, '');

                    if(!$appCode || !$appSecret) {
                        throw new ShippingPartnerApiException("Tenant #{$tenant->code}: App code and secret invalid");
                    }

                    if(empty($shippingPartner->getSetting(ShippingPartner::SETTING_CARRIER))) {
                        throw new ShippingPartnerApiException("ShippingPartner #{$shippingPartner->code}: carrier invalid");
                    }

                    return new M32Provider(config('gobiz.m32.url'), $appCode, $appSecret);
                }

            case ShippingPartner::PROVIDER_SHOPEE:
                return new ShopeeProvider();

            case ShippingPartner::PROVIDER_TIKTOKSHOP:
                return new TikTokShopProvider();

            case ShippingPartner::PROVIDER_TOPSHIP:
                return new TopshipProvider($shippingPartner->settings);

            default:
                return new ManualProvider();
        }

    }
}
