<?php
namespace Modules\Product\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\StoreSku;

class StoreSkuTransformer extends TransformerAbstract
{
	public function __construct()
    {
        $this->setDefaultIncludes([]);
    }

	public function transform(StoreSku $storeSku)
	{
        $name = $this->getNameText($storeSku->marketplace_code);
	    return [
	        'id'   => (int) $storeSku->id,
	        'code' => $storeSku->marketplace_code,
	        'name' => $name,
	    ];
	}

    protected function getNameText($code)
    {
        $name = '';
        switch ($code) {
            case Marketplace::CODE_FOBIZ:
                $name = 'Fobiz';
                break;
            case Marketplace::CODE_KIOTVIET:
                $name = 'Kiot Việt';
                break;
            case Marketplace::CODE_LAZADA:
                $name = 'Lazada';
                break;
            case Marketplace::CODE_SAPO:
                $name = 'Sapo';
                break;
            case Marketplace::CODE_SHOPBASE:
                $name = 'Shopbase';
                break;
            case Marketplace::CODE_SHOPEE:
                $name = 'Shopee';
                break;
            case Marketplace::CODE_TIKI:
                $name = 'Tiki';
                break;
            case Marketplace::CODE_TIKTOKSHOP:
                $name = 'Tiktok Shop';
                break;
            case Marketplace::CODE_VELAONE:
                $name = 'Velaone';
                break;
            
            default:
                $name = '';
                break;
        }
        return $name;
    }
}