<?php

namespace Gobiz\Log;

use Gobiz\Log\Formatters\GELFJsonFormatter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory implements LoggerFactoryInterface
{
    /**
     * @var string
     */
    protected $storagePath;

    /**
     * LoggerFactory constructor
     *
     * @param string $storagePath
     */
    public function __construct($storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
    }

    /**
     * Make the new logger
     *
     * @param string $name
     * @param array $options
     * @return LoggerInterface
     */
    public function make($name, array $options = [])
    {
        $name = Str::kebab($name);
        $logger = new Logger($name);

        $logger->pushHandler($this->makeRotatingFileHandler($name));

        $logger->pushProcessor(function ($record) use ($options) {
            return array_merge($record, [
                'context' => array_merge(Arr::get($options, 'context', []), $record['context']),
            ]);
        });

        return $logger;
    }

    /**
     * Make the daily file log handler
     *
     * @param string $name
     * @return RotatingFileHandler
     */
    protected function makeRotatingFileHandler($name)
    {
        $rotatingFileHandler = new RotatingFileHandler($this->getLogFile($name), 0, Logger::DEBUG, true, 0777);
        $rotatingFileHandler->setFormatter(new GELFJsonFormatter());

        return $rotatingFileHandler;
    }

    /**
     * Get the path of the log file
     *
     * @param string $name
     * @return string
     */
    protected function getLogFile($name)
    {
        return $this->storagePath . '/' . $name . '.log';
    }
}