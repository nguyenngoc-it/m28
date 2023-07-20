<?php

namespace Modules\Document\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\FreightBill\Models\FreightBill;

class DocumentQuery extends ModelQueryFactory
{
    /**
     * @var array
     */
    protected $joins = [
        'importing_barcodes' => ['documents.id', '=', 'importing_barcodes.document_id'],
        'document_orders' => ['documents.id', '=', 'document_orders.document_id'],
        'document_freight_bill_inventories' => ['documents.id', '=', 'document_freight_bill_inventories.document_id'],
        'orders' => ['orders.id', '=', 'document_freight_bill_inventories.order_id'],
        'freight_bills' => ['freight_bills.id', '=', 'document_freight_bill_inventories.freight_bill_id'],
    ];

    protected function newModel()
    {
        return new Document();
    }

    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('documents.warehouse_id', $warehouseId);
        } else {
            $query->where('documents.warehouse_id', $warehouseId);
        }
    }

    /**
     * Filter by verified time
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyVerifiedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'documents.verified_at', $input);
    }

    /**
     * Filter by created time
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'documents.created_at', $input);
    }

    /**
     * @param ModelQuery $query
     * @param array $type
     */
    protected function applyImportedTypeFilter(ModelQuery $query, array $type)
    {
        $query->whereIn('documents.type', $type);
    }

    /**
     * lọc theo id kiện nhập
     * @param ModelQuery $query
     * @param integer $id
     */
    protected function applyPackageIdFilter(ModelQuery $query, $id)
    {
        $query->getQuery()->whereHas('importingBarcodes', function ($subQuery) use ($id) {
            $subQuery->whereIn('type', [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL]);
            $subQuery->where('object_id', trim($id));
        });
    }

    /**
     * lọc theo mã kiện nhập
     * @param ModelQuery $query
     * @param string $code
     */
    protected function applyPackageCodeFilter(ModelQuery $query, $code)
    {
        $query->getQuery()->whereHas('importingBarcodes', function ($subQuery) use ($code) {
            $subQuery->where('type', ImportingBarcode::TYPE_PACKAGE_CODE);
            $subQuery->where('barcode', trim($code));
        });
    }

    /**
     * lọc theo vận đơn kiện nhập
     * @param ModelQuery $query
     * @param string $code
     */
    protected function applyPackageFreightBillCodeFilter(ModelQuery $query, $code)
    {
        $query->getQuery()->whereHas('importingBarcodes', function ($subQuery) use ($code) {
            $subQuery->where('type', ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL);
            $subQuery->where('barcode', trim($code));
        });
    }

    /**
     * lọc theo vận đơn của đơn
     * @param ModelQuery $query
     * @param string $code
     */
    protected function applyFreightBillCodeFilter(ModelQuery $query, $code)
    {
        $query->getQuery()->whereHas('importingBarcodes', function ($subQuery) use ($code) {
            $subQuery->where('type', ImportingBarcode::TYPE_FREIGHT_BILL);
            $subQuery->where('barcode', trim($code));
        });
    }

    /**
     * lọc theo id đơn xuất
     * @param ModelQuery $query
     * @param integer $id
     */
    protected function applyOrderIdFilter(ModelQuery $query, $id)
    {
        $query->join('document_orders')
            ->where('document_orders.order_id', $id);
    }

    /**
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyInventoryDocumentStatusFilter(ModelQuery $query, $status)
    {
        $query->where('documents.status', $status);
    }

    /** trạng thái đơn kiểm kê
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyInventoryOrderStatusFilter(ModelQuery $query, $status)
    {
        $query->join('document_freight_bill_inventories')
            ->join('orders')
            ->where('orders.status', $status);
    }

    /**
     * @param ModelQuery $query
     * @param $input
     */
    protected function applyInventoryFreightBillCreatedAtFilter(ModelQuery $query, $input)
    {
        $query->join('document_freight_bill_inventories')
            ->join('freight_bills');

        $this->applyFilterTimeRange($query, 'freight_bills.created_at', $input);
    }

    /** lọc theo ngày nhận bộ chứng từ đối soát
     * @param ModelQuery $query
     * @param $receivedDate
     * @return void
     */
    protected function applyReceivedDateFilter(ModelQuery $query, $receivedDate)
    {
        $query->whereDate('received_date', '>=', $receivedDate['from'])
            ->whereDate('received_date', '<=', $receivedDate['to']);
    }


    /** lọc theo mã chứng từ
     * @param ModelQuery $query
     * @param $freightBillCode
     * @return void
     */
    public function applyFreightBillFilter(ModelQuery $query, $freightBillCode)
    {
        $query->join('document_freight_bill_inventories');
        if(is_array($freightBillCode)) {
            $query->whereIn('document_freight_bill_inventories.freight_bill_code', $freightBillCode);
        } else {
            $freightBillCode = trim($freightBillCode);
            $freightBillCodeArray = explode(" ", $freightBillCode);
            if(count($freightBillCodeArray) > 1) {
                $query->whereIn('document_freight_bill_inventories.freight_bill_code', $freightBillCodeArray);
            } else {
                $query->where('document_freight_bill_inventories.freight_bill_code', $freightBillCode);
            }
        }
    }

    /** lọc theo shipping partner
     * @param ModelQuery $query
     * @param $shippingPartnerId
     * @return void
     */
    protected function applyShippingPartnerIdFilter(ModelQuery $query, $shippingPartnerId)
    {
        if(is_array($shippingPartnerId)) {
            $query->whereIn('documents.shipping_partner_id', $shippingPartnerId);
        } else {
            $query->where('documents.shipping_partner_id', $shippingPartnerId);
        }

    }

    /** lọc theo người tạo chứng từ
     * @param ModelQuery $query
     * @param $creatorId
     * @return void
     */
    protected function applyCreatorIdFilter(ModelQuery $query, $creatorId)
    {
        $query->where('documents.creator_id', $creatorId);
    }

    /** lọc theo mã chứng từ
     * @param ModelQuery $query
     * @param $code
     * @return void
     */
    protected function applyCodeFilter(ModelQuery $query, $code)
    {
        $query->where('documents.code', $code);
    }

    /** lọc theo mã vận đơn
     * @param ModelQuery $query
     * @param $code
     * @return void
     */
    protected function applyTrackingCodeFilter(ModelQuery $query, $code)
    {
        
        $codes = explode(' ', $code);
        // Sửa join document_orders sau khi a Thanh fixx
        $query->leftJoin('document_delivery_comparisons', 'documents.id', '=', 'document_delivery_comparisons.document_id')
              ->leftJoin('orders', 'document_delivery_comparisons.order_id', '=', 'orders.id')
              ->leftJoin('freight_bills', 'orders.id', '=', 'freight_bills.order_id')
              ->whereIn('freight_bills.freight_bill_code', $codes);
    }
}
