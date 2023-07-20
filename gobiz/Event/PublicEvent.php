<?php

namespace Gobiz\Event;

use Illuminate\Support\Str;

abstract class PublicEvent implements PublicEventInterface
{
    /**
     * Get the event name
     *
     * @return string
     */
    public function getName()
    {
        return Str::snake(class_basename($this));
    }
}