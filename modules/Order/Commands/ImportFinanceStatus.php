<?php

namespace Modules\Order\Commands;

use Exception;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFinanceStatus
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $errors;

    protected $importCodes = [];

    /**
     * ImportFinanceStatus constructor.
     * @param $filePath
     * @param User $user
     */
    public function __construct($filePath, User $user)
    {
        $this->tenant   = $user->tenant;
        $this->filePath = $filePath;
        $this->user     = $user;
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

        @unlink($this->filePath);

        return $this->errors;
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

        $row = $this->makeRow($row);
        if (!$row) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }

        if(isset($this->importCodes[$row['order_code']])) {
            return;
        }

        $order = $this->tenant->orders()->firstWhere('code', $row['order_code']);
        if(!$order instanceof Order) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'order_code_invalid',
                'order_code' => $row['order_code']
            ];
            return;
        }
        if($order->finance_status != Order::FINANCE_STATUS_UNPAID) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'order_status_invalid',
                'order_code' => $row['order_code']
            ];
            return;
        }

        Service::order()->updateFinanceStatus($order, Order::FINANCE_STATUS_PAID, $this->user);

        $this->importCodes[$row['order_code']] = true;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'order_code',
            'freight_bill_code',
            'service',
            'amount',
            'status',
            'finance_status'
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