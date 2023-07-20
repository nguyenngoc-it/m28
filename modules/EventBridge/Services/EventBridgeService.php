<?php

namespace Modules\EventBridge\Services;

use Aws\Result;
use Gobiz\Log\LogService;
use Gobiz\Transformer\Commands\MakeTransformerManager;
use Gobiz\Transformer\TransformerManagerInterface;
use Illuminate\Support\Str;
use Modules\App\Services\AppException;
use Modules\EventBridge\Jobs\PutEventBridgeJob;
use Modules\Service;
use Psr\Log\LoggerInterface;
use Throwable;

class EventBridgeService implements EventBridgeServiceInterface
{
    /**
     * @var TransformerManagerInterface
     */
    protected $transformer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * EventBridgeService constructor
     */
    public function __construct()
    {
        $this->transformer = (new MakeTransformerManager(
            config('aws.event_bridge.transformers', []),
            config('aws.event_bridge.transformer_finders', []),
        ))->handle();

        $this->logger = LogService::logger('aws');
    }

    /**
     * Push event to queue for publish after
     *
     * @param EventBridge $event
     */
    public function queue(EventBridge $event)
    {
        dispatch(new PutEventBridgeJob($this->makeEventsInput($event)));
    }

    /**
     * Put event to event bridge
     *
     * @param EventBridge $event
     * @return Result
     * @throws Throwable
     */
    public function put(EventBridge $event)
    {
        return $this->putEvents($this->makeEventsInput($event));
    }

    /**
     * @param EventBridge $event
     * @return array
     */
    protected function makeEventsInput(EventBridge $event)
    {
        return [
            'Entries' => [
                [
                    'DetailType' => $event->getEventName(),
                    'Detail' => json_encode(array_merge($this->transformer->transform($event->getPayload()), [
                        'env' => config('app.env'),
                    ])),
                ],
            ],
        ];
    }

    /**
     * Put events
     *
     * @param array $input
     * @return Result
     * @throws Throwable
     */
    public function putEvents($input)
    {
        $uuid = Str::uuid();

        $input['Entries'] = array_map(function (array $entry) {
            return array_merge([
                'EventBusName' => config('aws.event_bridge.name'),
                'Source' => 'gobiz.'.config('app.name'),
            ], $entry);
        }, $input['Entries']);

        $this->logger->debug('cloudwatchevents.putEvents.request.'.$uuid, $input);

        try {
            $res = Service::app()->aws()->createCloudWatchEvents()->putEvents($input);
            $this->logger->info('cloudwatchevents.putEvents.response.'.$uuid, $res->toArray());

            if ($res->get('FailedEntryCount')) {
                throw new AppException(json_encode($res->toArray()));
            }

            return $res;
        } catch (Throwable $exception) {
            $this->logger->error('cloudwatchevents.putEvents.exception.'.$uuid, [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    /**
     * Get transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function getTransformer()
    {
        return $this->transformer;
    }
}
