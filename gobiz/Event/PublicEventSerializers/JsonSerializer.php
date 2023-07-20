<?php

namespace Gobiz\Event\PublicEventSerializers;

use Gobiz\Event\PublicEventSerializerInterface;

class JsonSerializer implements PublicEventSerializerInterface
{
    /**
     * Serialize the event payload
     *
     * @param array $payload
     * @return string
     */
    public function serialize(array $payload)
    {
        return json_encode($payload);
    }

    /**
     * Unserialize the event payload
     *
     * @param string $serializedPayload
     * @return array
     */
    public function unserialize($serializedPayload)
    {
        return json_decode($serializedPayload, true);
    }
}