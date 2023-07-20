<?php

namespace Gobiz\Kafka;

interface DispatcherInterface
{
    /**
     * Publish message to the given topic
     *
     * @param string $topic
     * @param string $payload
     * @param null|string $key
     */
    public function publish($topic, $payload, $key = null);

    /**
     * Subscribe message of the given topics
     *
     * @param string|array $topics
     * @param string $groupId
     * @param callable $listener
     */
    public function subscribe($topics, $groupId, callable $listener);
}