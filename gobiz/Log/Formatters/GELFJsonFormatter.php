<?php

namespace Gobiz\Log\Formatters;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Psr\Log\LogLevel;
use Throwable;
use Traversable;

class GELFJsonFormatter extends MonologJsonFormatter
{
    /**
     * Mapping Monolog log levels to Graylog2 log priorities.
     */
    public static $logLevels = [
        LogLevel::DEBUG     => 7,
        LogLevel::INFO      => 6,
        LogLevel::NOTICE    => 5,
        LogLevel::WARNING   => 4,
        LogLevel::ERROR     => 3,
        LogLevel::CRITICAL  => 2,
        LogLevel::ALERT     => 1,
        LogLevel::EMERGENCY => 0,
    ];

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        return parent::format($this->processRecord($record));
    }

    /**
     * @param array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        /**
         * @var DateTimeInterface $datetime
         */
        $datetime = $record['datetime'];
        $levelName = strtolower($record['level_name']);

        $record['timestamp'] = (float)$datetime->format('U.u');
        $record['level'] = Arr::get(static::$logLevels, $levelName, static::$logLevels[LogLevel::ALERT]);

        $sortedRecord = [];
        foreach (['datetime', 'channel', 'level_name', 'message'] as $field) {
            $sortedRecord[$field] = Arr::pull($record, $field);
        }

        return array_merge($sortedRecord, $record);
    }

    /**
     * Normalizes given $data
     *
     * @param mixed $data
     * @param int $depth
     * @return mixed
     */
    protected function normalize($data, $depth = 0)
    {
        if (is_array($data) || $data instanceof Traversable) {
            $normalized = [];

            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value);
            }

            return $normalized;
        }

        if ($data instanceof Throwable) {
            return [
                'class' => get_class($data),
                'message' => $data->getMessage(),
                'code' => $data->getCode(),
                'file' => $data->getFile(),
                'line' => $data->getLine(),
            ];
        }

        if ($data instanceof DateTimeInterface) {
            return Carbon::createFromTimestamp($data->getTimestamp())->toIso8601ZuluString();
        }

        return $data;
    }
}
