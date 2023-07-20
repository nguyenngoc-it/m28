<?php

namespace Modules\Order\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Document\Models\Document;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;

class OrderQuery extends ModelQueryFactory
{
    protected $joins = [
        'order_stocks' => ['orders.id', '=', 'order_stocks.order_id'],
        'order_transactions' => ['orders.id', '=', 'order_transactions.order_id'],
        'order_skus' => ['orders.id', '=', 'order_skus.order_id'],
        'freight_bills' => ['orders.id', '=', 'freight_bills.order_id'],
        'document_freight_bill_inventories' => ['orders.id', '=', 'document_freight_bill_inventories.order_id'],
        'documents' => ['documents.id', '=', 'document_freight_bill_inventories.document_id'],
        'document_orders' => ['orders.id', '=', 'document_orders.order_id']
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Order();
    }

    /**
     * Filter theo    thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'orders.created_at', $input);
    }

    /**
     * Filter theo thoi gian dong goi don hang
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyPackedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'orders.packed_at', $input);
    }

    /**
     * Filter theo    thoi gian du kien giao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyIntendedDeliveryAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'orders.intended_delivery_at', $input);
    }

    /**
     * Filter theo ten nguoi nhan
     *
     * @param ModelQuery $query
     * @param string $receiver_name
     */
    protected function applyReceiverNameFilter(ModelQuery $query, $receiver_name)
    {
        if ($receiver_name) {
            $query->where('orders.receiver_name', 'like', '%' . trim($receiver_name) . '%');
        }
    }

