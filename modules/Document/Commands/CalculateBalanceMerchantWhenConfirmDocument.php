<?php

namespace Modules\Document\Commands;

use Illuminate\Support\Arr;
use Modules\Document\Jobs\ProcessCodTransactionJob;
use Modules\Document\Jobs\ProcessCostOfGoodsTransactionJob;
use Modules\Document\Jobs\ProcessExportServiceTransactionJob;
use Modules\Document\Jobs\ProcessExtentServiceTransactionJob;
use Modules\Document\Jobs\ProcessImportReturnGoodsServiceTransactionJob;
use Modules\Document\Jobs\ProcessImportServiceTransactionJob;
use Modules\Document\Jobs\ProcessShippingFeeTransactionJob;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Document\Models\ImportingBarcode;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;
use Gobiz\Log\LogService;
use Psr\Log\LoggerInterface;

class CalculateBalanceMerchantWhenConfirmDocument
{
    /** @var Document */
    protected $document;

    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * BalanceSku constructor.
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        if ($this->logger === null) {
            $this->logger = LogService::logger('calculate_balance_merchant_confirm_document');
        }
        return $this->logger;
    }

    public function handle()
    {
        $this->logger()->info('Start ', $this->document->only(['id', 'code', 'type']));

        switch ($this->document->type) {
            case Document::TYPE_PACKING:
                $this->documentPacking($this->document);
                break;
            case Document::TYPE_IMPORTING:
                $this->documentImporting($this->document);
                break;
            case Document::TYPE_FREIGHT_BILL_INVENTORY:
                $this->documentFreightBillInventory($this->document);
                break;
            case Document::TYPE_IMPORTING_RETURN_GOODS:
                $this->documentImportingReturnGoods($this->document);
                break;
            case Document::TYPE_EXPORTING:
                $this->documentExporting($this->document);
                break;
        }
    }

    /**
     * Xử lý N+1
     * @param $merchantIds
     * @return array
     */
    protected function getMerchants($merchantIds)
    {
        if (empty($merchantIds)) return [];
        $merchantIds = array_unique($merchantIds);
        $merchants   = Merchant::query()->whereIn('id', $merchantIds)->get();
        $data        = [];
        /** @var Merchant $merchant */
        foreach ($merchants as $merchant) {
            $data[$merchant->id] = $merchant;
        }

        return $data;
    }

    /**
     * Khi xác nhận đóng gói thành công, trừ chi phí đóng gói của đơn hàng vào ví (chi phí đóng gói = tổng chi phí dịch vụ xuất hàng của từng đơn)
     * @param Document $document
     */
    protected function documentPacking(Document $document)
    {
        $orders           = $document->orders;
        $orderIds         = [];
        $orderDropshipIds = [];
        /** @var Order $order */
        foreach ($orders as $order) {
            $amount = $order->service_amount; //phí đóng gói

            if ($order->dropship) { //nếu đơn dropship thì tính thêm cả phí hàng hóa và phí vận chuyển
                $amount             = $amount + $order->cost_price + $order->shipping_amount;
                $orderDropshipIds[] = $order->id;
            }
            if (!$amount) {
                $orderIds[] = $order->id;
                continue;
            }
            if (!$merchant = $order->merchant) {
                $this->logger()->error('document ' . $document->code . ' - has purchasing with empty merchant ' . $order->code);
                continue;
            }

            if (
                $order->finance_service_status == Order::FINANCE_STATUS_UNPAID
            ) {
                $purchaseUnits[0] = [
                    'name' => $order->code,
                    'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type]),
                    'orderId' => 'order-' . $order->id,
                    'referenceId' => $order->id,
                    'amount' => $amount,
                    'customType' => Transaction::TYPE_EXPORT_SERVICE,
                ];
                $transaction      = Service::transaction()->create(
                    $merchant,
                    Transaction::ACTION_COLLECT,
                    ['purchaseUnits' => $purchaseUnits]
                );
                $this->logger()->info('transaction_export_service_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
                $merchant->buildMerchantTransaction(
                    MerchantTransaction::ACTION_COLLECT,
                    Transaction::TYPE_EXPORT_SERVICE,
                    $amount,
                    $order
                )->withTransId($transaction->_id)->create();

                dispatch(new ProcessExportServiceTransactionJob($transaction->_id, $order->id));
            }
        }

        if (!empty($orderDropshipIds)) {
            //đối với đơn dropship sẽ chuyển trang luôn trạng thái tài chính Đã Thanh Toán mà k + COD cho seller
            Order::query()
                ->whereIn('id', $orderDropshipIds)
                ->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
        }

        if (!empty($orderIds)) {
            //đối với đơn không có tiền dịch vụ sẽ chuyển trang luôn trạng thái tài chính dịch vụ đã thanh toán
            Order::query()
                ->whereIn('id', $orderIds)
                ->update(['finance_service_status' => Order::FINANCE_STATUS_PAID]);
        }
    }

    /**
     * Thực hiện trừ tiền dịch vụ nhập hàng và chuyển trạng thái kiện nhập về Đã Thanh Toán
     * @param Document $document
     */
    protected function documentImporting(Document $document)
    {
        $tenant               = $document->tenant;
        $importingBarcodes    = $document->importingBarcodes;
        $purchasingPackageIds = [];
        foreach ($importingBarcodes as $importingBarcode) {
            $objectId          = $importingBarcode->object_id;
            $purchasingPackage = null;
            if (!in_array($importingBarcode->type, [ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL, ImportingBarcode::TYPE_PACKAGE_CODE])) {
                continue;
            }
            $purchasingPackage = $tenant->purchasingPackages()->firstWhere('id', $objectId);
            if (
                !$purchasingPackage instanceof PurchasingPackage ||
                $purchasingPackage->finance_status == PurchasingPackage::FINANCE_STATUS_PAID
            ) {
                continue;
            }
            if (!$purchasingPackage->service_amount) {
                $purchasingPackageIds[] = $purchasingPackage->id;
                continue;
            }
            if (!$merchant = $purchasingPackage->merchant) {
                $this->logger()->error('document ' . $document->code . ' - has purchasing with empty merchant ' . $purchasingPackage->code);
                continue;
            }

            $purchaseUnits[0] = [
                'name' => $purchasingPackage->code,
                'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type]),
                'orderId' => 'purchasing_package-' . $purchasingPackage->id,
                'referenceId' => $purchasingPackage->id,
                'amount' => $purchasingPackage->service_amount,
                'customType' => Transaction::TYPE_IMPORT_SERVICE,
            ];
            $transaction      = Service::transaction()->create(
                $merchant,
                Transaction::ACTION_COLLECT,
                ['purchaseUnits' => $purchaseUnits]
            );
            $this->logger()->info('transaction_import_service_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
            $merchant->buildMerchantTransaction(
                MerchantTransaction::ACTION_COLLECT,
                Transaction::TYPE_IMPORT_SERVICE,
                $purchasingPackage->service_amount,
                $purchasingPackage
            )->withTransId($transaction->_id)->create();

            dispatch(new ProcessImportServiceTransactionJob($transaction->_id, $purchasingPackage->id));
        }

        if (!empty($purchasingPackageIds)) { //những kiện không có giá dịch vụ thì sẽ chuyển trạng thái Đã Thanh Toán tài chính
            PurchasingPackage::query()
                ->whereIn('id', $purchasingPackageIds)
                ->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
        }
    }

    /**
     * Khi xác nhận chứng từ đối soát:
     * Cộng COD vào tài khoản seller,
     * Trừ chi phí vận chuyển, chi phí COD, chi phí mở rộng vào tài khoản seller
     * Chuyển đơn hàng sang trạng thái tài chính "Đã thanh toán"
     *
     * @param Document $document
     */
    protected function documentFreightBillInventory(Document $document)
    {
        $freightBillInventoryFinanceFeeIds    = [];
        $freightBillInventoryFinanceCodIds    = [];
        $freightBillInventoryFinanceExtentIds = [];
        $noCodorderIds                        = [];
        $orderExtentIds                       = [];

        /** @var DocumentFreightBillInventory $freightBillInventory */
        foreach ($document->documentFreightBillInventories as $freightBillInventory) {
            $order = $freightBillInventory->order;
            if ($order->dropship) continue; // logic đơn hàng dropship sẽ k chạy logic gì cả, số tiền COD được cộng luôn khi xác nhận đóng hàng
            if (!$merchant = $order->merchant) {
                $this->logger()->error('document ' . $document->code . ' - has order with empty merchant ' . $order->code);
                continue;
            }

            if ($freightBillInventory->finance_status_cod == DocumentFreightBillInventory::FINANCE_STATUS_UNPAID) {
                if ($freightBillInventory->cod_paid_amount) {
                    $purchaseUnits[0] = [
                        'name' => $order->code,
                        'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type, 'freight_bill_inventory' => $freightBillInventory->id]),
                        'orderId' => 'order-' . $order->id,
                        'memo' => $freightBillInventory->id,
                        'referenceId' => $order->id,
                        'amount' => $freightBillInventory->cod_paid_amount,
                        'customType' => Transaction::TYPE_COD,
                    ];
                    $transaction      = Service::transaction()->create(
                        $merchant,
                        Transaction::ACTION_REFUND,
                        ['purchaseUnits' => $purchaseUnits]
                    );
                    $this->logger()->info('transaction_cod_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
                    $merchant->buildMerchantTransaction(
                        MerchantTransaction::ACTION_COLLECT,
                        Transaction::TYPE_COD,
                        $freightBillInventory->cod_paid_amount,
                        $freightBillInventory
                    )->withTransId($transaction->_id)->create();

                    dispatch(new ProcessCodTransactionJob($transaction->_id, $order->id, $freightBillInventory->id));
                } else {
                    $freightBillInventoryFinanceCodIds[] = $freightBillInventory->id;
                    $noCodorderIds[]                     = $order->id;
                }
            }

            if (($freightBillInventory->finance_status_extent == DocumentFreightBillInventory::FINANCE_STATUS_UNPAID)
                && empty($order->extent_service_amount)) {
                if ($freightBillInventory->extent_amount) {
                    $purchaseUnits[0] = [
                        'name' => $order->code,
                        'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type, 'freight_bill_inventory' => $freightBillInventory->id]),
                        'orderId' => 'order-' . $order->id,
                        'memo' => $freightBillInventory->id,
                        'referenceId' => $order->id,
                        'amount' => $freightBillInventory->extent_amount,
                        'customType' => Transaction::TYPE_EXTENT,
                    ];
                    $transaction      = Service::transaction()->create(
                        $merchant,
                        Transaction::ACTION_COLLECT,
                        ['purchaseUnits' => $purchaseUnits]
                    );
                    $this->logger()->info('transaction_extent_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
                    $merchant->buildMerchantTransaction(
                        MerchantTransaction::ACTION_COLLECT,
                        Transaction::TYPE_EXTENT,
                        $freightBillInventory->extent_amount,
                        $freightBillInventory
                    )->withTransId($transaction->_id)->create();

                    dispatch(new ProcessExtentServiceTransactionJob($transaction->_id, $order->id, $freightBillInventory->id));
                } else {
                    $freightBillInventoryFinanceExtentIds[] = $freightBillInventory->id;
                    $orderExtentIds[]                       = $order->id;
                }
            }

            $shippingFee = $freightBillInventory->cod_fee_amount + $freightBillInventory->other_fee + $freightBillInventory->shipping_amount;
            if ($freightBillInventory->finance_status_fee == DocumentFreightBillInventory::FINANCE_STATUS_UNPAID) {
                if ($shippingFee) {
                    $purchaseUnits[0] = [
                        'name' => $order->code,
                        'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type, 'freight_bill_inventory' => $freightBillInventory->id]),
                        'orderId' => 'order-' . $order->id,
                        'memo' => $freightBillInventory->id,
                        'referenceId' => $order->id,
                        'amount' => $shippingFee,
                        'customType' => Transaction::TYPE_SHIPPING,
                    ];
                    $transaction      = Service::transaction()->create(
                        $merchant,
                        Transaction::ACTION_COLLECT,
                        ['purchaseUnits' => $purchaseUnits]
                    );
                    $this->logger()->info('transaction_shipping_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
                    $merchant->buildMerchantTransaction(
                        MerchantTransaction::ACTION_COLLECT,
                        Transaction::TYPE_SHIPPING,
                        $shippingFee,
                        $freightBillInventory
                    )->withTransId($transaction->_id)->create();
                    dispatch(new ProcessShippingFeeTransactionJob($transaction->_id, $freightBillInventory->id));
                } else {
                    $freightBillInventoryFinanceFeeIds[] = $freightBillInventory->id;
                }
            }
        }

        /**
         * Xử lý update lại trạng thái cho các đơn và chứng từ không có số tiền trạng thái thành đã thanh toán
         */
        Order::query()->whereIn('id', $noCodorderIds)->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
        DocumentFreightBillInventory::query()->whereIn('id', $freightBillInventoryFinanceCodIds)
            ->update([
                'finance_status_cod' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
            ]);
        Order::query()->whereIn('id', $orderExtentIds)->update(['finance_extent_service_status' => Order::FINANCE_STATUS_PAID]);
        DocumentFreightBillInventory::query()->whereIn('id', $freightBillInventoryFinanceExtentIds)
            ->update([
                'finance_status_extent' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
            ]);
        DocumentFreightBillInventory::query()->whereIn('id', $freightBillInventoryFinanceFeeIds)
            ->update([
                'finance_status_fee' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
            ]);
    }

    /**
     * @param Document $document
     * @param array $transactions
     */
    protected function processTransactionFreightBillInventory(Document $document, array $transactions = [])
    {
        $merchants = $this->getMerchants(array_keys($transactions));
        foreach ($transactions as $merchantId => $info) {
            $merchant = $merchants[$merchantId] ?? null;

            $logMessage = 'transaction_created  ' . $document->code . ' - ' . $merchantId;

            if (isset($info['cod'])) {
                $transactionId = Service::transaction()
                    ->create($merchant, Transaction::ACTION_DEPOSIT, ['purchaseUnits' => $info['cod']])->_id;

                $this->logger()->info($logMessage . ' - COD ' . $transactionId);

                $orderIds                          = Arr::get($info, 'orderIds', []);
                $freightBillInventoryFinanceCodIds = Arr::get($info, 'freightBillInventoryFinanceCodIds', []);

                dispatch(new ProcessCodTransactionJob($transactionId, $orderIds, $freightBillInventoryFinanceCodIds));
            }

            if (isset($info['extent'])) {
                $transactionId = Service::transaction()
                    ->create($merchant, Transaction::ACTION_COLLECT, ['purchaseUnits' => $info['extent']])->_id;

                $this->logger()->info($logMessage . ' - extent ' . $transactionId);

                $orderExtentIds                       = Arr::get($info, 'orderExtentIds', []);
                $freightBillInventoryFinanceExtentIds = Arr::get($info, 'freightBillInventoryFinanceExtentIds', []);

                dispatch(new ProcessExtentServiceTransactionJob($transactionId, $orderExtentIds, $freightBillInventoryFinanceExtentIds));
            }

            if (isset($info['shipping'])) {
                $transactionId = Service::transaction()
                    ->create($merchant, Transaction::ACTION_COLLECT, ['purchaseUnits' => $info['shipping']])->_id;

                $this->logger()->info($logMessage . ' - shipping fee ' . $transactionId);
                $freightBillInventoryFinanceFeeIds = Arr::get($info, 'freightBillInventoryFinanceFeeIds', []);
                dispatch(new ProcessShippingFeeTransactionJob($transactionId, $freightBillInventoryFinanceFeeIds));
            }

        }
    }

    /**
     * @param Document $document
     */
    public function documentImportingReturnGoods(Document $document)
    {
        $importingBarcodes = $document->importingBarcodes;
        $orderIds          = [];

        foreach ($importingBarcodes as $importingBarcode) {
            $freightBill = FreightBill::find($importingBarcode->object_id);
            $order       = $freightBill->order;
            if (
                !$order instanceof Order ||
                $order->finance_service_import_return_goods_status == Order::FINANCE_STATUS_PAID
            ) {
                continue;
            }

            if (!$order->service_import_return_goods_amount) {
                $orderIds[] = $order->id;
                continue;
            }
            if (!$merchant = $order->merchant) {
                $this->logger()->error('document ' . $document->code . ' - has order with empty merchant ' . $order->code);
                continue;
            }

            $purchaseUnits[0] = [
                'name' => $order->code,
                'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type]),
                'orderId' => 'order-' . $order->id,
                'referenceId' => $order->id,
                'amount' => $order->service_import_return_goods_amount,
                'customType' => Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE
            ];
            $transaction      = Service::transaction()->create(
                $merchant,
                Transaction::ACTION_COLLECT,
                ['purchaseUnits' => $purchaseUnits]
            );
            $this->logger()->info('transaction_import_return_service_created ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
            $merchant->buildMerchantTransaction(
                MerchantTransaction::ACTION_COLLECT,
                Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE,
                $order->service_import_return_goods_amount,
                $order
            )->withTransId($transaction->_id)->create();

            dispatch(new ProcessImportReturnGoodsServiceTransactionJob($transaction->_id, $order->id));
        }

        if (!empty($orderIds)) { //vẫn chuyển trạng thái với đơn k có phí hàng hoàn
            Order::query()
                ->whereIn('id', $orderIds)
                ->update(['finance_service_import_return_goods_status' => Order::FINANCE_STATUS_PAID]);
        }
    }

    /**
     * @param Document $document
     */
    public function documentExporting(Document $document)
    {
        $orderIds          = [];
        foreach ($document->orderExportings as $orderExporting) {
            $order = $orderExporting->order;
            if (
                !$order instanceof Order ||
                $order->finance_cost_of_goods_status == Order::FINANCE_STATUS_PAID
            ) {
                continue;
            }
            if (!$merchant = $order->merchant) {
                $this->logger()->error('document ' . $document->code . ' - has order with empty merchant ' . $order->code);
                continue;
            }
            $order->setCostOfGoods();
            if (empty($order->cost_of_goods)) {
                continue;
            }

            $purchaseUnits[0] = [
                'name' => $order->code,
                'description' => json_encode(['document_id' => $document->id, 'code' => $document->code, 'type' => $document->type]),
                'orderId' => 'order-' . $order->id,
                'referenceId' => $order->id,
                'amount' => $order->cost_of_goods,
                'customType' => Transaction::TYPE_COST_OF_GOODS
            ];
            $merchantTransaction = $merchant->buildMerchantTransaction(
                MerchantTransaction::ACTION_COLLECT,
                Transaction::TYPE_COST_OF_GOODS,
                $order->cost_of_goods,
                $order
            )->create();
            $transaction      = Service::transaction()->create(
                $merchant,
                Transaction::ACTION_COLLECT,
                ['purchaseUnits' => $purchaseUnits]
            );
            $this->logger()->info('transaction_cost_of_goods ' . $document->code . ' - ' . $transaction->_id . ' - ' . $merchant->id);
            $merchantTransaction->trans_id = $transaction->_id;
            $merchantTransaction->save();

            dispatch(new ProcessCostOfGoodsTransactionJob($transaction->_id, $order->id));
        }

        if (!empty($orderIds)) { //vẫn chuyển trạng thái với đơn k có phí hàng hoàn
            Order::query()
                ->whereIn('id', $orderIds)
                ->update(['finance_service_import_return_goods_status' => Order::FINANCE_STATUS_PAID]);
        }
    }
}
