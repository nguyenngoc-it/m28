<?php

namespace Modules\Document\Jobs;

use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Order\Models\Order;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\Transaction;

class ProcessOrderTransactionsJob extends ProcessTransactionJob
{
    /**
     * @var array
     */
    protected $transactionIds;

    /**
     * @var array
     */
    protected $orderIds;

    /**
     * @var array
     */
    protected $freightBillInventoryFinanceFeeIds;

    /**
     * @var array
     */
    protected $freightBillInventoryFinanceCodIds;

    /**
     * @var array
     */
    protected $freightBillInventoryFinanceExtentIds;

    /**
     * @var array
     */
    protected $orderExtentIds;


    /**
     * ProcessOrderTransactionsJob constructor.
     * @param $transactionIds
     * @param $orderIds
     * @param $freightBillInventoryFinanceFeeIds
     * @param $freightBillInventoryFinanceCodIds
     * @param $freightBillInventoryFinanceExtentIds
     * @param $orderExtentIds
     */
    public function __construct(
        $transactionIds = [],
        $orderIds = [],
        $freightBillInventoryFinanceFeeIds = [],
        $freightBillInventoryFinanceCodIds = [],
        $freightBillInventoryFinanceExtentIds = [],
        $orderExtentIds = []
    )
    {
        $this->transactionIds = $transactionIds;
        $this->orderIds       = $orderIds;
        $this->freightBillInventoryFinanceFeeIds = $freightBillInventoryFinanceFeeIds;
        $this->freightBillInventoryFinanceCodIds = $freightBillInventoryFinanceCodIds;
        $this->freightBillInventoryFinanceExtentIds = $freightBillInventoryFinanceExtentIds;
        $this->orderExtentIds = $orderExtentIds;
    }

    public function handle()
    {
        $this->logger()->info('start ', [
            'transactionIds' => $this->transactionIds,
            'freightBillInventoryFinanceFeeIds' => $this->freightBillInventoryFinanceFeeIds,
            'freightBillInventoryFinanceCodIds' => $this->freightBillInventoryFinanceCodIds,
            'freightBillInventoryFinanceExtentIds' => $this->freightBillInventoryFinanceExtentIds,
            'orderIds' => $this->orderIds,
            'orderExtentIds' => $this->orderExtentIds,
        ]);

        foreach ($this->transactionIds as $transactionId) {
            Transaction::find($transactionId)->process();
        }


        if (!empty($this->freightBillInventoryFinanceFeeIds)) {
            DocumentFreightBillInventory::query()
                ->whereIn('id', $this->freightBillInventoryFinanceFeeIds)
                ->update([
                    'finance_status_fee' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
                ]);
        }

        if (!empty($this->freightBillInventoryFinanceCodIds)) {
            DocumentFreightBillInventory::query()
                ->whereIn('id', $this->freightBillInventoryFinanceCodIds)
                ->update([
                    'finance_status_cod' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
                ]);
        }

        if (!empty($this->freightBillInventoryFinanceExtentIds)) {
            DocumentFreightBillInventory::query()
                ->whereIn('id', $this->freightBillInventoryFinanceExtentIds)
                ->update([
                    'finance_status_extent' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
                ]);
        }

        if (!empty($this->orderIds)) {
            Order::query()
                ->whereIn('id', $this->orderIds)
                ->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
        }

        if (!empty($this->orderExtentIds)) {
            Order::query()
                ->whereIn('id', $this->orderExtentIds)
                ->update(['finance_extent_service_status' => Order::FINANCE_STATUS_PAID]);
        }
    }
}
