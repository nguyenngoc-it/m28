<?php

namespace Gobiz\Activity;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/activity.php', 'activity');

        $this->app->singleton(ActivityLoggerInterface::class, function () {
            return new MongoActivityLogger(DB::connection(config('activity.mongo_connection')));
        });
    }

    public function provides()
    {
        return [ActivityLoggerInterface::class];
    }
}