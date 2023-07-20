<?php

namespace Modules\Product\Services;

use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Tenant\Services\WebhookEvent;

class SkuWebhookEventPublisher
{
    /**
     * @var Sku
     */
    protected $sku;

    /**
     * SkuWebhookEventPublisher constructor
     *
     * @param Sku $sku
     */
    public function __construct(Sku $sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return WebhookEvent
     */
    public function changeStock()
    {
        $product = $this->sku->product;
        $stocks = $this->sku->stocks;

        $stocks->load(['warehouse']);

        return $this->sku->webhookEvent(SkuEvent::CHANGE_STOCK, [
            'product_code' => $product->code,
            'merchant_code' => $product->merchant ? $product->merchant->code : null,
            'stocks' => $stocks->map(function (Stock $stock) {
                return [
                    'stock' => $stock,
                    'warehouse_code' => $stock->warehouse->code,
                ];
            }),
        ], $this->getOwner());
    }

    /**
     * @return string
     */
    protected function getOwner()
    {
        return $this->sku->creator->username;
    }
}
