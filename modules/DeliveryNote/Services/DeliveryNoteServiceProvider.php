<?php

namespace Modules\DeliveryNote\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DeliveryNoteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(DeliveryNoteServiceInterface::class, DeliveryNoteService::class);
    }

    public function provides()
    {
        return [DeliveryNoteServiceInterface::class];
    }
}
