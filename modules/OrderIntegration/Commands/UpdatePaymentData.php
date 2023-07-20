<?php

namespace Modules\OrderIntegration\Commands;

use App\Base\CommandBus;
use Exception;
use Gobiz\Transformer\TransformerService;
use Gobiz\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Commands\CreateOrderTransaction;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Validators\UpdatePaymentDataValidator;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class UpdatePaymentData extends CommandBus
{
    /**
     * @var array
     */
    public $input;

    /**
     * @var array
     */
    public $dataCommon;

    /**
     * @var array
     */
    public $errors;

    /**
     * @var User
     */
    public $creator;

    /**
     * ProcessCreateOrder constructor
     *
     * @param array $input
     * @param User $creator
     */
    public function __construct(array $input, User $creator)
    {
        $this->input    = $input;
        $this->creator  = $creator;
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws Exception
     */
    public function handle()
    {
        $file = data_get($this->input, 'file');
        $line = 1;
        (new FastExcel())->import($file, function ($row) use (&$line) {
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
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $row = array_values($row);

        $rowData = array_filter($row, function ($value) {
            return !empty($value);
        });
        if (!count($rowData)) {
            return;
        }

        $dataCommon = $this->makeCommonData($row);

        $validator = new UpdatePaymentDataValidator($dataCommon);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'order_code' => $dataCommon['order_code'],
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        $orders = Order::where('code', $dataCommon['order_code'])
                        ->where('status', '<>', Order::STATUS_CANCELED)
                        ->get();

        if ($orders && count($orders) == 1) {
            foreach ($orders as $order) {
                $dataOrderOriginal = $order->getOriginal();

                $order = $this->updateOrder($order);

                if ($order->wasChanged()) {
                    $changedAtts = $order->getChanges();
                    if (isset($changedAtts['updated_at'])) unset($changedAtts['updated_at']);
        
                    // Transform data logger for locations
                    $dataCompare = Arr::only($dataOrderOriginal, array_keys($changedAtts));
        
                    $this->makeLogOrderAttribute($order, $dataCompare, $changedAtts);
                }   
            }
        } else {
            $this->errors[] = [
                'line'       => $line,
                'order_code' => $dataCommon['order_code'],
                'errors'     => 'Not Exist or Duplicate',
            ];
            return;
        }
    }
    

    /**
     * Make Common Data For Make Order Record
     *
     * @param array $transformData
     * @return array $dataCommon
     */
    protected function makeCommonData(array $transformData)
    {
        $dataCommon = [
            "order_code"      => data_get($transformData, 0),
            "total_amount"    => data_get($transformData, 1),
            "shipping_amount" => data_get($transformData, 2),
            "order_amount"    => data_get($transformData, 3),
            "cod_amount"      => data_get($transformData, 4),
            "paid_amount"     => data_get($transformData, 5),
            "debit_amount"    => data_get($transformData, 6),
            "payment_type"    => data_get($transformData, 7),
            "bank_name"       => data_get($transformData, 8),
            "bank_account"    => data_get($transformData, 9),
            "payment_time"    => data_get($transformData, 10),
        ];

        $this->dataCommon = $dataCommon;

        return $dataCommon;
    }

    protected function updateOrder(Order $order)
    {
        $orderUpdated = DB::transaction(function () use ($order){
            // Create Order Transaction
            $this->createOrderTransaction($order);

            // Update Order
            $order->total_amount    = (double) $this->dataCommon['total_amount'];
            $order->shipping_amount = (double) $this->dataCommon['shipping_amount'];
            $order->order_amount    = (double) $this->dataCommon['order_amount'];
            $order->cod             = (double) $this->dataCommon['cod_amount'];

            if ($this->dataCommon['payment_type']) {
                $order->payment_type = $this->dataCommon['payment_type'];
            }

            $order->paid_amount = (double) $this->dataCommon['paid_amount'];
            
            $order->debit_amount = (double) $this->dataCommon['debit_amount'];

            $order->save();

            return $order;
        });

        return $orderUpdated;
    }

    protected function createOrderTransaction(Order $order)
    {
        $inputs = [
            'payment_amount' => $this->dataCommon['paid_amount'],
            'payment_method' => $this->dataCommon['payment_type'],
            'bank_name'      => $this->dataCommon['bank_name'],
            'bank_account'   => $this->dataCommon['bank_account'],
            'payment_time'   => $this->dataCommon['payment_time'],
        ];
        if ($inputs['payment_amount']
        && $inputs['payment_method']
        && $inputs['bank_name']
        && $inputs['bank_account']
        && $inputs['payment_time'])
        {
            return (new CreateOrderTransaction($order, $inputs, $this->creator, true))->handle();
        }
    }

    /**
     * Make Log For Order Attribute Changed
     *
     * @param Order $order
     * @param array $dataBefore Dữ liệu trước khi thay đổi
     * @param array $dataAfter Dữ liệu sau khi thay đổi
     * @return void
     */
    protected function makeLogOrderAttribute(Order $order, array $dataBefore, array $dataAfter)
    {
        foreach ($dataBefore as $key => $value) {
            $dataTransform = $this->transformLocationLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataBefore[$key]);
                $dataBefore[$dataTransform[$key]['new_key']] = $dataTransform[$key]['new_value'];
            }
        }

        foreach ($dataAfter as $key => $value) {
            $dataTransform = $this->transformLocationLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataAfter[$key]);
                $dataAfter[$dataTransform[$key]['new_key']] = $dataTransform[$key]['new_value'];
            }
        }

        (new OrderAttributesChanged($order, $this->creator, $dataBefore, $dataAfter))->queue();
    }

    /**
     * Transform data locations log info
     *
     * @param string $key
     * @param string $value
     * @return array
     */
    protected function transformLocationLogInfo($key, $value)
    {
        $keyReturn    = '';
        $dataReturn   = [];
        switch ($key) {
            case 'receiver_province_id':
                $keyReturn    = 'receiver_province';
                break;

            case 'receiver_district_id':
                $keyReturn    = 'receiver_district';
                break;

            case 'receiver_ward_id':
                $keyReturn    = 'receiver_ward';
                break;

            default:
                $keyReturn    = '';
                $typeLocation = '';
                break;
        }
        // Get Location
        if ($keyReturn) {
            $location = Location::find(intval($value));
            if ($location) {
                $dataReturn[$key] = [
                    'new_key'   => $keyReturn,
                    'new_value' => $location->label,
                ];
            }
        }

        return $dataReturn;
    }
}
