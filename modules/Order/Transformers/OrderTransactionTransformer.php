<?php
namespace Modules\Order\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Order\Models\OrderTransaction;

class OrderTransactionTransformer extends TransformerAbstract
{

	public function transform(OrderTransaction $orderTransaction)
	{	
	    return [
	        'order_id'     => (int) $orderTransaction->order_id,
	        'method'       => $orderTransaction->method,
	        'amount'       => (double) $orderTransaction->amount,
	        'payment_time' => $orderTransaction->payment_time,
	    ];
	}
}