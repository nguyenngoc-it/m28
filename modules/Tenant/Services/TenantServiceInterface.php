<?php

namespace Modules\Tenant\Services;

use Gobiz\Email\EmailProviderInterface;
use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystem;
use Illuminate\Support\Collection;
use Modules\Gobiz\Services\M10ApiInterface;
use Modules\Gobiz\Services\M4ApiInterface;
use Modules\Gobiz\Services\M6ApiInterface;
use Modules\Tenant\Models\Tenant;

interface TenantServiceInterface
{
    /**
     * Get list tenants
     *
     * @return Collection
     */
    public function lists();

    /**
     * Find tenant by id
     *
     * @param int $id
     * @return Tenant|null
     */
    public function find($id);

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain($domain);

    /**
     * Lay doi tuong storage cua tenant
     *
     * @param Tenant $tenant
     * @return CloudFilesystem
     */
    public function storage(Tenant $tenant);

    /**
     * Lấy đối tượng xử lý email của tenant
     *
     * @param Tenant $tenant
     * @return EmailProviderInterface
     */
    public function email(Tenant $tenant);

    /**
     * Generate tenant url
     *
     * @param Tenant $tenant
     * @param string $path
     * @param array $query
     * @param string|null $domain
     * @return string
     */
    public function url(Tenant $tenant, $path = '', array $query = [], $domain = null);

    /**
     * @param array $query
     * @param $domain
     * @return mixed
     */
    public function redirectUrl(array $query = [], $domain = null);

    /**
     * Lấy đối tượng kết nối m6
     *
     * @param Tenant $tenant
     * @return M6ApiInterface
     */
    public function m6(Tenant $tenant);

    /**
     * Lấy đối tượng kết nối m4
     *
     * @param Tenant $tenant
     * @param string $type
     * @return M4ApiInterface
     */
    public function m4(Tenant $tenant, string $type);

    /**
     * Lấy đối tượng kết nối m10
     *
     * @param Tenant $tenant
     * @return M10ApiInterface
     */
    public function m10(Tenant $tenant);
}
