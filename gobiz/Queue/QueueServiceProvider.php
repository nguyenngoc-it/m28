<?php

namespace Gobiz\Queue;

use Gobiz\Sets\RedisSets;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(UniqueQueueInterface::class, function () {
            return new UniqueQueue(new RedisSets(app('redis')), config('queue.unique_job_expire'));
        });
    }

    public function provides()
    {
        return [UniqueQueueInterface::class];
    }
}