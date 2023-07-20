<?php

namespace Modules\Auth\Services;

use Exception;
use InvalidArgumentException;
use Laravel\Socialite\Two\AbstractProvider as OAuthProvider;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\Auth\Commands\SaveAuthenticatedUser;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class AuthService implements AuthServiceInterface
{
    /**
     * @var OAuthProvider[]
     */
    protected $oauths = [];

    /**
     * Make OAuth handler for tenant
     *
     * @param Tenant $tenant
     * @return OAuthProvider
     */
    public function oauth(Tenant $tenant)
    {
        if (!isset($this->oauths[$tenant->id])) {
            $this->oauths[$tenant->id] = new GobizOAuth(request(), $tenant->client_id, $tenant->client_secret, url('login/callback'));
        }

        return $this->oauths[$tenant->id];
    }

    /**
     * Make auth state
     *
     * @param Tenant $tenant
     * @param string $domain
     * @param string $ref
     * @return string
     */
    public function makeAuthState(Tenant $tenant, $domain, $ref = '')
    {
        return $domain.'@'.trim($ref).'@'.hash('sha256', "{$domain}@{$tenant->client_secret}");
    }

    /**
     * Parse auth state
     *
     * @param string $state
     * @return array
     * @throws InvalidArgumentException
     */
    public function parseAuthState($state)
    {
        $states = explode('@', $state);
        $domain = $states[0];
        $ref    = isset($states[1]) ? $states[1] : '';

        if (!$tenant = Service::tenant()->findByDomain($domain)) {
            throw new InvalidArgumentException('TENANT_NOT_FOUND');
        }

        if ($state !== $this->makeAuthState($tenant, $domain, $ref)) {
            throw new InvalidArgumentException('STATE_INVALID');
        }

        return [
            'tenant' => $tenant,
            'domain' => $domain,
            'ref' => $ref
        ];
    }

    /**
     * Save the authenticated user
     *
     * @param Tenant $tenant
     * @param AuthenticatedUser $user
     * @return User
     * @throws Exception
     */
    public function saveUser(Tenant $tenant, AuthenticatedUser $user)
    {
        return (new SaveAuthenticatedUser($tenant, $user))->handle();
    }
}
