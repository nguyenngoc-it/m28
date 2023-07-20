<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationShippingPartner;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Công nợ với ĐVVC
 * Class DebtShippingPartnerController
 * @package Modules\Document\Controllers
 */
class DebtShippingPartnerController extends Controller
{

    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'shipping_partner_id',
            'inventory_order_status',
            'inventory_document_status',
            'shipping_financial_status',
            'inventory_freight_bill_created_at',
            'page',
            'per_page',
            'freight_bill'
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;

        if (empty($filter['inventory_freight_bill_created_at'])) {
            $filter['inventory_freight_bill_created_at'] = [
                'from' => (new Carbon())->subMonths(3)->toDateString(),
                'to' => (new Carbon())->toDateString(),
            ];
        }
        $filter = $this->makeFilterByShippingPartner($filter);

        return $filter;
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilterByShippingPartner($filter)
    {
        $userLocationIds    = $this->user->locations->pluck('id')->toArray();
        $shippingPartnerIds = LocationShippingPartner::query()->whereIn('location_id', $userLocationIds)->pluck('shipping_partner_id')->toArray();
        if(!empty($filter['shipping_partner_id']) && in_array($filter['shipping_partner_id'], $shippingPartnerIds))
        {
            return $filter;
        }

        $filter['shipping_partner_id'] = $shippingPartnerIds;
        return $filter;
    }


