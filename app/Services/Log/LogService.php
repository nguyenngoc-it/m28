<?php

namespace App\Services\Log;

use Closure;
use Psr\Log\LoggerInterface;

class LogService implements LogServiceInterface
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
    public function logTimeExecute(Closure $handler, LoggerInterface $logger, $message = '', array $context = [])
    {
        $start = microtime(true);
        $logger->info("{$message} Start", $context);
        $result = $handler();
        $time = microtime(true) - $start;
        $logger->info("{$message} End: {$time}", $context);

        return $result;
    }
}