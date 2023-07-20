<?php

namespace Modules\Document\Commands;

use App\Base\Validator;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Support\Conversion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFreightBillInventory
{
    /** @var ShippingPartner */
    protected $shippingPartner;
    /** @var User $user */
    protected $user;

    /** @var Merchant | null */
    protected $merchant;

    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];
    /**
     * @var array
     */
    protected $message = [];

    /**
     * ImportSkuInventory constructor.
     * @param UploadedFile $file
     * @param ShippingPartner $shippingPartner
     * @param User $user
     */
    public function __construct(UploadedFile $file, ShippingPartner $shippingPartner, User $user)
    {
        $this->shippingPartner = $shippingPartner;
        $this->user            = $user;
        $this->file            = $file;
    }

    /**
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle(): array
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        if (empty($this->errors) && empty($this->processedRows)) {
            $this->errors[] = ['file' => 'empty'];
        }
        if ($this->message) {
            $this->message = $this->message[0];
        }

        return [
            'message' => $this->message,
            'errors' => $this->errors,
            'processed_rows' => $this->processedRows
        ];
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, int $line)
    {
        $row     = array_map(function ($value) {
            return trim($value);
        }, $row);
        $rowData = array_filter($row, function ($value) {
            return $value != '';
        });

        if (!count($rowData)) {
            return;
        }

        $row = $this->makeRow($row);
        if (!$row) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }

        if (
            empty($row['freight_bill_code']) &&
            empty($row['order_code'])
        ) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['freight_bill_code' => [Validator::ERROR_REQUIRED => []]],
            ];
            return;
        }

        $freightBill = null;
        if (!empty($row['freight_bill_code'])) {
            $freightBill = FreightBill::query()
                ->where('shipping_partner_id', $this->shippingPartner->id)
                ->where('freight_bill_code', $row['freight_bill_code'])->first();
        }

        if (!empty($row['order_code'])) {
            if (!$freightBill instanceof FreightBill) {
                $order = $this->user->tenant->orders()
                    ->where('code', $row['order_code'])->first();
                if (!$order instanceof Order) {
                    $this->errors[] = [
                        'line' => $line,
                        'errors' => ['order_code' => [Validator::ERROR_INVALID => []]],
                        'order_code' => $row['order_code'],
                    ];
                    return;
                }
                $freightBill = $order->freightBills()->where('shipping_partner_id', $this->shippingPartner->id)->first();
            } else if ($freightBill->order->code != $row['order_code']) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['order_code' => [Validator::ERROR_INVALID => []]],
                    'order_code' => $row['order_code'],
                ];
                return;
            }
        }

        if (!$freightBill instanceof FreightBill) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['freight_bill_code' => [Validator::ERROR_INVALID => []]],
                'freight_bill_code' => Arr::get($row, 'freight_bill_code'),
            ];
            return;
        }

        $sumAmount = DocumentFreightBillInventory::query()->selectRaw('SUM(cod_fee_amount) as sum_cod_fee_amount, SUM(shipping_amount) as sum_shipping_amount')
            ->where('freight_bill_id', $freightBill->id)->first()->toArray();
        $sumCodFeeAmount = Conversion::convertMoney((float)$sumAmount['sum_cod_fee_amount']);
        $sumShippingAmount = Conversion::convertMoney((float)$sumAmount['sum_shipping_amount']);

        /**
         * Tài chính có thể đối soát bổ sung chi phí với giá trị số âm (để giảm chi phí) (chỉ áp dụng đối với trường chi phí vận chuyển, chi phí COD)
         */
        foreach (['cod_fee_amount', 'shipping_amount'] as $p) {
            if ($row[$p] < 0) {
                $this->message [] = [
                    [
                        'line' => $line,
                        'message' => [$p => true]
                    ]
                ];
            }
        }
        foreach (['cod_paid_amount', 'cod_fee_amount', 'shipping_amount'] as $p) {
            if ($row[$p] === "" || $row[$p] === " ") {
                $row[$p] = null;
            }
            if ($row[$p] === null) {
                continue;
            }
            if ($p == 'cod_paid_amount' && (!empty($row[$p])) && (!is_numeric($row[$p]) || $row[$p] < 0)) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => [$p => [Validator::ERROR_INVALID => []]],
                    'amount' => $row[$p],
                ];
                return;
            }
            if ($p != 'cod_paid_amount' && (!empty($row[$p])) && (!is_numeric($row[$p]))) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => [$p => [Validator::ERROR_INVALID => []]],
                    'amount' => $row[$p],
                ];
                return;
            }
            if ($p != 'cod_paid_amount' && ($sumCodFeeAmount + $row[$p] < 0) || $sumShippingAmount + $row[$p] < 0) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => [$p => [Validator::ERROR_INVALID => []]],
                    'amount' => $row[$p],
                ];
                return;
            }

        }

        $row['freight_bill'] = $freightBill;

        /**
         * Tính lại phí mở rộng theo COD đã thu
         */
        if ($freightBill->order) {
            $row['extent_amount'] = 0;
        }

        $this->processedRows[] = $row;
    }

    /**
     * @param array $row
     * @return array|bool
     */
    protected function makeRow(array $row)
    {
        $params = [
            'freight_bill_code',
            'order_code',
            'cod_paid_amount',
            'cod_fee_amount',
            'shipping_amount',
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return false;
        }

        return array_combine($params, $values);
    }
}
