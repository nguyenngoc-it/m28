<?php

namespace Modules\Auth\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Modules\Tenant\Models\Tenant;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\User\Models\User;
use Modules\User\Models\UserIdentity;

class SaveAuthenticatedUser
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var AuthenticatedUser
     */
    protected $authenticatedUser;

    /**
     * SaveAuthenticatedUser constructor
     *
     * @param Tenant $tenant
     * @param AuthenticatedUser $authenticatedUser
     */
    public function __construct(Tenant $tenant, AuthenticatedUser $authenticatedUser)
    {
        $this->tenant = $tenant;
        $this->authenticatedUser = $authenticatedUser;
    }

    /**
     * @return User
     * @throws Exception
     */
    public function handle()
    {
        $user = $this->saveUser();
        $this->saveUserIdentity($user);

        return $user;
    }

    /**
     * @return User|object
     * @throws Exception
     */
    protected function saveUser()
    {
        $tenant = $this->tenant;
        $authenticatedUser = $this->authenticatedUser;
        $data = array_filter([
            'email' => $authenticatedUser->getEmail(),
            'name' => $authenticatedUser->getName(),
            'phone' => Arr::get($authenticatedUser->getRaw(), 'phone'),
            'avatar' => $authenticatedUser->getAvatar(),
        ]);
        $data['permissions'] = Arr::get($authenticatedUser->getRaw(), 'permissions') ?: [];
        $data['synced_at'] = new Carbon();

        return User::query()->updateOrCreate([
            'tenant_id' => $tenant->id,
            'username' => $authenticatedUser->getNickname(),
        ], $data);
    }

    /**
     * @param User $user
     * @return UserIdentity|object
     */
    protected function saveUserIdentity(User $user)
    {
        $authenticatedUser = $this->authenticatedUser;

        return UserIdentity::query()->updateOrCreate([
            'user_id' => $user->id,
            'source' => UserIdentity::SOURCE_GOBIZ,
        ], array_filter([
            'source_user_id' => $authenticatedUser->getId(),
            'source_user_info' => $authenticatedUser->getRaw(),
            'access_token' => $authenticatedUser->token,
            'refresh_token' => $authenticatedUser->refreshToken,
        ]));
    }
}
