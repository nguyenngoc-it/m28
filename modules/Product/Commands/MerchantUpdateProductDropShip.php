<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;

class MerchantUpdateProductDropShip extends UpdateProduct
{
    /**
     * @return Product
     */
    public function handle()
    {
        DB::transaction(function () {
            $this->updateBase(false);
            $this->updateImages();
            $this->updateServices();

            $this->oldProductCode = $this->product->code;

            $this->syncOptions();

            $this->syncSkus();

            $this->createDefaultSku();
        });

        if ($this->payloadLogs) {
            $this->product->logActivity(ProductEvent::UPDATE, $this->user, $this->payloadLogs);
        }

        return $this->product->refresh();

    }
}
