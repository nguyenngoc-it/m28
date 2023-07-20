<?php

namespace Modules\Location\Consoles;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\Location\Jobs\SubscribingM32LocationJob;

/**
 * Subscriber kiện hàng từ bên m6
 *
 * Class SubscribePublicEvent
 * @package Modules\Order\Consoles
 */
class SubscribeM32LocationEvent extends Command
{
    protected $signature = 'location:subscribe-m32-location-event';
    protected $description = 'Location subscribe public event';

    public function handle()
    {
        $logger = LogService::logger('m32-location-subscribed-events');

        EventService::publicEventDispatcher()->subscribe(['m32-locations'], 'm32-location-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', $message);

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new SubscribingM32LocationJob($message['payload']));
        });
    }

}
