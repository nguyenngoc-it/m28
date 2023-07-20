<?php

namespace Modules\Marketplace\Services;

use InvalidArgumentException;
use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class MarketplaceService implements MarketplaceServiceInterface
{
    /**
     * @var MarketplaceInterface[]
     */
    protected $marketplaces = [];

    /**
     * StoreService constructor
     *
     * @param MarketplaceInterface[] $marketplaces
     */
    public function __construct(array $marketplaces)
    {
        $this->marketplaces = $marketplaces;
    }

    /**
     * Lấy danh sách nền tảng sàn TMĐT
     *
     * @return MarketplaceInterface[]
     */
    public function marketplaces()
    {
        return $this->marketplaces;
    }

    /**
     * Lấy đối tượng xử lý của 1 nền tảng sàn TMĐT
     *
     * @param string $code
     * @return MarketplaceInterface|null
     */
    public function marketplace($code)
    {
        foreach ($this->marketplaces as $marketplace) {
            if ($marketplace->getCode() === $code) {
                return $marketplace;
            }
        }

        return null;
    }

    /**
     * Tạo OAuth state
     *
     * @param Tenant $tenant
     * @param int $merchantId
     * @param string $domain
     * @param int $warehouseId
     * @return string
     */
    public function makeOAuthState(Tenant $tenant, $merchantId, $domain, $warehouseId)
    {
        $payload = $merchantId.'|'.$domain.'|'.$warehouseId;

        return $payload.'|'.hash('sha256', "{$payload}|{$tenant->client_secret}");
    }

    /**
     * Phân tích oauth state
     *
     * @param string $state
     * @return array|null
     */
    public function parseOAuthState($state)
    {
        $state = urldecode($state);
        $items = explode('|', $state);

        if (count($items) !== 4) {
            throw new InvalidArgumentException('STATE_INVALID');
        }

        list($merchantId, $domain, $warehouseId) = $items;

        if (!$merchant = Merchant::find($merchantId)) {
            throw new InvalidArgumentException('MERCHANT_NOT_FOUND');
        }

        if (!$warehouse = Warehouse::find($warehouseId)) {
            throw new InvalidArgumentException('WAREHOUSE_NOT_FOUND');
        }

        if ($state !== $this->makeOAuthState($merchant->tenant, $merchant->id, $domain, $warehouseId)) {
            throw new InvalidArgumentException('STATE_INVALID');
        }

        return compact('merchant', 'domain', 'warehouse');
    }
}
