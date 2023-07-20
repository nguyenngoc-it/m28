<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(UserServiceInterface::class, function () {
            return new UserService();
        });
    }

    public function provides()
    {
        return [UserServiceInterface::class];
    }
}
