<?php

namespace Modules\Merchant\Consoles;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Merchant\Models\Merchant;
use Modules\Stock\Models\Stock;

class SetStoragedAt extends Command
{
    protected $signature = 'merchant:set_storaged_at';
    protected $description = 'Cập nhật ngày bắt đầu sử dụng dịch vụ lưu kho cho seller';

    public function handle()
    {
        $sellers = Merchant::query()->where('status', true)->get();
        /** @var Merchant $seller */
        foreach ($sellers as $seller) {
            $storagedAt = $this->getStoragedAtOfSeller($seller);
            if ($storagedAt) {
                $seller->storaged_at = $storagedAt;
                $seller->save();
                $this->info('Setted storaged At for seller ' . $seller->name);
            }
        }
    }

    /**
     * @param Merchant $seller
     *
     * @return Carbon|null
     */
    private function getStoragedAtOfSeller(Merchant $seller)
    {
        /** @var Stock $firstStock */
        $firstStock = Stock::query()->select('stocks.*')->join('skus', 'stocks.sku_id', 'skus.id')
            ->where('skus.merchant_id', $seller->id)
            ->orderBy('stocks.created_at')->first();
        if ($firstStock) {
            return $firstStock->created_at;
        }

        return null;
    }
}
