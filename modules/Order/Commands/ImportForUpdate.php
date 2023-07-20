<?php

namespace Modules\Order\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Order\Commands\RelateObjects\InputOrderByFile;
use Modules\Order\Validators\ImportedForUpdateValidator;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportForUpdate
{
    /**
     * @var User
     */
    protected $user;
    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];


    public function __construct(UploadedFile $file, User $user)
    {
        $this->user = $user;
        $this->file = $file;
    }

    /**
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws Exception
     */
    public function handle()
    {
        $line  = 1;
        $datas = [];
        (new FastExcel())->import($this->file, function ($row) use (&$line, &$datas) {
            $line++;
            $data = $this->processRow($row, $line);
            if ($data) {
                $datas[] = $data;
            } else {
                $this->errors[] = [
                    'line' => $line,
                    'errors' => 'INVALID',
                ];
            }
        });

        if (empty($datas)) {
            $this->errors[] = ['file' => 'empty'];
            return $this->errors;
        }
        $mergeDatas = [];
        foreach ($datas as $data) {
            $mergeDatas[$data['order_code']]['line']                  = $data['line'];
            $mergeDatas[$data['order_code']]['order_code']            = $data['order_code'];
            $mergeDatas[$data['order_code']]['shipping_partner_code'] = $data['shipping_partner_code'];
            $mergeDatas[$data['order_code']]['receiver_name']         = $data['receiver_name'];
            $mergeDatas[$data['order_code']]['receiver_phone']        = $data['receiver_phone'];
            $mergeDatas[$data['order_code']]['receiver_postal_code']  = $data['receiver_postal_code'];
            $mergeDatas[$data['order_code']]['receiver_country']      = $data['receiver_country'];
            $mergeDatas[$data['order_code']]['receiver_province']     = $data['receiver_province'];
            $mergeDatas[$data['order_code']]['receiver_district']     = $data['receiver_district'];
            $mergeDatas[$data['order_code']]['receiver_ward']         = $data['receiver_ward'];
            $mergeDatas[$data['order_code']]['receiver_address']      = $data['receiver_address'];
            $mergeDatas[$data['order_code']]['skus'][]                = [
                'sku_code' => $data['sku_code'],
                'sku_quantity' => $data['sku_quantity'],
                'sku_price' => $data['sku_price'],
                'sku_discount' => $data['sku_discount'],
            ];
            $mergeDatas[$data['order_code']]['cod']                   = $data['cod'];
        }

        foreach ($mergeDatas as $mergeData) {
            $validator = new ImportedforUpdateValidator($this->user, $mergeData);
            if ($validator->fails()) {
                $this->errors[] = [
                    'line' => $mergeData['line'],
                    'errors' => TransformerService::transform($validator),
                    'order_code' => Arr::get($mergeData, 'order_code', null),
                ];
                continue;
            }

            $inputOrderByFile                   = new InputOrderByFile($mergeData);
            $inputOrderByFile->shippingPartner  = $validator->getShippingPartner();
            $inputOrderByFile->receiverCountry  = $validator->getLocationCountry();
            $inputOrderByFile->receiverProvince = $validator->getLocationProvince();
            $inputOrderByFile->receiverDistrict = $validator->getLocationDistrict();
            $inputOrderByFile->receiverWard     = $validator->getLocationWard();

            $order = $validator->getOrder();


            (new UpdateOrderByFile($order, $inputOrderByFile, $this->user))->handle();
        }

        return $this->errors;
    }

    /**
     * @param array $row
     * @param int $line
     * @return array|void
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function ($value) {
            return $value != '';
        });

        if (!count($rowData)) {
            return;
        }

        $data = $this->makeRow($row);
        if (!empty($data)) {
            $data['line'] = $line;
        }

        return $data;
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
            'shipping_partner_code',
            'shop_name',
            'campaign',
            'created_at_origin',
            'seller_code',
            'seller_name',
            'receiver_name',
            'receiver_phone',
            'receiver_country',
            'receiver_postal_code',
            'receiver_province',
            'receiver_district',
            'receiver_ward',
            'receiver_address',
            'sku_code',
            'sku_name',
            'warehouse_code_and_name',
            'warehouse_area_code',
            'sku_quantity',
            'unit',
            'sku_price',
            'sku_discount',
            'amount',
            'amount_total',
            'discount',
            'cod',
            'status',
            'finance_status',
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
