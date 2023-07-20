<?php

namespace Modules\Location\Listeners\Kafka;

use Exception;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Psr\Log\LoggerInterface;

class LocationPublicEventSubscriberM32
{
    /** @var array $eventName */
    protected $appliedEvents = [
        'SYNC_LOCATION',
    ];
    /** @var array $payload */
    protected $payload = [];
    protected $event;

    /**
     * LocationPublicEventSubscriberM32 constructor.
     * @param array $inputs
     */
    public function __construct(array $inputs)
    {
        $this->logger()->info('event', $inputs);
        $this->payload = Arr::get($inputs, 'payload', []);
        $this->event   = Arr::get($inputs, 'event');
    }

    public function handle()
    {
        if (!in_array($this->event, $this->appliedEvents)) {
            $this->logger()->error('event_not_allow');
            return;
        }

        try {
            Location::updateOrCreate(
                [
                    'code' => Arr::get($this->payload, 'code'),
                    'type' => Arr::get($this->payload, 'type')
                ],
                [
                    'parent_code' => Arr::get($this->payload, 'parent_code'),
                    'label' => Arr::get($this->payload, 'label'),
                    'post_code' => Arr::get($this->payload, 'postal_code')
                ]
            );
        } catch (Exception $exception) {
            $this->logger()->error('exception: ' . $exception->getMessage());
        }

    }

    protected function logger(): LoggerInterface
    {
        return LogService::logger('m32-location-events');
    }
}
