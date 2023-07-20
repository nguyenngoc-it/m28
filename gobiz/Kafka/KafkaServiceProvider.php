<?php

namespace Gobiz\Kafka;

use Gobiz\Log\LogService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class KafkaServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(ConnectionManagerInterface::class, function () {
            return $this->makeConnectionManager();
        });
    }

    /**
     * @return ConnectionManager
     */
    protected function makeConnectionManager()
    {
        $connections = new ConnectionManager($this->app);

        foreach (config('kafka.connections') as $connection => $config) {
            $dispatcher = new Dispatcher($config['brokers'], LogService::logger('kafka'));
            $dispatcher->consumerGroupPrefix = config('kafka.consumer_group_prefix');
            $dispatcher->debug = config('kafka.debug');

            $connections->register($connection, $dispatcher);
        }

        return $connections;
    }

    public function provides()
    {
        return [
            ConnectionManagerInterface::class,
            DispatcherInterface::class,
        ];
    }
}