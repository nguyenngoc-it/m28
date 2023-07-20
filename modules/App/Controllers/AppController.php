<?php

namespace Modules\App\Controllers;

use Laravel\Lumen\Routing\Controller;
use Modules\App\Commands\GetMetrics;

class AppController extends Controller
{
    public function metrics()
    {
        return response((new GetMetrics())->handle())->header('Content-Type', 'text/plain');
    }
}
