<?php

namespace Modules\App\Console;

use Carbon\Carbon;
use Gobiz\Email\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Modules\Service;

class TestConnectionCommand extends Command
{
    protected $signature = 'app:test {service?}';

    protected $description = 'Test connections';

    public function handle()
    {
        $service = $this->argument('service');

        $services = $service ? [$service] : [
            'DB',
            'Mongo',
            'Redis',
            'Storage',
            'Email',
            'EventBridge',
        ];

        foreach ($services as $service) {
            $this->{'test'.$service}();
        }
    }

    protected function testDB()
    {
        $this->warn('DB: Connecting');
        DB::select('SHOW TABLES');
        $this->info('DB: Connected');
    }

    protected function testMongo()
    {
        $this->warn('Mongo: Connecting');
        DB::connection('mongodb')->getMongoDB()->listCollections();
        $this->info('Mongo: Connected');
    }

    protected function testRedis()
    {
        $this->warn('Redis: Connecting');
        Redis::connection()->set('test', time(), 10);
        Redis::connection()->get('test');
        Redis::connection()->del('test');
        $this->info('Redis: Connected');
    }

    protected function testStorage()
    {
        $this->warn("Storage: Connecting");
        Storage::disk('s3')->put('test.log', Carbon::now()->toDateTimeString(), 'public');
        $file = Storage::disk('s3')->url('test.log');
        $this->info("Storage: Connected - File: {$file}");
    }

    protected function testEmail($to = null)
    {
        $this->warn("Email: Sending");
        if (EmailService::email()->send($to ?: 'nguyensontung@gobiz.vn', 'Test', 'Test...')) {
            $this->info("Email: Sent");
        } else {
            $this->error("Email: Error");
        }
    }

    protected function testEventBridge()
    {
        $this->warn('EventBridge: Connecting');

        $res = Service::eventBridge()->putEvents([
            'Entries' => [
                [
                    'DetailType' => 'TestConnection',
                    'Detail' => json_encode([
                        'env' => config('app.env'),
                    ]),
                ],
            ],
        ]);

        $this->info("EventBridge: Connected, ".json_encode($res->toArray()));
    }
}
