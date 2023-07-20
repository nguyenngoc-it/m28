<?php

namespace Gobiz\Redis\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Redis\RedisManager;

class TestRedisConnectionCommand extends Command
{
    protected $signature = 'redis:test {connection?}';

    protected $description = 'Test redis connection';

    public function handle()
    {
        $redis = $this->redis()->connection($this->argument('connection') ?: null);

        $this->info('Ping: ' . $redis->ping());
        $this->info('Set data: ' . $redis->set('test', Carbon::now()->toDateTimeString(), 'EX', 60));
        $this->info('Get data: ' . $redis->get('test'));
    }

    /**
     * @return RedisManager
     */
    protected function redis()
    {
        return app('redis');
    }
}