    /**
     * @param $shippingPartnerId
     * @return ShippingPartner|null
     */
    protected function getShippingPartner($shippingPartnerId)
    {
        return ShippingPartner::find($shippingPartnerId);
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return \Modules\Currency\Models\Currency|null
     */
    protected function getCurrency(ShippingPartner $shippingPartner)
    {
        $country  = $shippingPartner->locations->first();
        return ($country instanceof Location) ? $country->currency : null;
    }


    /**
     * @param $filter
     * @param $shippingPartner
     * @return array|bool
     */
    protected function customValidate($filter, &$shippingPartner = null)
    {
        if(empty($filter['shipping_partner_id']) || (!$shippingPartner = $this->getShippingPartner($filter['shipping_partner_id']))) {
            return ['shipping_partner_id' => 'invalid'];
        }

        $time = (array)$filter['inventory_freight_bill_created_at'];
        $from = Arr::get($time, 'from', '');
        $to   = Arr::get($time, 'to', '');

        if(!$this->checkFormatDate($from) || !$this->checkFormatDate($to)) {
            return ['time' => 'invalid'];
        }
        $from = new Carbon($from);
        $to   = new Carbon($to);
        if($from > $to) {
            return ['time' => 'invalid'];
        }

        if(
            $from->addMonth(3) < $to
        ) {
            // Không được quá 3 tháng
            return ['time' => 'invalid'];
        }

        return true;
    }

    /**
     * @param $date
     * @return bool
     */
    protected function checkFormatDate($date)
    {
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter          = $this->getQueryFilter();
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner = null;
        $validate        = $this->customValidate($filter, $shippingPartner);
        if($validate !== true) {
            return $this->response()->error('INPUT_INVALID', $validate);
        }

        $perPage = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $page    = Arr::pull($filter, 'page', config('paginate.page'));

        $results = Service::order()->query($filter)->getQuery()->select([
            'orders.id', 'orders.code', 'orders.paid_amount', 'orders.cod_fee_amount', 'orders.shipping_amount',
            'orders.other_fee','orders.status','orders.freight_bill', 'shipping_financial_status',
            DB::raw("freight_bills.created_at as freight_bill_created_at")
        ])
            ->where('orders.shipping_partner_id', $shippingPartner->id)
            ->with(['documentFreightBillInventories' => function ($q) {
                $q->with(['document']);
            }])
            ->orderBy('freight_bills.created_at', 'DESC')
            ->paginate($perPage, ['orders.*'], 'page', $page);

        $inventoryDocumentStatus = Arr::get($filter, 'inventory_document_status');
        $currency = $this->getCurrency($shippingPartner);
        return $this->response()->success([
            'orders' => array_map(function(Order $order) use ($inventoryDocumentStatus) {
                $document = null;
                $documentFreightBillInventories = $order->documentFreightBillInventories;
                foreach ($order->documentFreightBillInventories as $documentFreightBillInventory) {
                    if($inventoryDocumentStatus == Document::STATUS_CANCELLED) {
                        if($documentFreightBillInventory->document->status == Document::STATUS_CANCELLED) {
                            $document = $documentFreightBillInventory->document;
                        }
                    } else {
                        if($documentFreightBillInventory->document->status != Document::STATUS_CANCELLED) {
                            $document = $documentFreightBillInventory->document;

                            if($documentFreightBillInventory->document->status == Document::STATUS_DRAFT) {
                                $order->paid_amount += $documentFreightBillInventory->cod_paid_amount;
                                $order->cod_fee_amount += $documentFreightBillInventory->cod_fee_amount;
                                $order->shipping_amount += $documentFreightBillInventory->shipping_amount;
                                $order->other_fee += $documentFreightBillInventory->other_fee;
                            }
                        }
                    }
                }

                if(empty($document) && $documentFreightBillInventories->count() > 0) {
                    $documentFreightBillInventory = $documentFreightBillInventories->first();
                    $document = ($documentFreightBillInventory) ? $documentFreightBillInventory->document : null;
                }

                return compact('order', 'document');
            }, $results->items()),
            'pagination' => $results,
            'currency' => $currency,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function stats()
    {
        $filter          = $this->getQueryFilter();
        Arr::pull($filter, 'shipping_financial_status');
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner = null;
        $validate        = $this->customValidate($filter, $shippingPartner);
        if($validate !== true) {
            return $this->response()->error('INPUT_INVALID', $validate);
        }
        foreach (['per_page', 'page'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $stats = Service::document()->query($filter)->getQuery()->select([
            'documents.status',
            DB::raw("SUM(
                IFNULL(document_freight_bill_inventories.cod_paid_amount, 0)
               -IFNULL(document_freight_bill_inventories.cod_fee_amount, 0)
               -IFNULL(document_freight_bill_inventories.shipping_amount, 0)
             ) as total_amount")
           ])
            ->where('documents.shipping_partner_id', $shippingPartner->id)
            ->where('documents.status', '!=', Document::STATUS_CANCELLED)
            ->groupBy('documents.status')->get()->toArray();
        $debtsPaid  = 0;
        $unpaidDebt = 0;
        foreach ($stats as $stat) {
            if($stat['status'] == Document::STATUS_DRAFT) {
                $unpaidDebt += $stat['total_amount'];
            } else if($stat['status'] == Document::STATUS_COMPLETED) {
                $debtsPaid += $stat['total_amount'];
            }
        }

        $expected = 0;
        $inventoryDocumentStatus = Arr::pull($filter, 'inventory_document_status');
        if(
            empty($inventoryDocumentStatus) ||
            in_array(strtoupper($inventoryDocumentStatus), ['NONE', 'CANCELLED'])
        ) {
            $orders = Service::order()->query($filter)->getQuery()
                ->select(['orders.id','orders.cod', 'orders.expected_shipping_amount'])
                ->where('orders.shipping_partner_id', $shippingPartner->id)
                ->where('orders.has_document_inventory', false)
                ->groupBy('orders.id')->get();
            foreach ($orders as $order) {
                $expected += ($order['cod'] - $order['expected_shipping_amount']);
            }
        }

        $currency = $this->getCurrency($shippingPartner);
        return $this->response()->success([
            'debts_paid' => $debtsPaid,
            'unpaid_debt' => $unpaidDebt,
            'expected' => $expected,
            'currency' => $currency,
        ]);
    }

    public function exportExcel()
    {
        $filter          = $this->getQueryFilter();
        Arr::pull($filter, 'shipping_financial_status');
        $inventoryDocumentStatus = Arr::pull($filter, 'inventory_document_status');

        if (strtoupper($inventoryDocumentStatus) == 'NONE') {
            $shippingPartner = null;
            $validate        = $this->customValidate($filter, $shippingPartner);
            if($validate !== true) {
                return $this->response()->error('INPUT_INVALID', $validate);
            }

            $fieldSelect = [
                'orders.code as Mã đơn',
                'orders.paid_amount as COD thu được',
                'orders.cod_fee_amount as Phí COD',
                'orders.shipping_amount as Phí vận chuyển',
                'orders.other_fee as Chi Phí Khác',
                Db::raw("
                CASE orders.status
                    WHEN 'WAITING_INSPECTION' THEN 'Chờ chọn kho'
                    WHEN 'WAITING_CONFIRM' THEN 'Chờ xác nhận'
                    WHEN 'WAITING_PROCESSING' THEN 'Chờ xử lý'
                    WHEN 'WAITING_PICKING' THEN 'Chờ nhặt hàng'
                    WHEN 'WAITING_PACKING' THEN 'Chờ đóng gói'
                    WHEN 'WAITING_DELIVERY' THEN 'Chờ giao'
                    WHEN 'DELIVERING' THEN 'Đang giao hàng'
                    WHEN 'PART_DELIVERED' THEN 'Đã giao 1 phần hàng'
                    WHEN 'FINISH' THEN 'Hoàn thành'
                    WHEN 'CANCELED' THEN 'Đã huỷ'
                    WHEN 'RETURN' THEN 'Đang trả hàng'
                    WHEN 'RETURN_COMPLETED' THEN 'Đã trả hàng'
                    WHEN 'FAILED_DELIVERY' THEN 'Giao hàng lỗi'
                END AS 'Trạng thái đơn'")
                ,
                'freight_bills.created_at as Ngày tạo vận đơn'
            ];

            $orders = Service::order()->query($filter)->getQuery()
                    ->select($fieldSelect)
                    ->where('orders.shipping_partner_id', $shippingPartner->id)
                    ->where('orders.has_document_inventory', false)
                    ->with(['documentFreightBillInventories' => function ($q) {
                        $q->with(['document']);
                    }])
                    ->orderBy('freight_bills.created_at', 'DESC')
                    ->get();
            $fileName = "debt-shipping-partners-" . strtolower($shippingPartner->code) . ".xlsx";
            return (new FastExcel($orders))->download($fileName);
        }
    }
}
