<?php

namespace Modules\Merchant\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Modules\Service;
use Modules\Transaction\Models\Transaction;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadTransactions
{
    /**
     * @var array
     */
    protected $transactions;

    /**
     * DownloadReceivedSkus constructor.
     * @param array $transactions
     */
    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
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
        return (new FastExcel($this->transactions))
            ->export('downloadTransactions.xlsx', function (array $transaction) {
                $detailNote = $this->makeDetailNote($transaction);
                return [
                    trans('created_at') => Carbon::create($transaction['timestamp'])->setTimezone('asia/ho_chi_minh')->format('H:i:s d/m/Y'),
                    trans('transaction_type') => trans(Service::transaction()->renderMessageType($transaction['transactionType'], !empty($transaction['purchaseUnits'][0]['customType']) ? $transaction['purchaseUnits'][0]['customType'] : null)),
                    trans('amount') => $transaction['amount'],
                    trans('balance_after_transaction') => $transaction['balanceAfter'],
                    trans('note') => $detailNote['note'],
                    trans('detail') . ' ' . trans('transaction') => $detailNote['detail'],
                    '#' => $transaction['id']
                ];
            });
    }

    /**
     * @param array $transaction
     * @return array
     */
    protected function makeDetailNote(array $transaction)
    {
        $result  = [
            'note' => '',
            'detail' => ''
        ];
        $orderId = !empty($transaction['purchaseUnits'][0]['orderId']) ? $transaction['purchaseUnits'][0]['orderId'] : null;
        if (($sep = explode('-', $orderId)) && $orderId) {
            $result['detail'] = !empty($sep[1]) ? $sep[1] : $sep[0];
        }
        if ($transaction['transactionType'] == Transaction::ACTION_GENESIS) {
            $result['note'] = $transaction['memo'] ?: '';
            return $result;
        }

        switch ($transaction['purchaseUnits'][0]['customType']) {
            case Transaction::ACTION_DEPOSIT:
            case Transaction::ACTION_COLLECT:
            case Transaction::ACTION_WITHDRAW:
                $result['note'] = $transaction['purchaseUnits'][0]['description'];
                break;
            case Transaction::TYPE_STORAGE_FEE:
                $result['note'] = trans('storage_amount') . ' ' . Carbon::create($transaction['timestamp'])->setTimezone('asia/ho_chi_minh')->format('d/m/Y');
                break;
            case Transaction::TYPE_COD:
            case Transaction::TYPE_IMPORT_SERVICE:
            case Transaction::TYPE_EXPORT_SERVICE:
            case Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE:
            case Transaction::TYPE_SHIPPING:
            case Transaction::TYPE_EXTENT:
                $description    = $transaction['purchaseUnits'][0]['description'];
                $description    = json_decode($description, true);
                $documentCode   = !empty($description['code']) ? $description['code'] : '';
                $result['note'] = trans('confirm_document_' . strtolower($transaction['purchaseUnits'][0]['customType'])) . ' ' . $documentCode;
                break;
        }

        return $result;
    }
}
