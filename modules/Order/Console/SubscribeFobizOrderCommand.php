<?php

namespace Modules\Order\Console;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Order\Jobs\HandleFobizOrderEventJob;

class SubscribeFobizOrderCommand extends Command
{
    protected $signature = 'order:subscribe-fobiz-order';

    protected $description = 'Subscribe topic fobiz order events';

    public function handle()
    {
        $logger = LogService::logger('fobiz-order-events');

        EventService::publicEventDispatcher()->subscribe('fobiz-orders', 'fobiz-order-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new HandleFobizOrderEventJob($message['payload']));
        });
    }

}
