<?php

namespace Modules\Order\Observers;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Jobs\CalculateAmountPaidToSeller;
use Modules\Order\Jobs\OrderAmountChangeJob;
use Modules\Order\Jobs\UpdateFreightBillShippingPartnerJob;
use Modules\Order\Jobs\UpdateLocationShippingPartnerJob;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\OrderEvent;
use Modules\OrderIntegration\PublicEvents\OrderUpdated;
use Gobiz\Log\LogService;
use Modules\Tenant\Models\TenantSetting;

class OrderObserver
{
    /**
     * Handle to the Order "created" event.
     *
     * @param  Order  $order
     * @return void
     */
    public function created(Order $order)
    {
        dispatch(new CalculateAmountPaidToSeller($order->id));
    }

    /**
     * Handle the Order "updated" event.
     *
     * @param  Order $order
     * @return void
     */
    public function updated(Order $order)
    {
        $changed = $order->getChanges();

        if(isset($changed['status'])) {
            try {
                (new OrderUpdated($order, OrderEvent::CHANGE_STATUS))->publish();
            } catch (\Exception $exception) {
                LogService::logger('order_observer')->info($exception->getMessage() . ' '.$order->code);
            }
        }

        $calculateAmountPaidToSeller = false;
        foreach (['paid_amount', 'service_amount', 'cod_fee_amount', 'cod_fee_amount', 'shipping_amount', 'other_fee', 'cost_of_goods'] as $p) {
            if(isset($changed[$p])) {
                $calculateAmountPaidToSeller = true;
            }
        }

        if(isset($changed['shipping_partner_id']) && !empty($changed['shipping_partner_id'])) {
            dispatch(new UpdateLocationShippingPartnerJob($order->id));
            dispatch(new UpdateFreightBillShippingPartnerJob($order->id));
        }

        if($calculateAmountPaidToSeller) {
            dispatch(new CalculateAmountPaidToSeller($order->id));
        }

        $this->publishOrderAmountToKafka($order);
    }
        protected function publishOrderAmountToKafka(Order $order)
    {
        if(!(int)$order->tenant->getSetting(TenantSetting::PUBLISH_EVENT_ORDER_CHANGE_AMOUNT)) {
            return false;
        }

        $amountChange = [];
        foreach ([
                     'total_amount', 'paid_amount', 'debit_amount', 'cod', 'extent_service_amount',
                     'cod_fee_amount', 'delivery_fee', 'other_fee', 'service_import_return_goods_amount',
                     'service_amount', 'amount_paid_to_seller'
                 ] as $p) {
            if($order->isDirty($p)) {
                $amountChange[$p] = [
                    'from' => $order->getOriginal($p),
                    'to'   => $order->{$p}
                ];
            }
        }

        if(!empty($amountChange)) {
            try {
                (new OrderUpdated($order, OrderEvent::CHANGE_AMOUNT, $amountChange))->publish();
            } catch (\Exception $exception) {
                LogService::logger('order_observer')->info('change_amount event error '.$exception->getMessage() . ' '.$order->code, $amountChange);
            }
        }
    }
}












