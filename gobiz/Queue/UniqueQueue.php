<?php

namespace Gobiz\Queue;

use Gobiz\Sets\SetsInterface;

class UniqueQueue implements UniqueQueueInterface
{
    /**
     * @var SetsInterface
     */
    protected $sets;

    /**
     * Expire của job nằm trong queue (seconds)
     *
     * @var int
     */
    protected $expire = 300;

    /**
     * UniqueQueue constructor
     *
     * @param SetsInterface $sets
     * @param int $expire
     */
    public function __construct(SetsInterface $sets, $expire = null)
    {
        $this->sets = $sets;
        $this->expire = is_null($expire) ? $this->expire : $expire;
    }

    /**
     * Push job vào queue
     *
     * @param UniqueJobInterface $job
     */
    public function push(UniqueJobInterface $job)
    {
        if ($this->has($job)) {
            return;
        }

        dispatch($job);

        $this->markQueued($job);
    }

    /**
     * @param UniqueJobInterface $job
     */
    protected function markQueued(UniqueJobInterface $job)
    {
        $this->sets->push($job->getJobId(), $this->expire);
    }

    /**
     * Kiểm tra job có nằm trong queue hay không
     *
     * @param UniqueJobInterface $job
     * @return bool
     */
    public function has(UniqueJobInterface $job)
    {
        return $this->sets->has($job->getJobId());
    }

    /**
     * Xóa job khỏi queue
     *
     * @param UniqueJobInterface $job
     */
    public function remove(UniqueJobInterface $job)
    {
        $this->sets->remove($job->getJobId());
    }
}