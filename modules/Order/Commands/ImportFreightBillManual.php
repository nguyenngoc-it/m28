<?php /** @noinspection SpellCheckingInspection */

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Order\Validators\ImportFreightBillManualValidator;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFreightBillManual
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @var array
     */
    protected $errors = [];
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBill constructor.
     * @param UploadedFile $file
     * @param User $user
     */
    public function __construct(UploadedFile $file, User $user)
    {
        $this->user = $user;
        $this->file = $file;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle(): array
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
    protected function processRow(array $row, int $line)
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
        $validator = new ImportFreightBillManualValidator($this->user, $rowInput, $this->insertedOrderPackings);
        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'order_code' => $rowInput['order_code'] ?? null,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        Service::orderPacking()->createTrackingNoByManual($validator->getOrderPacking(), $validator->getFreightBill(), $this->user, $validator->getShippingPartner(), Arr::get($rowInput, 'freight_bill_status'));
        $this->insertedOrderPackings[$rowInput['order_code']]['shipping_partner_code'][] = $rowInput['shipping_partner_code'];
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row): array
    {
        $params = [
            'order_code',
            'freight_bill',
            'shipping_partner_code',
            'freight_bill_status',
            'receiver_phone'
        ];
        $values = array_values(array_map('trim', $row));

        return array_combine($params, $values);
    }
}
