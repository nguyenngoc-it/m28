<?php

namespace Gobiz\Queue;

interface UniqueQueueInterface
{
    /**
     * Push job vào queue
     *
     * @param UniqueJobInterface $job
     */
    public function push(UniqueJobInterface $job);

    /**
     * Kiểm tra job có nằm trong queue hay không
     *
     * @param UniqueJobInterface $job
     * @return bool
     */
    public function has(UniqueJobInterface $job);

    /**
     * Xóa job khỏi queue
     *
     * @param UniqueJobInterface $job
     */
    public function remove(UniqueJobInterface $job);
}