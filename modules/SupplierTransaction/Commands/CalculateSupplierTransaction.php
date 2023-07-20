<?php

namespace Modules\SupplierTransaction\Commands;

use Gobiz\Log\LogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\Supplier\Models\Supplier;
use Modules\SupplierTransaction\Jobs\ProcessSupplierTransactionJob;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\SupplierTransObjInterface;
use Psr\Log\LoggerInterface;

class CalculateSupplierTransaction
{
    protected $supplierTransObj;
    protected $type;
    /** @var LoggerInterface */
    protected $logger;
    /** @var array */
    protected $supplierAmounts = [];

    /**
     * BalanceSku constructor.
     * @param SupplierTransObjInterface $supplierTransObj
     * @param string $type
     */
    public function __construct(SupplierTransObjInterface $supplierTransObj, string $type)
    {
        $this->supplierTransObj = $supplierTransObj;
        $this->type             = $type;
        $this->logger           = LogService::logger('cal_supplier_transaction');
        $this->buildSupplierAmounts();
    }

    /**
     * @return void
     */
    protected function buildSupplierAmounts()
    {
        if ($this->supplierTransObj instanceof Order) {
            /** @var OrderSku $orderSku */
            foreach ($this->supplierTransObj->orderSkus as $orderSku) {
                if (($supplier = $orderSku->sku->supplier) && ($batchOfGood = $orderSku->sku->batchOfGood)) {
                    $this->supplierAmounts[$supplier->id]['supplier'] = $supplier;
                    $this->supplierAmounts[$supplier->id]['amount']   = isset($this->supplierAmounts[$supplier->id]['amount']) ?
                        $this->supplierAmounts[$supplier->id]['amount'] + ($batchOfGood->cost_of_goods * $orderSku->quantity) :
                        $batchOfGood->cost_of_goods * $orderSku->quantity;
                }
            }
        }
        if ($this->supplierTransObj instanceof PurchasingPackage) {
            /** @var PurchasingPackageItem $purchasingPackageItem */
            foreach ($this->supplierTransObj->purchasingPackageItems as $purchasingPackageItem) {
                if (!$purchasingPackageItem->sku) {
                    continue;
                }
                if (($supplier = $purchasingPackageItem->sku->supplier) && ($batchOfGood = $purchasingPackageItem->sku->batchOfGood)) {
                    $this->supplierAmounts[$supplier->id]['supplier'] = $supplier;
                    $this->supplierAmounts[$supplier->id]['amount']   = isset($this->supplierAmounts[$supplier->id]['amount']) ?
                        $this->supplierAmounts[$supplier->id]['amount'] + ($batchOfGood->cost_of_goods * $purchasingPackageItem->quantity) :
                        $batchOfGood->cost_of_goods * $purchasingPackageItem->quantity;
                }
            }
        }

        if ($this->supplierTransObj instanceof Document) {
            $documentSupplierTransaction = $this->supplierTransObj->documentSupplierTransaction;
            $this->supplierAmounts[$documentSupplierTransaction->supplier_id]['supplier'] = $documentSupplierTransaction->supplier;
            $this->supplierAmounts[$documentSupplierTransaction->supplier_id]['amount']   = $documentSupplierTransaction->amount;
        }
    }

    /**
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function handle()
    {
        DB::transaction(function () {
            switch ($this->type) {
                case SupplierTransaction::TYPE_PAYMENT_DEPOSIT:
                    $this->execPaymentDeposit();
                    break;
                case SupplierTransaction::TYPE_PAYMENT_COLLECT:
                    $this->execPaymentCollect();
                    break;
                case SupplierTransaction::TYPE_IMPORT:
                    $this->execImport();
                    break;
                case SupplierTransaction::TYPE_EXPORT:
                    $this->execWhenExport();
                    break;
                case SupplierTransaction::TYPE_IMPORT_BY_RETURN:
                    $this->execImportForReturn();
                    break;
            }
        });
    }

    /**
     * @return void
     */
    protected function execPaymentDeposit()
    {
        foreach ($this->supplierAmounts as $supplierAmount) {
            /** @var Supplier $supplier */
            $supplier = $supplierAmount['supplier'];
            if (empty($supplierAmount['amount'])) {
                $this->logger->error('execPaymentDeposit amount empty for supplier ' . $supplier->name);
            } else {
                $this->plusSoldTransaction($supplier, $supplierAmount['amount']);
            }
        }
    }

    /**
     * @return void
     */
    protected function execPaymentCollect()
    {
        foreach ($this->supplierAmounts as $supplierAmount) {
            /** @var Supplier $supplier */
            $supplier = $supplierAmount['supplier'];
            if (empty($supplierAmount['amount'])) {
                $this->logger->error('execPaymentDeposit amount empty for supplier ' . $supplier->name);
            } else {
                $this->subSoldTransaction($supplier, $supplierAmount['amount']);
            }
        }
    }

    /**
     * @return void
     */
    protected function execImport()
    {
        foreach ($this->supplierAmounts as $supplierAmount) {
            /** @var Supplier $supplier */
            $supplier = $supplierAmount['supplier'];
            if (empty($supplierAmount['amount'])) {
                $this->logger->error('execImport amount empty for supplier ' . $supplier->name);
            } else {
                $this->plusInventoryTransaction($supplier, $supplierAmount['amount']);
            }
        }
    }

