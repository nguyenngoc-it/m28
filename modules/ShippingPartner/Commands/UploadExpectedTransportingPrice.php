<?php /** @noinspection SpellCheckingInspection */

namespace Modules\ShippingPartner\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\ShippingPartner\Jobs\UpdateExpectedTransportingPriceJob;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransporting;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;
use Modules\ShippingPartner\Services\ShippingPartnerEvent;
use Modules\ShippingPartner\Validators\UploadExpectedTransportingPriceValidator;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class UploadExpectedTransportingPrice
{
    /**
     * @var User
     */
    protected $user;
    /** @var UploadedFile */
    protected $file;
    /**
     * @var array
     */
    protected $inputs;
    /** @var ExpectedTransporting $expectedTransportingPrice */
    protected $expectedTransportingPrice;
    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;
    /** @var array $errors */
    protected $errors = [];


    /**
     * @throws ExpectedTransportingPriceException
     */
    public function __construct(array $inputs, User $user)
    {
        $this->user = $user;
        $this->file = Arr::get($inputs, 'file');
        unset($inputs['file']);
        $this->inputs                    = $inputs;
        $this->shippingPartner           = ShippingPartner::find(Arr::get($this->inputs, 'shipping_partner_id'));
        $this->expectedTransportingPrice = $this->shippingPartner->expectedTransporting();
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
            }
        });

        if (empty($datas)) {
            $this->errors[] = ['file' => 'empty'];
            return $this->errors;
        }
        $this->shippingPartner->logActivity(ShippingPartnerEvent::UPLOAD_EXPECTED_TRANSPORTING_PRICE, $this->user,
            ['warehouse_id' => Arr::get($this->inputs, 'warehouse_id')]);
        /**
         * Nếu nhiều hơn 1000 bản ghi thì cho vào queue
         */
        if (count($datas) > 1000) {
            foreach (array_chunk($datas, 100) as $data) {
                dispatch(new UpdateExpectedTransportingPriceJob($data,
                    Arr::get($this->inputs, 'shipping_partner_id'),
                    Arr::get($this->inputs, 'warehouse_id')));
            }
            $this->errors[] = [
                'warnings' => 'processing_in_bath'
            ];
            return $this->errors;
        }

        foreach ($datas as $data) {
            $validator = new UploadExpectedTransportingPriceValidator(array_merge($this->inputs, $data), $this->user, $this->expectedTransportingPrice);
            if ($validator->fails()) {
                $this->errors[] = [
                    'line' => $data['line'],
                    'errors' => TransformerService::transform($validator),
                ];
                continue;
            }

            $this->expectedTransportingPrice->makeTablePrice(array_merge($this->inputs, $data, ['tenant_id' => $this->shippingPartner->tenant_id]));
        }

        return $this->errors;
    }

    /**
     * @param array $row
     * @param int $line
     * @return array|void
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

        return $this->makeRow($row, $line);
    }

    /**
     * @param array $row
     * @param $line
     * @return array
     */
    protected function makeRow(array $row, $line): array
    {
        $dataByPosition = array_values($row);
        $makeRow        = [];
        $requiredFields = $this->expectedTransportingPrice->requiredFieldTablePrices();
        foreach ($requiredFields as $requiredField) {
            switch ($requiredField) {
                case 'max_weight':
                    $makeRow['max_weight'] = $dataByPosition[0];
                    break;
                case 'price':
                    $makeRow['price'] = $dataByPosition[1];
                    break;
                case 'return_price_ratio':
                    $makeRow['return_price_ratio'] = $dataByPosition[2];
                    break;
                case 'receiver_province':
                    $makeRow['receiver_province'] = $dataByPosition[3];
                    break;
                case 'receiver_province_id':
                    $makeRow['receiver_province_id'] = (int)$dataByPosition[4];
                    break;
            }
        }
        $makeRow['line'] = $line;

        return $makeRow;
    }
}
