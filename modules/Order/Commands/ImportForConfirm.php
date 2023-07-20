<?php

namespace Modules\Order\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Order\Validators\ImportedForConfirmValidator;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportForConfirm
{
    /**
     * @var User
     */
    protected $user;
    /** @var UploadedFile */
    protected $file;
    /** @var array $errors */
    protected $errors = [];

    /**
     * ImportForConfirm constructor.
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
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws WorkflowException
     */
    public function handle()
    {
        $line  = 1;
        $datas = [];
        (new FastExcel())->import($this->file, function ($row) use (&$line, &$datas) {
            $line++;
            $datas[] = $this->processRow($row, $line);
        });

        if (empty($datas)) {
            $this->errors[] = ['file' => 'empty'];
            return $this->errors;
        }
        $mergeDatas = [];
        foreach ($datas as $data) {
            $mergeDatas[$data['order_code']]['line']       = $data['line'];
            $mergeDatas[$data['order_code']]['order_code'] = $data['order_code'];
        }

        foreach ($mergeDatas as $mergeData) {
            $validator = new ImportedforConfirmValidator($this->user, $mergeData);
            if ($validator->fails()) {
                $this->errors[] = [
                    'line' => $mergeData['line'],
                    'errors' => TransformerService::transform($validator),
                    'order_code' => Arr::get($mergeData, 'order_code', null),
                ];
                continue;
            }
            /**
             * Tự động chọn kho xuất cho đơn nếu đơn chưa chọn kho xuất
             */
            $order = $validator->getOrder();
            Service::order()->autoInspection($order, $this->user);
            /**
             * Chuyển trạng thái đơn sang WAITING_PROCESSING
             */
            if (($order->status == Order::STATUS_WAITING_CONFIRM)
                && $validator->getOrder()->canChangeStatus(Order::STATUS_WAITING_PROCESSING)) {
                $validator->getOrder()->changeStatus(Order::STATUS_WAITING_PROCESSING, $this->user);
            }
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

        return $this->makeRow($row, $line);
    }

    /**
     * @param array $row
     * @param $line
     * @return array
     */
    protected function makeRow(array $row, $line)
    {
        return [
            'order_code' => array_values($row)[0],
            'line' => $line,
        ];
    }
}
