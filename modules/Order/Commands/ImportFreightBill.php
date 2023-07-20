<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Validators\ImportFreightBillValidator;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Services\OrderPackingEvent;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFreightBill
{
    /**
     * @var User
     */
    protected $user;

    /** @var Warehouse */
    protected $warehouse;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBill constructor.
     * @param UploadedFile $file
     * @param Warehouse $warehouse
     * @param User $user
     */
    public function __construct(UploadedFile $file, Warehouse $warehouse, User $user)
    {
        $this->user      = $user;
        $this->warehouse = $warehouse;
        $this->tenant    = $user->tenant;
        $this->file      = $file;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

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

        $rowInput  = $this->makeRow($row);
        $validator = new ImportFreightBillValidator($this->user, $this->warehouse, $rowInput, $this->insertedOrderPackings);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'order_code' => isset($rowInput['order_code']) ? $rowInput['order_code'] : null,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        Service::orderPacking()->createTrackingNoByManual($validator->getOrderPacking(), $validator->getFreightBill(), $this->user, $validator->getShippingPartner());

        $this->insertedOrderPackings[$rowInput['order_code']]['shipping_partner_code'][] = $rowInput['shipping_partner_code'];
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'order_code',
            'freight_bill',
            'shipping_partner_code',
        ];
        $values = array_values($row);
        $row    = array_combine($params, $values);

        return $row;
    }
}
