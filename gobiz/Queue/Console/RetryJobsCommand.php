<?php

namespace Gobiz\Queue\Console;

use Illuminate\Queue\Console\RetryCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetryJobsCommand extends RetryCommand
{
    protected $signature = 'queue:retry-jobs
        {queue : Retry all of the failed jobs for the specified queue}
        {--from= : Failed time from}
        {--to= : Failed time to}
        {--limit= : Limit jobs need to retry}
    ';

    /**
     * Get the job IDs to be retried.
     *
     * @return array
     */
    protected function getJobIds()
    {
        $query = DB::connection(config('queue.failed.database'))
            ->table(config('queue.failed.table'))
            ->where('queue', $this->argument('queue'));

        if ($from = $this->option('from')) {
            $query->where('failed_at', '>=', $from);
        }

        if ($to = $this->option('to')) {
            $to = Str::contains($to, ' ') ? $to : $to.' 23:59:59';
            $query->where('failed_at', '<=', $to);
        }

        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }

        return $query->pluck('id')->all();
    }
}
