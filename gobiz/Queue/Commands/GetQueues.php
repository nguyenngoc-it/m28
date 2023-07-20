<?php

namespace Gobiz\Queue\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GetQueues
{
    /**
     * @return array
     */
    public function handle()
    {
        return ($queues = config('queue.queues')) ? $this->getFromConfig($queues) : $this->getFromWorkers();
    }

    /**
     * @param array $queueNames
     * @return array
     */
    protected function getFromConfig(array $queueNames)
    {
        $queues = [];
        foreach ($queueNames as $queue) {
            $queue = explode(':', $queue, 2);
            list($connection, $queue) = count($queue) === 1 ? [config('queue.default'), $queue[0]] : $queue;
            $queues[] = compact('connection', 'queue');
        }

        return $queues;
    }

    /**
     * @return array
     */
    protected function getFromWorkers()
    {
        $queues = [];

        $workers = File::glob(env('SUPERVISOR_WORKERS', app()->basePath('workers/*.conf')));
        foreach ($workers as $worker) {
            $contents = explode(PHP_EOL, file_get_contents($worker));

            Collection::make($contents)
                ->filter(function ($line) {
                    return Str::contains($line, 'artisan queue:work');
                })->map(function ($command) {
                    $command = explode('artisan queue:work', $command);
                    $command = Collection::make(explode(' ', $command[1]))->filter();

                    $args = $command->filter(function ($str) {
                        return !Str::startsWith($str, '-');
                    })->values();

                    $options = $command->filter(function ($str) {
                        return Str::startsWith($str, '-');
                    })->map(function ($option) {
                        list($name, $value) = array_pad(explode('=', $option), 2, null);
                        return compact('name', 'value');
                    });

                    $queues = $options->firstWhere('name', '--queue');
                    $queues = $queues ? explode(',', $queues['value']) : ['default'];

                    return [
                        'connection' => $args->get(0) ?: config('queue.default'),
                        'queues' => $queues,
                    ];
                })->each(function (array $command) use (&$queues) {
                    foreach ($command['queues'] as $queue) {
                        $queues[] = [
                            'connection' => $command['connection'],
                            'queue' => $queue,
                        ];
                    }
                });
        }

        return $queues;
    }
}
