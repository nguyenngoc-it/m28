<?php

namespace Modules\Order\Commands;
use Modules\Order\Models\Order;
use Modules\Order\Services\StatusOrder;
use Modules\Service;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;

/**
 * Class OrderStats
 * @package Modules\Order\Commands
 */
class OrderStats
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
        $stats = Service::order()->query($this->filter)->getQuery()->select([
            'orders.status',
            DB::raw("SUM(orders.cod) as total_cod"),
            DB::raw("SUM(orders.paid_amount)  as total_paid_amount"),
            DB::raw("SUM(orders.service_amount) as total_service_amount"),
            DB::raw("SUM(orders.shipping_amount) as total_shipping_amount"),
            DB::raw("SUM(orders.expected_shipping_amount) as total_expected_shipping_amount"),
            DB::raw("SUM(orders.amount_paid_to_seller) as total_amount_paid_to_seller"),
            DB::raw("SUM(orders.service_import_return_goods_amount) as total_service_import_return_goods_amount"),
            DB::raw("SUM(orders.extent_service_amount) as total_extent_service_amount"),
            DB::raw("SUM(orders.extent_service_expected_amount) as total_extent_service_expected_amount"),
        ])->groupBy('orders.status')->get()->toArray();

        $totalServiceAmount   = 0;
        $totalShippingAmount  = 0;
        $totalExpectedShippingAmount  = 0;
        $totalReturnAmount    = 0;
        $totalPaidAmount      = 0;
        $totalRemainingAmount = 0;
        $saleExpectedAmount   = 0;
        $saleAmount           = 0;
        $totalServiceImportReturnGoods = 0;
        $extentServiceAmount = 0;
        $extentServiceExpectedAmount = 0;
        $grossProfit         = 0;

        foreach ($stats as $stat) {

            $totalPaidAmount      += $stat['total_paid_amount'];
            $totalServiceAmount   += $stat['total_service_amount'];
            $totalShippingAmount  += $stat['total_shipping_amount'];
            $totalRemainingAmount += $stat['total_amount_paid_to_seller'];
            $totalServiceImportReturnGoods += $stat['total_service_import_return_goods_amount'];
            $extentServiceAmount  += $stat['total_extent_service_amount'];

            if(!in_array($stat['status'], [Order::STATUS_CANCELED])) {
                $totalExpectedShippingAmount += $stat['total_expected_shipping_amount'];
                $extentServiceExpectedAmount += $stat['total_extent_service_expected_amount'];
            }

            if(in_array($stat['status'], [Order::STATUS_RETURN, Order::STATUS_RETURN_COMPLETED])) {
                $totalReturnAmount += $stat['total_cod'];
            }

            if(in_array($stat['status'], [
                Order::STATUS_DELIVERING, Order::STATUS_PART_DELIVERED,
                Order::STATUS_DELIVERED, Order::STATUS_FINISH,
            ])) {
                $saleAmount += $stat['total_cod'];
            }

            if(in_array($stat['status'], StatusOrder::getBeforeStatus(Order::STATUS_DELIVERING))) {
                $saleExpectedAmount += $stat['total_cod'];
            }
        }

        $grossProfit = $totalPaidAmount - $totalShippingAmount - $extentServiceAmount;
        return [
            'sale_amount' => $saleAmount,// doanh số bán hàng thực tế,
            'sale_expected_amount' => $saleExpectedAmount,// doanh số bán hàng dự kiến
            'extent_service_amount' => $extentServiceAmount, // chi phí vận hành thực tế
            'extent_service_expected_amount' => $extentServiceExpectedAmount, // chi phí vận hành dự kiến
            'paid_amount' => $totalPaidAmount, //số tiền thu được - Doanh thu (khách đã thanh toán, được cộng dồn khi xác nhận chứng từ đối soát)
            'service_amount' => $totalServiceAmount, //số tiền dịch vụ đóng hàng
            'shipping_amount' => $totalShippingAmount, // chi phí VC thực tế (được cộng dồn khi xác nhận chứng từ đối soát)
            'expected_shipping_amount' => $totalExpectedShippingAmount, //chi phí VC dự kiến
            'remaining_amount' => $totalRemainingAmount, //số tiền còn lại thanh toán cho seller
            'return_amount' => $totalReturnAmount, // số tiền hoàn
            'service_import_return_goods_amount' => $totalServiceImportReturnGoods, //tiền dịch vụ hoàn hàng,
            'gross_profit' => $grossProfit, // lợi nhuận gộp  (lấy doanh thu - chi phí VC - chi phí vận hành)
        ];
    }
}

