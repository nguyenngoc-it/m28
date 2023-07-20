<?php

namespace Gobiz\Event;

use Gobiz\Transformer\TransformerManagerInterface;

class EventService
{
    /**
     * @return PublicEventDispatcherInterface
     */
    public static function publicEventDispatcher()
    {
        return app(PublicEventDispatcherInterface::class);
    }

    /**
     * @return TransformerManagerInterface
     */
    public static function publicEventTransformer()
    {
        return app(EventServiceProvider::PUBLIC_EVENT_TRANSFORMER);
    }
}
