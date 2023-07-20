<?php

namespace Modules\Marketplace\Transformers;

use App\Base\Transformer;
use Modules\Marketplace\Services\OAuthConnectable;
use Modules\Marketplace\Services\MarketplaceInterface;

class MarketplaceTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param MarketplaceInterface $marketplace
     * @return mixed
     */
    public function transform($marketplace)
    {
        return [
            'code' => $marketplace->getCode(),
            'name' => $marketplace->getName(),
            'connection_method' => $marketplace instanceof OAuthConnectable ? 'OAUTH' : null,
        ];
    }
}