    /** Filter theo ten shop
     * @param ModelQuery $query
     * @param $nameStore
     * @return void
     */
    protected function applyNameStoreFilter(ModelQuery $query, $nameStore)
    {
        if ($nameStore) {
            $query->where('orders.name_store', $nameStore);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $warehouse_id
     */
    protected function applyWarehouseIdFilter(ModelQuery $query, $warehouse_id)
    {
        $query->join('order_stocks')
            ->where('order_stocks.warehouse_id', $warehouse_id);
        $query->groupBy('orders.id');
    }

    /**
     * @param ModelQuery $query
     * @param $warehouse_id
     */
    protected function applyWarehouseAreaIdFilter(ModelQuery $query, $warehouse_id)
    {
        $query->join('order_stocks')
            ->where('order_stocks.warehouse_area_id', $warehouse_id);
        $query->groupBy('orders.id');
    }

    /**
     * @param ModelQuery $query
     * @param boolean $noWarehouseArea
     */
    protected function applyNoWarehouseAreaFilter(ModelQuery $query, $noWarehouseArea)
    {
        if ($noWarehouseArea) {
            $query->leftJoin('order_stocks', 'orders.id', '=', 'order_stocks.order_id');
            $query->whereNull('order_stocks.id');
            $query->groupBy('orders.id');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $skuId
     */
    protected function applySkuIdFilter(ModelQuery $query, $skuId)
    {
        $query->join('order_skus');
        if (is_array($skuId)) {
            $query->whereIn('order_skus.sku_id', $skuId);
        } else {
            $query->where('order_skus.sku_id', $skuId);
        }
        $query->groupBy('orders.id');
    }


    /**
     * @param ModelQuery $query
     * @param $method
     */
    protected function applyPaymentMethodFilter(ModelQuery $query, $method)
    {
        $query->join('order_transactions')
            ->where('order_transactions.method', trim($method));
        $query->groupBy('orders.id');
    }

    /**
     * Filter theo sdt nguoi nhan
     *
     * @param ModelQuery $query
     * @param string $receiver_phone
     */
    protected function applyReceiverPhoneFilter(ModelQuery $query, $receiver_phone)
    {
        if ($receiver_phone) {
            $query->where('orders.receiver_phone', 'like', '%' . trim($receiver_phone) . '%');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    public function applyCodeFilter(ModelQuery $query, $code)
    {
        $codes = explode(" ", $code);
        $codes = array_map(function ($value) {
            return trim($value);
        }, $codes);
        $codes = array_unique($codes);

        $query->getQuery()->where(function($query) use($codes) {
            return $query->whereIn('orders.code', $codes)
                         ->orWhereIn('orders.ref_code', $codes);
        });
    }

    /**
     * @param ModelQuery $query
     * @param $merchantId
     */
    public function applyMerchantIdFilter(ModelQuery $query, $merchantId)
    {
        if (is_array($merchantId)) {
            $query->getQuery()
                ->whereIn('orders.merchant_id', $merchantId);
        } else {
            $query->where('orders.merchant_id', $merchantId);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $merchantIds
     */
    public function applyMerchantIdsFilter(ModelQuery $query, array $merchantIds)
    {
        $query->whereIn('merchant_id', $merchantIds);
    }

    /**
     * @param ModelQuery $query
     * @param $locationId
     */
    public function applyLocationIdFilter(ModelQuery $query, $locationId)
    {
        $merchantIds = Merchant::query()->where('location_id', $locationId)->pluck('id')->all();
        $query->whereIn('orders.merchant_id', $merchantIds);
    }

    /**
     * @param ModelQuery $query
     * @param $freightBillCode
     */
    public function applyFreightBillFilter(ModelQuery $query, $freightBillCode)
    {
        $query->join('freight_bills');
        if (is_array($freightBillCode)) {
            $query->whereIn('freight_bills.freight_bill_code', $freightBillCode);
        } else {
            $freightBillCode      = trim($freightBillCode);
            $freightBillCodeArray = explode(" ", $freightBillCode);
            if (count($freightBillCodeArray) > 1) {
                $query->whereIn('freight_bills.freight_bill_code', $freightBillCodeArray);
            } else {
                $query->where('freight_bills.freight_bill_code', $freightBillCode);
            }
        }

        $query->whereNotIn('freight_bills.status', [FreightBill::STATUS_CANCELLED]);
    }

    /**
     * @param ModelQuery $query
     * @param array $listStatus
     */
    public function applyListStatusFilter(ModelQuery $query, array $listStatus)
    {
        $query->whereIn('orders.status', $listStatus);
    }


    /** trạng thái đơn kiểm kê
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyInventoryOrderStatusFilter(ModelQuery $query, $status)
    {
        $query->where('orders.status', $status);
    }


    /**
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyInventoryDocumentStatusFilter(ModelQuery $query, $status)
    {
        if (strtoupper($status) == 'NONE') { //nếu tìm kiếm đơn chưa có chứng từ
            $query->where('orders.has_document_inventory', false);
        } else {
            $query->join('document_freight_bill_inventories')
                ->join('documents')
                ->where('documents.status', $status);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $input
     */
    protected function applyInventoryFreightBillCreatedAtFilter(ModelQuery $query, $input)
    {
        $query->join('freight_bills');

        $this->applyFilterTimeRange($query, 'freight_bills.created_at', $input);
    }


    /**
     * Lọc đơn chưa đối soát thông tin giao nhận của từng đơn vị vận chuyển
     *
     * @param ModelQuery $query
     * @param $input
     */
    protected function applyNoForControlFilter(ModelQuery $query, $input)
    {
        $listStatus = [
            Order::STATUS_DELIVERING,
            Order::STATUS_DELIVERED,
            Order::STATUS_RETURN,
            Order::STATUS_RETURN_COMPLETED,
            Order::STATUS_FAILED_DELIVERY,
        ];
        $this->applyListStatusFilter($query, $listStatus);
        $query->leftJoin('document_delivery_comparisons', 'orders.id', '=', 'document_delivery_comparisons.order_id');
        if ($input) {
            $query->whereNull('document_delivery_comparisons.order_id');
        } else {
            $query->whereNotNull('document_delivery_comparisons.order_id');
        }
    }

    /**
     * Filter theo thoi gian xuat kho
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyExportingWarehouseAtFilter(ModelQuery $query, array $input)
    {
        $query->join('document_orders');
        $query->leftJoin('documents', 'documents.id', '=', 'document_orders.document_id')
            ->where('documents.type', Document::TYPE_EXPORTING);
        $this->applyFilterTimeRange($query, 'documents.created_at', $input);
    }

    /** lọc theo shipping partner
     * @param ModelQuery $query
     * @param $shippingPartnerId
     * @return void
     */
    protected function applyShippingPartnerIdFilter(ModelQuery $query, $shippingPartnerId)
    {
        if (is_array($shippingPartnerId)) {
            $query->whereIn('orders.shipping_partner_id', $shippingPartnerId);
        } else {
            $query->where('orders.shipping_partner_id', $shippingPartnerId);
        }

    }

    /**
     * @param ModelQuery $query
     * @param array $shippingFinancialStatus
     * @return void
     */
    protected function applyShippingFinancialStatusFilter(ModelQuery $query, array $shippingFinancialStatus)
    {
        $query->whereIn('orders.shipping_financial_status', $shippingFinancialStatus);
    }
}
