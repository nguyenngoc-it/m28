<?php

namespace Gobiz\Activity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Jenssegers\Mongodb\Connection;
use Jenssegers\Mongodb\Query\Builder;
use MongoDB\BSON\UTCDateTime;

class MongoActivityLogger implements ActivityLoggerInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * MongoActivityLogRepository constructor
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function log(ActivityInterface $activity)
    {
        $time = $activity->getTime();
        $activity = $activity->getActivityAsArray();
        $activity['payload'] = $this->normalizePayload($activity['payload']);
        $objects = Arr::pull($activity, 'objects');

        $logId = null;
        foreach ($objects as $object => $objectId) {
            $logId = (string)$this->collection($object)->insertGetId(array_merge($activity, [
                "{$object}_id" => $objectId,
                'time' => new UTCDateTime($time->getTimestamp()*1000),
                'created_at' => new UTCDateTime(),
            ]));
        }

        return $logId;
    }

    /**
     * @param mixed $payload
     * @return array
     */
    protected function normalizePayload($payload)
    {
        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                $payload[$key] = $this->normalizePayload($value);
            }

            return $payload;
        }

        if ($payload instanceof Model) {
            return $payload->attributesToArray();
        }

        if (is_object($payload) && method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function get($object, $objectId, array $filter = [])
    {
        $query = $this->collection($object)
            ->where("{$object}_id", $objectId)
            ->orderByDesc('_id');

        if (isset($filter['action'])) {
            $query->whereIn('action', (array)$filter['action']);
        }

        return $query->get()
            ->map(function ($activity) {
                return $this->transformActivity($activity);
            })->all();
    }

    /**
     * @param string $object
     * @param string $activityId
     * @return array|null
     */
    public function find($object, $activityId)
    {
        $activity = $this->collection($object)->find($activityId);

        return $activity ? $this->transformActivity($activity) : null;
    }

    /**
     * @param array $activity
     * @return array
     */
    protected function transformActivity(array $activity)
    {
        $id = Arr::pull($activity, '_id');

        return array_merge($activity, [
            'id' => (string)$id,
            'time' => $this->toDateTime($activity['time']),
            'created_at' => $this->toDateTime($activity['created_at']),
        ]);
    }

    /**
     * @param UTCDateTime $utcDateTime
     * @return string
     */
    protected function toDateTime(UTCDateTime $utcDateTime)
    {
        return $utcDateTime->toDateTime()->setTimezone(Carbon::now()->getTimezone());
    }

    /**
     * @param $object
     * @return Builder
     */
    protected function collection($object)
    {
        return $this->connection->collection("{$object}_activities");
    }
}
