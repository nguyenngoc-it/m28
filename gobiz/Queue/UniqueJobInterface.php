<?php

namespace Gobiz\Queue;

interface UniqueJobInterface
{
    /**
     * Get the id of job
     *
     * @return string
     */
    public function getJobId();
}