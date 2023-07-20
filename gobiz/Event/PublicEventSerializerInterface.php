<?php

namespace Gobiz\Event;

interface PublicEventSerializerInterface
{
    /**
     * Serialize the event payload
     *
     * @param array $payload
     * @return string
     */
    public function serialize(array $payload);

    /**
     * Unserialize the event payload
     *
     * @param string $serializedPayload
     * @return array
     */
    public function unserialize($serializedPayload);
}