<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Document\Jobs\CalculateBalanceMerchantWhenConfirmDocumentJob;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Document\Models\ImportingBarcode;
use Modules\Order\Models\Order;
use Gobiz\Log\LogService;

class ReCalculateBalanceMerchant extends Command
{
    protected $signature = 're-calculate-balance-merchant {--type=PACKING}';

    protected $description = 'Chạy lại nhưng chứng từ chưa được tính phí';

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger = null;

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function logger()
    {
        if($this->logger === null) {
            $this->logger = LogService::logger('re_calculate_balance_merchant');
        }
        return $this->logger;
    }

    public function handle()
    {
        $startDate = Carbon::now()->subDays(config('gobiz.count_date_run_calculate'))->toDateTimeString();
        $endDate   = Carbon::now()->toDateTimeString();

        $type = $this->option('type');
        $type = strtoupper($type);
        if(
            !in_array($type, [Document::TYPE_PACKING, Document::TYPE_IMPORTING,
            Document::TYPE_FREIGHT_BILL_INVENTORY, Document::TYPE_IMPORTING_RETURN_GOODS])
        ) {
            $this->info('type invalid '.$startDate . ' - '.$endDate);
            return;
        }

        $this->logger()->info('start '.$type .': '.$startDate. ' - '.$endDate);

        /** @var Builder $query */
        $query = '';
        switch ($type) {
            case Document::TYPE_PACKING:
                $query = $this->makeQueryDocumentPacking();
                break;
            case Document::TYPE_IMPORTING:
                $query = $this->makeQueryDocumentImporting();
                break;
            case Document::TYPE_FREIGHT_BILL_INVENTORY:
                $query = $this->makeQueryDocumentFreightBillInventory();
                break;
            case Document::TYPE_IMPORTING_RETURN_GOODS:
                $query = $this->makeQueryDocumentImportingReturnGoods();
                break;
        }

        $documentIds = $query->where('documents.verified_at', '>=', $startDate)
        ->where('documents.verified_at', '<', $endDate)
        ->where('documents.type', $type)
        ->where('documents.status', Document::STATUS_COMPLETED)
        ->pluck('documents.id')->toArray();

        if (!empty($documentIds)) {
            $documentIds = array_unique($documentIds);
            foreach ($documentIds as $documentId) {
                $this->logger()->info('start '.$type .': '.$documentId . ' - '.$startDate . ' - '.$endDate);

                dispatch(new CalculateBalanceMerchantWhenConfirmDocumentJob($documentId));
            }
        }
    }

    /**
     * Chạy lại các đơn của chứng đóng hàng (dịch vụ xuất) nếu đơn chưa thanh toán
     * @return Builder
     */
    protected function makeQueryDocumentPacking()
    {
        return Document::query()->select('documents.id')
            ->join('document_orders', 'documents.id', 'document_orders.document_id')
            ->join('orders', 'orders.id', 'document_orders.order_id')
            ->where('orders.finance_service_status', Order::FINANCE_STATUS_UNPAID);
    }

    /**
     * Chạy lại các chứng nhập hàng từ các kiện chưa được thanh toán
     * @return Builder
     */
    protected function makeQueryDocumentImporting()
    {
        return Document::query()->select('documents.id')
            ->join('importing_barcodes', 'documents.id', 'importing_barcodes.document_id')
            ->join('purchasing_packages', 'purchasing_packages.id', 'importing_barcodes.object_id')
            ->whereIn('importing_barcodes.type', [ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL, ImportingBarcode::TYPE_PACKAGE_CODE])
            ->where('purchasing_packages.finance_status', Order::FINANCE_STATUS_UNPAID);
    }

    /**
     * Chạy lại các chứng từ kiểm kê đã hoàn thành mà các kiểm kê trong chứng từ chưa thanh toán
     * @return Builder
     */
    protected function makeQueryDocumentFreightBillInventory()
    {
        return Document::query()->select('documents.id')
            ->join('document_freight_bill_inventories', 'documents.id', 'document_freight_bill_inventories.document_id')
            ->where(function($query) {
                $query->orWhere('document_freight_bill_inventories.finance_status_cod', DocumentFreightBillInventory::FINANCE_STATUS_UNPAID);
                $query->orWhere('document_freight_bill_inventories.finance_status_extent', DocumentFreightBillInventory::FINANCE_STATUS_UNPAID);
                $query->orWhere('document_freight_bill_inventories.finance_status_fee', DocumentFreightBillInventory::FINANCE_STATUS_UNPAID);
            });
    }

    /**
     * Chạy lại các chứng nhập hàng hoàn mà chưa bị trừ tiền
     * @return Builder
     */
    protected function makeQueryDocumentImportingReturnGoods()
    {
        return Document::query()->select('documents.id')
            ->join('document_orders', 'documents.id', 'document_orders.document_id')
            ->join('orders', 'orders.id', 'document_orders.order_id')
            ->where('orders.finance_service_import_return_goods_status', Order::FINANCE_STATUS_UNPAID);
    }
}
