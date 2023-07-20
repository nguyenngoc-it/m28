<?php

namespace Modules\Order\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\Order;
use Modules\Order\Services\StatusOrder;
use Modules\Product\Models\Sku;

class ListFinanceTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Order $order
     * @return mixed
     */
    public function transform($order)
    {
        $skus = $order->skus;
        $sku  = $skus->first();

        $saleAmount = 0;
        $saleExpectedAmount = 0;
        $totalReturnAmount = 0;
        if(in_array($order->status, [
            Order::STATUS_DELIVERING, Order::STATUS_PART_DELIVERED,
            Order::STATUS_DELIVERED, Order::STATUS_FINISH,
        ])) {
            $saleAmount = $order->cod;
        }

        if(in_array($order->status, StatusOrder::getBeforeStatus(Order::STATUS_DELIVERING))) {
            $saleExpectedAmount = $order->cod;
        }
        if(in_array($order->status, [Order::STATUS_RETURN, Order::STATUS_RETURN_COMPLETED])) {
            $totalReturnAmount = $order->cod;
        }


        $grossProfit = ($order->paid_amount - $order->shipping_amount - $order->extent_service_amount);

        return array_merge($order->only(['currency']), [
            'order' => $order,
            'skus'  => $skus,
            'sku'   => $sku,
            'product' => ($sku instanceof Sku) ? $sku->product : '',
            'service_amount' => $order->service_amount,
            'remaining_amount' => $order->amount_paid_to_seller,
            'return_amount' => $totalReturnAmount, // số tiền hoàn
            'sale_amount' => $saleAmount,// doanh số bán hàng thực tế,
            'sale_expected_amount' => $saleExpectedAmount,// doanh số bán hàng dự kiến
            'gross_profit' => $grossProfit, // lợi nhuận gộp  (lấy doanh thu - chi phí VC - chi phí vận hành)
        ]);
    }
}