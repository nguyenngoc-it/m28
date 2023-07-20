<?php

namespace Gobiz\Kafka\Console;

use Gobiz\Kafka\KafkaService;
use Illuminate\Console\Command;

class ExamplePublishCommand extends Command
{
    protected $signature = 'kafka:pub {topic=test} {--connection=}';

    protected $description = 'Test publish message';

    public function handle()
    {
        $topic = $this->argument('topic');
        $connection = $this->option('connection');

        KafkaService::dispatcher($connection)->publish($topic, $message = json_encode(['time' => date('Y-m-d H:i:s')]));
        $this->info("Published - Topic: {$topic} - Message: {$message} - Connection: " . ($connection ?: 'default'));
    }
}
