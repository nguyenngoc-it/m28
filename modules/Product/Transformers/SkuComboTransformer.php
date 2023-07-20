<?php

namespace Modules\Product\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryTransformerNew;
use Modules\Merchant\ExternalTransformers\MerchantTransformerNew;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Transformers\SkuTransformer;
use Modules\User\Models\User;
use Modules\User\Transformers\UserTransformerNew;

class SkuComboTransformer extends TransformerAbstract
{

    public function __construct()
	{
		$this->setDefaultIncludes(['category', 'creator', 'merchant', 'skus']);
	}

    public function transform(SkuCombo $skuCombo)
    {
        
        $images = (array) $skuCombo->image;
        $collection = collect($images);
        $filteredImages = $collection->reject(function ($value, $key) {
            return $value == '';
        })->all();
        
        return  [
            'id'          => $skuCombo->id,
            'code'        => $skuCombo->code,
            'name'        => $skuCombo->name,
            'image'       => array_values($filteredImages),
            'status'      => $skuCombo->status,
            'price'       => $skuCombo->price,
            'source'      => $skuCombo->source,
            'category_id' => $skuCombo->category_id,
            'merchant_id' => $skuCombo->merchant_id,
            'created_at'  => $skuCombo->created_at,
        ];
    }

    /**
     * Include Sku
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkus(SkuCombo $skuCombo)
    {
        $skuComboSkus = $skuCombo->skuComboSkus;

        return $this->collection($skuComboSkus, new SkuComboSkuTransformer);
    }

    /**
     * Include Merchant
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeMerchant(SkuCombo $skuCombo)
    {
        $merchant = $skuCombo->merchant;
		if (!$merchant) {
			$merchant = new Merchant();
		}

        return $this->item($merchant, new MerchantTransformerNew);
    }

    /**
     * Include creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCreator(SkuCombo $skuCombo)
    {
        $creator = $skuCombo->merchant->user;
		if (!$creator) {
			$creator = new User();
		}

        return $this->item($creator, new UserTransformerNew);
    }

    /**
     * Include category
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCategory(SkuCombo $skuCombo)
    {
        $category = $skuCombo->category;
		if (!$category) {
			$category = new Category();
		}

        return $this->item($category, new CategoryTransformerNew);
    }

}
