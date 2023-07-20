<?php

namespace Modules\Marketplace\Services;

use Modules\Tenant\Models\Tenant;

interface MarketplaceServiceInterface
{
    /**
     * Lấy danh sách marketplace
     *
     * @return MarketplaceInterface[]
     */
    public function marketplaces();

    /**
     * Lấy đối tượng xử lý của marketplace
     *
     * @param string $code
     * @return MarketplaceInterface|null
     */
    public function marketplace($code);

    /**
     * Phân tích oauth state
     *
     * @param string $state
     * @return array|null
     */
    public function parseOAuthState($state);

    /**
     * Tạo OAuth state
     *
     * @param Tenant $tenant
     * @param int $merchantId
     * @param string $domain
     *  @param int $warehouseId
     * @return string
     */
    public function makeOAuthState(Tenant $tenant, $merchantId, $domain, $warehouseId);
}
