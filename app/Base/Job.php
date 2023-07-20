<?php

namespace App\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

abstract class Job implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
}
