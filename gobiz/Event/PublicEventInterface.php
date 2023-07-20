<?php

namespace Gobiz\Event;

interface PublicEventInterface
{
    /**
     * Get the event name
     *
     * @return string
     */
    public function getName();

    /**
     * Get the event payload
     *
     * @return array
     */
    public function getPayload();

    /**
     * Get the event key
     *
     * @return string|null
     */
    public function getKey();
}