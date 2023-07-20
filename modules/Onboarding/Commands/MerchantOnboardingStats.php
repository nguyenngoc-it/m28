<?php

namespace Modules\Onboarding\Commands;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class MerchantOnboardingStats
 * @package Modules\Onboarding\Commands
 */
class MerchantOnboardingStats
{
    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var User
     */
    protected $creator;

    /**
     * MerchantStats constructor.
     * @param array $filter
     * @param User $creator
     */
    public function __construct(array $filter, User $creator)
    {
        $this->filter  = $filter;
        $this->creator = $creator;
    }

    /**
     * @return array
     */
    public function handle()
    {
        $query  = Service::order()->query($this->filter)->getQuery();
        $orders = $query->orderBy('created_at', 'asc')->get();

        $cod                = 0;
        $expectedCod        = 0;
        $expectedOrderFee   = 0;
        $expectedImportFee  = Service::purchasingPackage()->query($this->filter)
            ->getQuery()->where('finance_status', PurchasingPackage::FINANCE_STATUS_UNPAID)->sum('service_amount');

        $totalOrder      = $orders->count();
        $totalDelivering = 0;
        $totalDelivered  = 0;
        $totalCostPrice  = 0;
        $totalReturned   = 0;
        $stats           = [];

        /** @var Order $order */
        foreach ($orders as $order) {
            $cod            += $order->cod;

            $orderDelivering = 0;
            $orderDelivered  = 0;
            $orderReturned   = 0;
            $orderCostPrice  = 0;

            if(in_array($order->status, [Order::STATUS_RETURN, Order::STATUS_RETURN_COMPLETED])) {
                $totalReturned += 1;
                $orderReturned = 1;
            } else if(in_array($order->status, [Order::STATUS_DELIVERED])) {
                $totalDelivered += 1;
                $orderDelivered = 1;
            } else if(in_array($order->status, [Order::STATUS_DELIVERING])) {
                $orderDelivering = 1;
                $totalDelivering += 1;
            }

            if(
                $order->finance_status == Order::FINANCE_STATUS_UNPAID &&
                !in_array($order->status, [Order::STATUS_RETURN, Order::STATUS_RETURN_COMPLETED, Order::STATUS_CANCELED])) {
                $expectedCod += $order->cod;
            }

            if($order->finance_status == Order::FINANCE_STATUS_UNPAID) {
                if(in_array($order->status, [Order::STATUS_WAITING_INSPECTION, Order::STATUS_WAITING_CONFIRM, Order::STATUS_WAITING_PACKING])) {
                    $expectedOrderFee += $order->service_amount;
                }
                if($order->status !== Order::STATUS_CANCELED) {
                    $expectedOrderFee += $order->shipping_amount;

                    $orderCostPrice = $order->cost_price;
                    $totalCostPrice += $orderCostPrice;
                }
            }

            $date = $order->created_at->toDateString();
            if(isset($stats[$date])) {
                $stats[$date] = [
                    'cod' => $stats[$date]['cod'] + $order->cod,
                    'total_order' => $stats[$date]['total_order'] + 1,
                    'total_delivered' => $stats[$date]['total_delivered'] + $orderDelivered,
                    'total_delivering' => $stats[$date]['total_delivering'] + $orderDelivering,
                    'total_returned' => $stats[$date]['total_returned'] + $orderReturned,
                    'cost_price' => $stats[$date]['cost_price'] + $orderCostPrice,
                ];
            } else {
                $stats[$date] = [
                    'cod' => $order->cod,
                    'cost_price' => $orderCostPrice,
                    'total_order' => 1,
                    'total_delivered' => $orderDelivered,
                    'total_delivering' => $orderDelivering,
                    'total_returned' => $orderReturned,
                ];
            }
        }

        return [
            'cod' => $cod,
            'expected_cod' => $expectedCod,
            'expected_order_fee' => $expectedOrderFee,
            'expected_import_fee' => $expectedImportFee,
            'total_order' => $totalOrder,
            'total_delivered' => $totalDelivered,
            'total_returned' => $totalReturned,
            'cost_price' => $totalCostPrice,
            'total_delivering' => $totalDelivering,
            'stats' => $stats
        ];
    }
}

