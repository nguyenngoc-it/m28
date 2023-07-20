<?php

namespace App\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

abstract class JobUniqueUntilProcessing implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, SerializesModels;
}
