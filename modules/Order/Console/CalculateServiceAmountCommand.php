<?php

namespace Modules\Order\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Order\Jobs\CalculateServiceAmount;
use Modules\Order\Models\Order;

class CalculateServiceAmountCommand extends Command
{
    protected $signature = 'order:calculate-service-amount
        {--batch=100}';

    protected $description = 'Calculate service amount';

    public function handle()
    {
        $query = Order::query();

        $this->info('Start');

        $query->select('id');
        $query->chunkById($this->option('batch'), function (Collection $orders) {
            $orders->map(function ($order) {
                dispatch(new CalculateServiceAmount($order->id));
            });
        });

        $this->info('Done');
    }

}