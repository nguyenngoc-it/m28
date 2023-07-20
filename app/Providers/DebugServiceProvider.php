<?php

namespace App\Providers;

use Gobiz\Log\LogService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    public function boot()
    {
        if (env('DEBUG_SQL')) {
            $logger = LogService::logger('sql');

            DB::listen(function (QueryExecuted $query) use ($logger) {
                try {
                    $sql = str_replace(['?'], ['\'%s\''], $query->sql);
                    $sql = vsprintf($sql, $query->bindings);
                    $logger->debug($sql, ['time' => $query->time]);
                } catch (\Throwable $exception) {
                    $logger->debug($query->sql, [
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}
