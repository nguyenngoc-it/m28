<?php

namespace Modules\Order\Commands;

use App\Base\Validator;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFreightBillStatusNew
{

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $merchantIds = [];

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $insertedCodes = [];

    /**
     * ImportOrderStatus constructor.
     * @param User $user
     * @param $filePath
     */
    public function __construct(User $user, $filePath)
    {
        $this->tenant      = $user->tenant;
        $this->merchantIds = $user->merchants()->where('merchants.status', true)->pluck('merchants.id')->toArray();
        $this->filePath    = $filePath;
        $this->user        = $user;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->filePath, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        return $this->errors;
    }

    /**
     * @param $rowInput
     * @param $line
     * @param array $freightBills
     * @return bool
     */
    protected function validateData($rowInput, $line, &$freightBills = [])
    {
        if (empty($rowInput['freight_bill_code']) || empty($rowInput['order_code'])) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['order_or_freight_bill_code' => [Validator::ERROR_REQUIRED => []]],
            ];
            return false;
        }

        if (empty($rowInput['status'])) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['status' => [Validator::ERROR_REQUIRED => []]],
            ];
            return false;
        }

        if ($rowInput['status'] != FreightBill::STATUS_DELIVERED && $rowInput['status'] != FreightBill::STATUS_FAILED_DELIVERY && $rowInput['status'] != FreightBill::STATUS_RETURN) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['status' => [Validator::ERROR_INVALID => []]],
            ];
            return false;
        }

        $key = $rowInput['freight_bill_code'] . $rowInput['order_code'];
        if (isset($this->insertedCodes[$key])) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['order_or_freight_bill_code' => [Validator::ERROR_DUPLICATED => []]],
            ];
            return false;
        }

        $order             = null;
        $freightBillStatus = [FreightBill::STATUS_CONFIRMED_PICKED_UP, FreightBill::STATUS_DELIVERING, FreightBill::STATUS_WAIT_FOR_PICK_UP, FreightBill::STATUS_PICKED_UP];

        if (!empty($rowInput['order_code'])) {
            $order = $this->tenant->orders()->where(['code' => $rowInput['order_code']])
                ->whereIn('merchant_id', $this->merchantIds)->first();
            if (!$order instanceof Order) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['order_code' => [Validator::ERROR_INVALID => []]],
                ];
                return false;
            }
            $documents = $order->documents;
            foreach ($documents as $document) {
                if ($document->status == 'COMPLETED' && $document->type == 'FREIGHT_BILL_INVENTORY') {
                    $this->errors[] = [
                        'line' => $line,
                        'errors' => ['order_code' => [Validator::ERROR_INVALID => []]],
                    ];
                    return false;
                }
            }
            $freightBills = FreightBill::query()
                ->where(['tenant_id' => $this->user->tenant_id, 'order_id' => $order->id])
                ->whereIn('status', $freightBillStatus)
                ->get();
            if (!$freightBills->count()) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['order_no_freight_bill' => [Validator::ERROR_INVALID => []]],
                ];
                return false;
            }
        }

        if (!empty($rowInput['freight_bill_code'])) {
            $freightBill = FreightBill::query()
                ->where(['tenant_id' => $this->user->tenant_id, 'freight_bill_code' => $rowInput['freight_bill_code']])
                ->first();
            if (!$freightBill instanceof FreightBill) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['freight_bill_code' => [Validator::ERROR_INVALID => []]],
                ];
                return false;
            }
            if (
                !in_array($freightBill->order->merchant_id, $this->merchantIds) ||
                ($order instanceof Order && $order->id != $freightBill->order->id)
            ) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['freight_bill_code' => [Validator::ERROR_INVALID => []]],
                ];
                return false;
            }

            if (!in_array($freightBill->status, $freightBillStatus)) {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => ['freight_bill_status' => [Validator::ERROR_INVALID => []]],
                ];
                return false;
            }

            $freightBills = [$freightBill];
        }

        $this->insertedCodes[$key] = $key;

        return true;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function ($value) {
            return !empty($value);
        });

        if (!count($rowData)) {
            return;
        }

        $rowInput = $this->makeRow($row);
        if (empty($rowInput)) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }

        $freightBills = [];
        if (!$this->validateData($rowInput, $line, $freightBills)) {
            return;
        }
        $status = $rowInput['status'];
        foreach ($freightBills as $freightBill) {
            Service::freightBill()->changeStatus($freightBill, $status, $this->user);
        }

    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'freight_bill_code',
            'order_code',
            'status',
        ];
        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return [];
        }

        return array_combine($params, $values);
    }

}
