<?php

namespace Modules\PurchasingPackage\Commands;

use Exception;
use Modules\PurchasingPackage\Models\PurchasingPackage;
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

        if(isset($this->importCodes[$row['package_code']])) {
            return;
        }

        $purchasingPackage = $this->tenant->purchasingPackages()->firstWhere('code', $row['package_code']);
        if(!$purchasingPackage instanceof PurchasingPackage) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'package_code_invalid',
                'package_code' => $row['package_code']
            ];
            return;
        }
        if($purchasingPackage->finance_status != PurchasingPackage::FINANCE_STATUS_UNPAID) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'package_status_invalid',
                'package_code' => $row['package_code']
            ];
            return;
        }

        Service::purchasingPackage()->updateFinanceStatus($purchasingPackage, PurchasingPackage::FINANCE_STATUS_PAID, $this->user);

        $this->importCodes[$row['package_code']] = true;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'package_code',
            'service',
            'price',
            'quantity',
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