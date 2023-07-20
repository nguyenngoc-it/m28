<?php

namespace Modules\PurchasingOrder\Consoles;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\PurchasingOrder\Jobs\SubscribingM2OrderJob;

/**
 * Subscriber đơn hàng từ bên m2
 *
 * Class SubscribePublicEvent
 * @package Modules\Order\Consoles
 */
class SubscribeM2OrderEvent extends Command
{
    protected $signature = 'purchasing_order:subscribe-m2-order-event';
    protected $description = 'Order subscribe public event';

    public function handle()
    {
        $logger = LogService::logger('m2-order-subscribed-events');

        EventService::publicEventDispatcher()->subscribe(['m2-order', 'm2-shiping'], 'm28-order-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new SubscribingM2OrderJob($message['payload']));
        });
    }

}
