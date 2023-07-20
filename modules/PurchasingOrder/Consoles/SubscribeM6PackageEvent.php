<?php

namespace Modules\PurchasingOrder\Consoles;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\PurchasingOrder\Jobs\SubscribingM6PackageJob;

/**
 * Subscriber kiện hàng từ bên m6
 *
 * Class SubscribePublicEvent
 * @package Modules\Order\Consoles
 */
class SubscribeM6PackageEvent extends Command
{
    protected $signature = 'purchasing_package:subscribe-m6-package-event';
    protected $description = 'Package subscribe public event';

    public function handle()
    {
        $logger = LogService::logger('m6-package-subscribed-events');

        EventService::publicEventDispatcher()->subscribe(['m6-package'], 'm28-package-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new SubscribingM6PackageJob($message['payload']));
        });
    }

}
