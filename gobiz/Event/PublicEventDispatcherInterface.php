<?php

namespace Gobiz\Event;

interface PublicEventDispatcherInterface
{
    /**
     * Publish event to the given topic
     *
     * @param string $topic
     * @param PublicEventInterface $event
     */
    public function publish($topic, PublicEventInterface $event);

    /**
     * Subscribe event of the given topics
     *
     * @param string|array $topics
     * @param string $groupId
     * @param callable $listener
     */
    public function subscribe($topics, $groupId, callable $listener);
}