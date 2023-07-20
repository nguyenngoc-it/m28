<?php

namespace Modules\Order\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Validators\ImportMerchantFreightBillValidator;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportMerchantFreightBill
{
    /** @var User $user */
    protected $user;
    /** @var Merchant */
    protected $merchant;
    /** @var UploadedFile $file */
    protected $file;
    /** @var bool $replace */
    protected $replace;
    /** @var array $errors */
    protected $errors = [];
    /** @var array $insertedOrderPackings */
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBill constructor.
     * @param UploadedFile $file
     * @param Merchant $merchant
     * @param User $user
     * @param bool $replace
     */
    public function __construct(UploadedFile $file, Merchant $merchant, User $user, $replace = false)
    {
        $this->user     = $user;
        $this->merchant = $merchant;
        $this->replace  = $replace;
        $this->file     = $file;
    }

    /**
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
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
        $row     = array_map(function ($value) {
            return trim($value);
        }, $row);
        $rowData = array_filter($row, function ($value) {
            return !empty($value);
        });
        if (!count($rowData)) {
            return;
        }
        $rowInput  = $this->makeRow($row);
        $validator = new ImportMerchantFreightBillValidator($this->merchant, $rowInput, $this->user, $this->insertedOrderPackings);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'order_code' => isset($rowInput['order_code']) ? $rowInput['order_code'] : null,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        if (!$validator->getOrderPacking()->freightBill || $this->replace) {
            Service::orderPacking()->createTrackingNoByManual($validator->getOrderPacking(), $validator->getFreightBill(), $this->user, $validator->getShippingPartner());
        }

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
