<?php

namespace Modules\Order\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPackingService;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Modules\Service\Models\Service as ServiceModel;

use Generator;
use Gobiz\Database\DBHelper;

class ExportOrderServices
{
    /** @var User $user */
    protected $user;
    protected $filter;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    public $type;

    /**
     * ExportStocks constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user, $type)
    {
        $this->user   = $user;
        $this->filter = $this->makeFilter($filter);
        $this->type   = $type;
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilter($filter)
    {
        Arr::pull($filter, 'page', config('paginate.page'));
        Arr::pull($filter, 'per_page', config('paginate.per_page'));

        return $filter;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->makeGenerator($this->type)))->export('/tmp/order-services-export-' . $this->user->id . '.xlsx');
    }


    /**
     * @return Generator
     */
    public function makeGenerator($type)
    {
        /**
         * @var Order $order
         */
        $results = DBHelper::chunkByIdGenerator($this->makeQuery(), 200);
        //chỉ import dịch vụ đóng gói
        $serviceExportIds = $this->user->tenant->services()->where('type', ServiceModel::SERVICE_TYPE_EXPORT)->pluck('id')->toArray();
        // export dịch vụ hoàn
        $serviceReturnIds = $this->user->tenant->services()->where('type', ServiceModel::SERVICE_TYPE_IMPORTING_RETURN_GOODS)->pluck('id')->toArray();
        $checkCount       = 0;
        foreach ($results as $orders) {
            foreach ($orders as $order) {
                if ($type['type_export'] == "export_refund_cost") {
                    $orderPackingServices = $order->orderImportReturnGoodsServices->whereIn('service_id', $serviceReturnIds);
                } else {
                    $orderPackingServices = $order->orderPackingServices->whereIn('service_id', $serviceExportIds);
                }
                $freightBill = $order->freightBills->first();
                foreach ($orderPackingServices as $orderPackingService) {
                    $checkCount++;
                    yield $this->makeRow($order, $freightBill, $orderPackingService, $type);
                }
            }
        }
        if ($checkCount == 0) {
            yield $this->setDataExportCost($type);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function makeQuery()
    {
        return Service::order()->query($this->filter)
            ->with([
                'orderPackingServices',
                'orderPackingServices.servicePrice',
                'orderPackingServices.servicePrice.service',
                'freightBills',
            ])
            ->getQuery();
    }

    /**
     * @param Order $order
     * @param $freightBill
     * @param $orderPackingService
     * @return array
     */
    protected function makeRow(Order $order, $freightBill, $orderPackingService, $type)
    {
        $servicePrice = $orderPackingService->servicePrice;
        if ($type['type_export'] == "export_refund_cost") {
            $quantity = $order->orderStocks->sum('quantity');
            $amount   = $servicePrice->price * $quantity;
        } else {
            $quantity = $orderPackingService->quantity;
            $amount   = $orderPackingService->amount;
        }
        $service     = $orderPackingService->service;
        $serviceName = ($service instanceof ServiceModel) ? $service->name : '';

        $exportRefundCost = [trans('yield_price') => $servicePrice->yield_price];
        $dataReturn       = [
            trans('seller_name') => ($order->merchant) ? $order->merchant->name : '',
            trans('order_code') => $order->code,
            trans('freight_bill_code') => ($freightBill instanceof FreightBill) ? $freightBill->freight_bill_code : "",
            trans('service') => $serviceName . ' - (' . $servicePrice->label . ')',
            trans('price') => $servicePrice->price,
        ];
        $data             = [
            trans('quantity') => $quantity,
            trans('amount') => $amount,
            trans('order_status') => trans('order.status.' . $order->status),
            trans('finance_status') => trans('order.finance_status.' . $order->finance_status),
            trans('packed_at') => ($order->packed_at) ? $order->packed_at->toDateTimeString() : ''
        ];
        if ($type['type_export'] == "export_refund_cost") {
            $dataReturn = array_merge($dataReturn, $data);
        } else {
            $dataReturn = array_merge($dataReturn, $exportRefundCost, $data);
        }
        return $dataReturn;
    }

    /**
     * @param $checkCount
     * @return Generator
     */
    public function setDataExportCost($type)
    {
        $exportRefundCost = [trans('yield_price') => ''];
        $dataReturn = [
            trans('seller_name') => '',
            trans('order_code') => '',
            trans('freight_bill_code') => '',
            trans('service') => '',
            trans('price') => ''
            ];
        $data = [
            trans('quantity') => '',
            trans('amount') => '',
            trans('order_status') => '',
            trans('finance_status') => '',
            trans('packed_at') => ''
        ];
        if ($type['type_export'] == "export_refund_cost") {
            $dataReturn = array_merge($dataReturn, $data);
        } else {
            $dataReturn = array_merge($dataReturn, $exportRefundCost, $data);
        }
        return $dataReturn;
    }

}
