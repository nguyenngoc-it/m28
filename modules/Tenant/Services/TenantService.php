<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystem;
use Gobiz\Email\EmailProviderInterface;
use Gobiz\Email\IrisEmailProvider;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Gobiz\Services\M4Api;
use Modules\Gobiz\Services\M4ApiInterface;
use Modules\Gobiz\Services\M10Api;
use Modules\Gobiz\Services\M10ApiInterface;
use Modules\Gobiz\Services\M6Api;
use Modules\Gobiz\Services\M6ApiInterface;
use Modules\Tenant\Models\Tenant;

class TenantService implements TenantServiceInterface
{
    /**
     * @var Collection
     */
    protected $tenants;

    /**
     * @var CloudFilesystem[]
     */
    protected $storages = [];

    /**
     * @var EmailProviderInterface[]
     */
    protected $emails = [];

    /**
     * @var M6ApiInterface[]
     */
    protected $m6Apis = [];


    /**
     * @var M4ApiInterface[]
     */
    protected $m4Apis = [];


    /**
     * @var M10ApiInterface[]
     */
    protected $m10Apis = [];

    /**
     * Get list tenants
     *
     * @return Collection
     */
    public function lists()
    {
        return $this->tenants === null ? $this->tenants = Tenant::all()->toBase() : $this->tenants;
    }

    /**
     * Find tenant by id
     *
     * @param int $id
     * @return Tenant|null
     */
    public function find($id)
    {
        return $this->lists()->firstWhere('id', $id);
    }

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain($domain)
    {
        return $this->lists()->first(function (Tenant $tenant) use ($domain) {
            $domains = array_merge($tenant->domains, $tenant->merchant_domains ?: []);
            return in_array($domain, $domains);
        });
    }

    /**
     * Lay doi tuong storage cua tenant
     *
     * @param Tenant $tenant
     * @return CloudFilesystem
     */
    public function storage(Tenant $tenant)
    {
        $key = $tenant->id;

        if (isset($this->storages[$key])) {
            return $this->storages[$key];
        }

        $config = array_merge(config('filesystems.disks.s3'), [
            'root' => $tenant->code,
        ]);

        return $this->storages[$key] = app('filesystem')->createS3Driver($config);
    }

    /**
     * Lấy đối tượng xử lý email của tenant
     *
     * @param Tenant $tenant
     * @return EmailProviderInterface
     */
    public function email(Tenant $tenant)
    {
        $key = $tenant->id;

        if (isset($this->emails[$key])) {
            return $this->emails[$key];
        }

        $config = array_merge(config('email.providers.iris'), [
            'default_sender' => $tenant->getSetting(Tenant::SETTING_EMAIL) ?: config('email.sender'),
        ]);

        if (
            ($username = $tenant->getSetting(Tenant::SETTING_IRIS_USERNAME))
            && ($password = $tenant->getSetting(Tenant::SETTING_IRIS_PASSWORD))
        ) {
            $config = array_merge($config, compact('username', 'password'));
        }

        return $this->emails[$key] = new IrisEmailProvider($config, LogService::logger('iris'));
    }

    /**
     * Lấy đối tượng kết nối m6
     *
     * @param Tenant $tenant
     * @return M6ApiInterface
     */
    public function m6(Tenant $tenant)
    {
        $key = $tenant->id;

        if (isset($this->m6Apis[$key])) {
            return $this->m6Apis[$key];
        }

        return $this->m6Apis[$key] = new M6Api(
            config('gobiz.m6.url'),
            $tenant->getSetting(Tenant::SETTING_M6_TOKEN),
            Arr::only(config('gobiz.m6'), ['timeout'])
        );
    }

    /**
     * Lấy đối tượng kết nối m4
     *
     * @param Tenant $tenant
     * @param string $type
     * @return M4Api|M4ApiInterface
     */
    public function m4(Tenant $tenant, string $type)
    {
        $key = $type . '-' . $tenant->id;

        if (isset($this->m4Apis[$key])) {
            return $this->m4Apis[$key];
        }

        if (!$m4Tenant = $tenant->{'m4_tenant_' . $type}) {
            throw new \InvalidArgumentException("The type {$type} invalid");
        }

        return $this->m4Apis[$key] = new M4Api(
            config('gobiz.m4.url'),
            $m4Tenant,
            Arr::only(config('gobiz.m4'), ['timeout'])
        );
    }

    /**
     * Lấy đối tượng kết nối m10
     *
     * @param Tenant $tenant
     * @return M10ApiInterface
     */
    public function m10(Tenant $tenant)
    {
        $key = $tenant->id;

        if (isset($this->m10Apis[$key])) {
            return $this->m10Apis[$key];
        }

        return $this->m10Apis[$key] = new M10Api(
            $tenant,
            config('gobiz.m10.url'),
            Arr::only(config('gobiz.m10'), ['timeout'])
        );
    }

    /**
     * Generate tenant url
     *
     * @param Tenant $tenant
     * @param string $path
     * @param array $query
     * @param string|null $domain
     * @return string
     */
    public function url(Tenant $tenant, $path = '', array $query = [], $domain = null)
    {
        $domain = $domain ?: Arr::first($tenant->domains);
        $url    = config('gobiz.tenant_ssl') ? "https://{$domain}" : "http://{$domain}";
        $url    .= $path ? '/' . $path : '';
        $url    .= !empty($query) ? '?' . http_build_query($query) : '';

        return $url;
    }

    /**
     * @param array $query
     * @param $domain
     * @return string
     */
    public function redirectUrl(array $query = [], $domain = null)
    {
        $url = $domain;
        if (!Str::contains($domain, ['http', 'https'])) {
            $url = config('gobiz.tenant_ssl') ? "https://{$domain}" : "http://{$domain}";
        }

        $url .= !empty($query) ? '?' . http_build_query($query) : '';

        return $url;
    }
}
