<?php

namespace Gobiz\Event;

use Gobiz\Kafka\DispatcherInterface;
use Gobiz\Transformer\TransformerManagerInterface;
use Illuminate\Support\Arr;

class PublicEventDispatcher implements PublicEventDispatcherInterface
{
    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var PublicEventSerializerInterface
     */
    protected $serializer;

    /**
     * @var TransformerManagerInterface
     */
    protected $transformer;

    /**
     * PublicEventDispatcher constructor
     *
     * @param DispatcherInterface $dispatcher
     * @param PublicEventSerializerInterface $serializer
     * @param TransformerManagerInterface $transformer
     */
    public function __construct(
        DispatcherInterface $dispatcher,
        PublicEventSerializerInterface $serializer,
        TransformerManagerInterface $transformer
    ) {
        $this->dispatcher = $dispatcher;
        $this->serializer = $serializer;
        $this->transformer = $transformer;
    }

    /**
     * Publish event to the given topic
     *
     * @param string $topic
     * @param PublicEventInterface $event
     */
    public function publish($topic, PublicEventInterface $event)
    {
        $this->dispatcher->publish($topic, $this->serializePayload($event), $event->getKey());
    }

    /**
     * @param PublicEventInterface $event
     * @return string
     */
    protected function serializePayload(PublicEventInterface $event)
    {
        return $this->serializer->serialize([
            'event' => $event->getName(),
            'payload' => $this->transformer->transform($event->getPayload()),
        ]);
    }

    /**
     * Subscribe event of the given topics
     *
     * @param string|array $topics
     * @param string $groupId
     * @param callable $listener
     */
    public function subscribe($topics, $groupId, callable $listener)
    {
        $this->dispatcher->subscribe($topics, $groupId, function ($message) use ($listener) {
            $listener([
                'offset' => $message->offset,
                'partition' => $message->partition,
                'topic' => $message->topic_name,
                'timestamp' => $message->timestamp,
                'key' => $message->key,
                'payload' => $this->serializer->unserialize($message->payload),
            ]);
        });
    }
}