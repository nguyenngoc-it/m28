<?php

namespace Modules\Product\Jobs;

use App\Base\Job;

abstract class SkuQueueableListener extends Job
{
    public $queue = 'sku_listeners';
}
