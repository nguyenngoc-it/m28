<?php

namespace Modules\Transaction\Jobs;

use App\Base\Job;
use Modules\Transaction\Models\Transaction;
use Gobiz\Log\LogService;
use Psr\Log\LoggerInterface;

class ProcessTransactionJob extends Job
{
    public $queue = 'transaction';

    /**
     * @var string
     */
    protected $transactionId;


    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * ProcessTransactionJob constructor
     *
     * @param string $transactionId
     */
    public function __construct(string $transactionId)
    {
        $this->transactionId = $transactionId;
    }


    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        if($this->logger === null) {
            $this->logger = LogService::logger('process_transaction_job');
        }
        return $this->logger;
    }

    public function handle()
    {
        $this->logger()->info('start '.$this->transactionId);

        Transaction::find($this->transactionId)->process();
    }
}
