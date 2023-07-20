<?php

namespace App\Services\Log;

use Closure;
use Psr\Log\LoggerInterface;

interface LogServiceInterface
{
    /**
     * Log time execute
     *
     * @param Closure $handler
     * @param LoggerInterface $logger
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function logTimeExecute(Closure $handler, LoggerInterface $logger, $message = '', array $context = []);
}