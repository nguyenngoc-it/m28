<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;

class UpdateMerchantProduct extends UpdateProductBase
{
    /**
     * @return Product
     */
    public function handle()
    {
        DB::transaction(function () {
            $this->updateBase();
            $this->updateImages();
            $this->updateServices();
        });

        (new ProductUpdated($this->product->id, $this->user->id, $this->payloadLogs))->queue();

        return $this->product->refresh();

    }
}
