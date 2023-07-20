<?php

namespace Gobiz\Kafka\Console;

use Gobiz\Kafka\KafkaService;
use Illuminate\Console\Command;

class ExampleSubscribeCommand extends Command
{
    protected $signature = 'kafka:sub {topic=test} {consumer=test} {--connection=}';

    protected $description = 'Test subscribe message';

    public function handle()
    {
        $topic = $this->argument('topic');
        $consumer = $this->argument('consumer');
        $connection = $this->option('connection');

        $this->info("Subscribe - Topic: {$topic} - Consumer: {$consumer} - Connection: " . ($connection ?: 'default'));

        KafkaService::dispatcher($connection)->subscribe($topic, $consumer, function ($message) {
            $this->info(print_r($message, true));
        });
    }
}
