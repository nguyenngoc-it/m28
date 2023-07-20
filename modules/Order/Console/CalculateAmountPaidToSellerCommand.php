<?php

namespace Modules\Order\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Order\Jobs\CalculateAmountPaidToSeller;
use Modules\Order\Models\Order;

class CalculateAmountPaidToSellerCommand extends Command
{
    protected $signature = 'order:calculate-amount-paid-to-seller 
        {--batch=100}';

    protected $description = 'Calculate amount paid to seller';

    public function handle()
    {
        $query = Order::query();
        $this->info('Start');

        $query->select('id');
        $query->chunkById($this->option('batch'), function (Collection $orders) {
            $orders->map(function ($order) {
                dispatch(new CalculateAmountPaidToSeller($order->id));
            });
        });

        $this->info('Done');
    }
}