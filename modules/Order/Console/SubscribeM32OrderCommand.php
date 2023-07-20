<?php

namespace Modules\Order\Console;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Order\Jobs\HandleM32OrderEventJob;

class SubscribeM32OrderCommand extends Command
{
    protected $signature = 'order:subscribe-m32-order';

    protected $description = 'Subscribe topic m32 order events';

    public function handle()
    {
        $logger = LogService::logger('m32-order-events');

        EventService::publicEventDispatcher()->subscribe('m32-order', 'm28-order-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new HandleM32OrderEventJob($message['payload']));
        });
    }

}
