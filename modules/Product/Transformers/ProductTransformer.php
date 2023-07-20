<?php
namespace Modules\Product\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Transformers\MerchantTransformer;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service\Transformers\ServicePriceTransformer;
use Modules\Service\Transformers\ServiceTransformer;
use Modules\User\Models\User;
use Modules\User\Transformers\UserTransformerNew;

class ProductTransformer extends TransformerAbstract
{

    protected $request;
	
	public function __construct()
	{
		$this->setAvailableIncludes(['creator', 'merchant', 'skus', 'services', 'service_prices']);
		$this->setDefaultIncludes(['marketplace']);
        $this->request = request()->all();
	}
	
	public function transform(Product $product)
	{
        $statusRequest = data_get($this->request, 'status');

        $status = $product->status;

        if ($statusRequest == Product::STATUS_STOP_SELLING) {
            $status = Product::STATUS_STOP_SELLING;
        }

	    return [
	        'id'          => (int) $product->id,
	        'name'        => $product->name,
	        'category_id' => $product->category_id,
	        'code'        => $product->code,
	        'weight'      => $product->weight,
	        'height'      => $product->height,
	        'width'       => $product->width,
	        'length'      => $product->length,
	        'description' => $product->description,
	        'images'      => $product->images,
	        'status'      => $status,
	        'created_at'  => $product->created_at,
	    ];
	}

	/**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCreator(Product $product)
    {
        $creator = $product->creator;
		if (!$creator) {
			$creator = new User();
		}

        return $this->item($creator, new UserTransformerNew);
    }

	/**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeMerchant(Product $product)
    {
        $merchant = $product->merchant;
		if (!$merchant) {
			$merchant = new Merchant();
		}

        return $this->item($merchant, new MerchantTransformer);
    }

	/**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeSkus(Product $product)
    {
        $statusRequest = data_get($this->request, 'status');
        if ($statusRequest) {
            $skus = $product->skus->filter(function (Sku $sku) use ($statusRequest) {
                return $sku->status == $statusRequest;
            });
        } else {
            $skus = $product->skus;
        }
        return $this->collection($skus, new SkuTransformer);
    }

	/**
     * Include Service
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeServices(Product $product)
    {
        $services = $product->services;
        return $this->collection($services, new ServiceTransformer);
    }

	/**
     * Include Service Price
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeServicePrices(Product $product)
    {
        $servicePrices = $product->servicePrices;
        return $this->collection($servicePrices, new ServicePriceTransformer);
    }

    /**
     * Include Service Price
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeMarketplace(Product $product)
    {
        $skus = $product->skus;
        $storeSkus = [];
        if ($skus) {
            foreach ($skus as $sku) {
                $storeSkus = $sku->storeSkus;
            }
        }

        if ($storeSkus) {
            return $this->collection($storeSkus, new StoreSkuTransformer);
        } else {
            return $this->null();
        }
    }
}