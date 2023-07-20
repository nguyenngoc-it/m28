<?php

namespace App\Jobs;

class ExampleJob extends Job
{
    protected $test;

    /**
     * Create a new job instance.
     *
     * @param $test
     */
    public function __construct($test)
    {
        $this->test = $test;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo $this->test;
    }
}
