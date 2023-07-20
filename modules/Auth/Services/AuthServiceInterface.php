<?php

namespace Modules\Auth\Services;

use InvalidArgumentException;
use Laravel\Socialite\Two\AbstractProvider as OAuthProvider;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

interface AuthServiceInterface
{
    /**
     * Make OAuth handler for tenant
     *
     * @param Tenant $tenant
     * @return OAuthProvider
     */
    public function oauth(Tenant $tenant);

    /**
     * Parse auth state
     *
     * @param string $state
     * @return array
     * @throws InvalidArgumentException
     */
    public function parseAuthState($state);

    /**
     * Make auth state
     *
     * @param Tenant $tenant
     * @param string $domain
     * @param string $ref
     * @return string
     */
    public function makeAuthState(Tenant $tenant, $domain, $ref = '');

    /**
     * Save the authenticated user
     *
     * @param Tenant $tenant
     * @param AuthenticatedUser $user
     * @return User
     */
    public function saveUser(Tenant $tenant, AuthenticatedUser $user);
}
