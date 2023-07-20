<?php

namespace Gobiz\Activity;

interface ActivityLoggerInterface
{
    /**
     * Log activity
     *
     * @param ActivityInterface $activity
     * @return string
     */
    public function log(ActivityInterface $activity);

    /**
     * Get log activities
     *
     * @param string $object
     * @param string $objectId
     * @param array $filter Ex: ['action' => ['A1', 'A2']]
     * @return array
     */
    public function get($object, $objectId, array $filter = []);

    /**
     * Find log activity
     *
     * @param string $object
     * @param string $activityId
     * @return array|null
     */
    public function find($object, $activityId);
}