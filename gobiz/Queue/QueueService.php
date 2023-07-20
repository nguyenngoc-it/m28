<?php

namespace Gobiz\Queue;

use Gobiz\Queue\Commands\GetQueues;

class QueueService
{
    /**
     * @return UniqueQueueInterface
     */
    public static function uniqueQueue()
    {
        return app(UniqueQueueInterface::class);
    }

    /**
     * @param object $job
     * @return mixed
     */
    public static function push($job)
    {
        return (config('queue.unique_job') && $job instanceof UniqueJobInterface)
            ? static::uniqueQueue()->push($job)
            : dispatch($job);
    }

    /**
     * @return array
     */
    public static function getQueues()
    {
        return (new GetQueues())->handle();
    }
}