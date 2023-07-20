<?php

namespace Gobiz\Event;

use DateTimeInterface;
use Gobiz\Event\PublicEventSerializers\JsonSerializer;
use Gobiz\Kafka\DispatcherInterface;
use Gobiz\Kafka\KafkaService;
use Gobiz\Transformer\Commands\MakeTransformerManager;
use Gobiz\Transformer\TransformerManagerInterface;
use Gobiz\Transformer\Transformers\DateTimeIso8601ZuluTransformer;
use Gobiz\Transformer\Transformers\ModelTransformer;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider implements DeferrableProvider
{
    const PUBLIC_EVENT_TRANSFORMER = 'gobiz.event.public_event.transformer';

    public function register()
    {
        $this->app->singleton(static::PUBLIC_EVENT_TRANSFORMER, function () {
            return $this->makeTransformerManager();
        });

        $this->app->singleton(PublicEventDispatcherInterface::class, function () {
            return $this->makePublicEventDispatcher();
        });
    }

    /**
     * @return PublicEventDispatcher
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function makePublicEventDispatcher()
    {
        return new PublicEventDispatcher(
            $this->makeDispatcher(),
            new JsonSerializer(),
            $this->app->make(static::PUBLIC_EVENT_TRANSFORMER)
        );
    }

    /**
     * @return DispatcherInterface
     */
    protected function makeDispatcher()
    {
        return KafkaService::connections()->get(config('event.public_event.kafka_connection'));
    }

    /**
     * @return TransformerManagerInterface
     */
    protected function makeTransformerManager()
    {
        return (new MakeTransformerManager(
            config('event.public_event.transformers', []),
            config('event.public_event.transformer_finders', []),
            $this->makeDefaultTransformers()
        ))->handle();
    }

    /**
     * @return array
     */
    protected function makeDefaultTransformers()
    {
        $modelTransformer = new ModelTransformer();
        $modelTransformer->formatDateTime = false;

        return [
            Model::class => $modelTransformer,
            DateTimeInterface::class => new DateTimeIso8601ZuluTransformer(),
        ];
    }

    public function provides()
    {
        return [PublicEventDispatcherInterface::class];
    }
}
