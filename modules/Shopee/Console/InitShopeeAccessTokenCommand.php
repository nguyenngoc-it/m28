<?php

namespace Modules\Shopee\Console;

use Gobiz\Support\RestApiException;
use Illuminate\Console\Command;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;

class InitShopeeAccessTokenCommand extends Command
{
    protected $signature = 'shopee:init-access-token {upgrade_code} {shop_ids?}';

    protected $description = 'Init access & refresh token v2';

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        /**
         * @var Store[] $stores
         */
        $upgradeCode = $this->argument('upgrade_code');
        $shopIds = $this->argument('shop_ids');

        $stores = $this->getStores($shopIds);

        $this->warn('Getting refresh token by upgrade code');
        $res = Service::shopee()->publicApi()->getRefreshTokenByUpgradeCode([
            'upgrade_code' => $upgradeCode,
            'shop_id_list' => $stores->pluck('marketplace_store_id')->map('intval')->toArray(),
        ]);

        $shopIds = $res->getData('response.success_shop_id_list');
        $refreshToken = $res->getData('response.refresh_token');
        $stores = $stores->whereIn('marketplace_store_id', $shopIds, false);

        $this->warn("Saving refresh token");
        foreach ($stores as $store) {
            $this->saveRefreshToken($store, $refreshToken);
        }

        foreach ($stores as $store) {
            $this->warn("Shop {$store->marketplace_store_id}: Refreshing access token");

            try {
                $this->refreshAccessToken($store, $refreshToken);
                $this->info("Shop {$store->marketplace_store_id}: Refresh access token success");
            } catch (\Throwable $exception) {
                $this->error("Shop {$store->marketplace_store_id}: Refresh access token failed");
            }
        }

        foreach ($stores as $store) {
            $this->warn("Shop {$store->marketplace_store_id}: Connecting");

            try {
                $store->shopeeApi()->testConnection();
                $this->info("Shop {$store->marketplace_store_id}: Connect success");
            } catch (\Throwable $exception) {
                $this->error("Shop {$store->marketplace_store_id}: Connect failed");
            }
        }
    }

    protected function getStores($shopIds)
    {
        $query = Store::query()->where('marketplace_code', Marketplace::CODE_SHOPEE);

        if ($shopIds) {
            return $query->whereIn('marketplace_store_id', explode(',', $shopIds))->get();
        }

        return $query->get()->filter(function (Store $store) {
            return !$store->getSetting('refresh_token');
        });
    }

    /**
     * @param Store $store
     * @param string $refreshToken
     * @return bool
     */
    protected function saveRefreshToken(Store $store, $refreshToken)
    {
        $store->settings = array_merge($store->settings, [
            'refresh_token' => $refreshToken,
            'refresh_token_expired_at' => time() + (30 * 24 * 30 * 30) - 60, // buffer 60s phòng sai lệch time
        ]);

        return $store->save();
    }

    /**
     * @param Store $store
     * @param string $refreshToken
     * @throws RestApiException
     */
    protected function refreshAccessToken(Store $store, $refreshToken)
    {
        $token = Service::shopee()->publicApi()->refreshAccessToken([
            'refresh_token' => $refreshToken,
            'shop_id' => (int)$store->marketplace_store_id,
        ])->getData();

        $store->settings = array_merge($store->settings, Service::shopee()->makeToken($token));
        $store->save();
    }
}
