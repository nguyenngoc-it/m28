<?php
namespace Modules\Order\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderSkuCombo;
use Modules\Order\Models\OrderSkuComboSku;
use Modules\Product\Transformers\SkuComboTransformer;

class OrderSkuComboTransformer extends TransformerAbstract
{
    public function __construct()
	{
		$this->setAvailableIncludes(['sku_combo', 'order_skus']);
	}

	public function transform(OrderSkuCombo $orderSkuCombo)
	{	
	    return [
	        'price'    => $orderSkuCombo->price,
	        'quantity' => $orderSkuCombo->quantity,
	    ];
	}

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkuCombo(OrderSkuCombo $orderSkuCombo)
    {
        $skuCombo = $orderSkuCombo->skuCombo;

        return $this->item($skuCombo, new SkuComboTransformer);
    }

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeOrderSkus(OrderSkuCombo $orderSkuCombo)
    {
        $skus = $orderSkuCombo->skuCombo->skus;
        $skuIds = [];
        foreach ($skus as $sku) {
           $skuIds[] = $sku->id;
        }
        $OrderSkuComboSku = OrderSkuComboSku::where('order_id', $orderSkuCombo->order_id)
                               ->where('sku_combo_id', $orderSkuCombo->sku_combo_id)
                               ->whereIn('sku_id', $skuIds)
                               ->get();

        if ($OrderSkuComboSku) {
            return $this->collection($OrderSkuComboSku, new OrderSkuComboSkuTransformer);
        } else {
            return $this->null();
        }
    }
}