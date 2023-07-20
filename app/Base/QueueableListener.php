<?php

namespace App\Base;

abstract class QueueableListener extends Job
{
    public $queue = 'listeners';
}
