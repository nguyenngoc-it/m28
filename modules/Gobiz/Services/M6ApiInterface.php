<?php

namespace Modules\Gobiz\Services;

use Gobiz\Support\RestApiResponse;
use Modules\Tenant\Models\Tenant;

interface M6ApiInterface
{
    /**
     * Get authenticated user
     *
     * @return RestApiResponse
     */
    public function me();

    /**
     * Create Package
     *
     * @param array $input
     * @return RestApiResponse
     */
    public function createPackage(array $input);

    /**
     * List Package
     *
     * @param Tenant $tenant
     * @param array $query
     * @return RestApiResponse
     */
    public function listPackage(Tenant $tenant, array $query);
}