    /**
     * @return void
     */
    protected function execWhenExport()
    {
        foreach ($this->supplierAmounts as $supplierAmount) {
            /** @var Supplier $supplier */
            $supplier = $supplierAmount['supplier'];
            if (empty($supplierAmount['amount'])) {
                $this->logger->error('execWhenExport amount empty for supplier ' . $supplier->name);
            } else {
                $this->subInventoryAndPlusSoldTransaction($supplier, $supplierAmount['amount']);
            }
        }
    }

    /**
     * @return void
     */
    protected function execImportForReturn()
    {
        foreach ($this->supplierAmounts as $supplierAmount) {
            /** @var Supplier $supplier */
            $supplier = $supplierAmount['supplier'];
            if (empty($supplierAmount['amount'])) {
                $this->logger->error('execImportForReturn amount empty for supplier ' . $supplier->name);
            } else {
                $this->plusInventoryAndSubSoldTransaction($supplier, $supplierAmount['amount']);
            }
        }
    }

    /**
     * @param Supplier $supplier
     * @param SupplierTransaction $supplierTransaction
     * @param string $wallet
     * @param string $action
     * @return Transaction
     */
    protected function createTransaction(Supplier $supplier, SupplierTransaction $supplierTransaction, string $wallet, string $action)
    {
        $purchaseUnits    = [];
        $purchaseUnits[0] = [
            'name' => $supplier->code,
            'description' => json_encode([
                'object_id' => $this->supplierTransObj->getObjectId(), 'object_type' => $this->supplierTransObj->getObjectType(),
                'supplier_id' => $supplier->id, 'supplier_code' => $supplier->code,
            ]),
            'orderId' => 'supplier-transaction-' . $supplierTransaction->id,
            'referenceId' => $supplierTransaction->id,
            'amount' => $supplierTransaction->amount,
            'customType' => 'SUPPLIER_' . $this->type,
        ];

        if ($wallet == Supplier::WALLET_INVENTORY) {
            $supplierWallet = $supplier->inventoryWallet();
        } else {
            $supplierWallet = $supplier->soldWallet();
        }

        return $supplierWallet->createTransaction($action, ['purchaseUnits' => $purchaseUnits]);
    }

    /**
     * @param Supplier $supplier
     * @param $amount
     * @return SupplierTransaction
     */
    protected function buildSupplierTransaction(Supplier $supplier, $amount)
    {
        return $supplier->buildSupplierTransaction(
            $this->type,
            $amount,
            $this->supplierTransObj
        )->create();
    }

    /**
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function subSoldTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transaction                        = $this->createTransaction($supplier, $supplierTransaction, Supplier::WALLET_SOLD, Transaction::ACTION_COLLECT);
        $supplierTransaction->sold_trans_id = $transaction->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }

    /**
     * @return void
     */
    protected function subInventoryTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transaction                             = $this->createTransaction($supplier, $supplierTransaction, Supplier::WALLET_INVENTORY, Transaction::ACTION_COLLECT);
        $supplierTransaction->inventory_trans_id = $transaction->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }

    /**
     * @return void
     */
    protected function plusInventoryTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transaction                             = $this->createTransaction($supplier, $supplierTransaction,
            Supplier::WALLET_INVENTORY, Transaction::ACTION_REFUND);
        $supplierTransaction->inventory_trans_id = $transaction->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }

    /**
     * @param Supplier $supplier
     * @param float $amount
     * @return void
     */
    protected function plusSoldTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transaction                        = $this->createTransaction($supplier, $supplierTransaction, Supplier::WALLET_SOLD, Transaction::ACTION_REFUND);
        $supplierTransaction->sold_trans_id = $transaction->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }

    /**
     * @return void
     */
    protected function plusInventoryAndSubSoldTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transactionRefund                       = $this->createTransaction($supplier, $supplierTransaction,
            Supplier::WALLET_INVENTORY, Transaction::ACTION_REFUND);
        $transactionCollect                      = $this->createTransaction($supplier, $supplierTransaction,
            Supplier::WALLET_SOLD, Transaction::ACTION_COLLECT);
        $supplierTransaction->inventory_trans_id = $transactionRefund->_id;
        $supplierTransaction->sold_trans_id      = $transactionCollect->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }

    /**
     * @return void
     */
    protected function subInventoryAndPlusSoldTransaction(Supplier $supplier, float $amount)
    {
        $supplierTransaction = $this->buildSupplierTransaction($supplier, $amount);

        $transactionRefund                       = $this->createTransaction($supplier, $supplierTransaction,
            Supplier::WALLET_SOLD, Transaction::ACTION_REFUND);
        $transactionCollect                      = $this->createTransaction($supplier, $supplierTransaction,
            Supplier::WALLET_INVENTORY, Transaction::ACTION_COLLECT);
        $supplierTransaction->sold_trans_id      = $transactionRefund->_id;
        $supplierTransaction->inventory_trans_id = $transactionCollect->_id;
        $supplierTransaction->save();

        dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
    }
}
