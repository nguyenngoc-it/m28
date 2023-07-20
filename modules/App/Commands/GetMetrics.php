<?php

namespace Modules\App\Commands;

use Gobiz\Queue\QueueService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class GetMetrics
{
    /**
     * @var string
     */
    protected $cacheKey = 'metrics';

    /**
     * @var int
     */
    protected $cacheTtl = 60; // seconds

    /**
     * @return string
     */
    public function handle()
    {
        if ($metrics = Cache::get($this->cacheKey)) {
            return $metrics;
        }

        $metrics = $this->makeMetrics();
        Cache::put($this->cacheKey, $metrics, $this->cacheTtl);

        return $metrics;
    }

    /**
     * @return string
     */
    protected function makeMetrics()
    {
        return implode(PHP_EOL, array_merge(
            $this->makeQueueSizeMetrics(),
            $this->makeQueueFailedJobsMetrics()
        ));
    }

    /**
     * @return array
     */
    protected function makeQueueSizeMetrics()
    {
        $res = [
            '# HELP queue_size The queue size.',
            '# TYPE queue_size gauge',
        ];

        foreach (QueueService::getQueues() as $queue) {
            $res[] = strtr('queue_size{connection="{connection}", queue="{queue}"} {value}', [
                '{connection}' => $queue['connection'],
                '{queue}' => $queue['queue'],
                '{value}' => Queue::connection($queue['connection'])->size($queue['queue']),
            ]);
        }

        return $res;
    }

    protected function makeQueueFailedJobsMetrics()
    {
        $res = [
            '# HELP queue_failed_jobs_total Total number of failed jobs.',
            '# TYPE queue_failed_jobs_total counter',
        ];

        $jobs = DB::table(config('queue.failed.table'))
            ->select(['connection', 'queue', DB::raw('COUNT(*) as count')])
            ->groupBy(['connection', 'queue'])
            ->get();

        foreach ($jobs as $job) {
            $res[] = strtr('queue_failed_jobs_total{connection="{connection}", queue="{queue}"} {value}', [
                '{connection}' => $job->connection,
                '{queue}' => $job->queue,
                '{value}' => $job->count,
            ]);
        }

        return $res;
    }
}
