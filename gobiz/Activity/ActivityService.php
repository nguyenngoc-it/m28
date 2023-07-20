<?php

namespace Gobiz\Activity;

class ActivityService
{
    /**
     * @return ActivityLoggerInterface
     */
    public static function logger()
    {
        return app(ActivityLoggerInterface::class);
    }

    /**
     * @param ActivityInterface $activity
     * @return string
     */
    public static function log(ActivityInterface $activity)
    {
        return static::logger()->log($activity);
    }
}