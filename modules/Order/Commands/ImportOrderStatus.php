<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Order\Validators\ImportOrderStatusValidator;
use Modules\Order\Validators\ReImportOrderValidator;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportOrderStatus
{
    CONST SHEET_ORDER_STATUS = 'order_status'; // trạng thái đơn
    CONST SHEET_RE_IMPORT = 're_import'; //tái nhập

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

    /**
     * @var array
     */
    protected $insertedOrderKeys = [];

    /**
     * Các dữ liệu của sheet cập nhật trạng thái đơn
     * @var array
     */
    protected $orderStatusData = [];

    /**
     * Các dữ liệu tái nhập
     * @var array
     */
    protected $reImports = [];

    /**
     * @var array
     */
    protected $merchantIds = [];

    /**
     * @var int
     */
    protected $lineOrderStatus = 1;

    protected $reImportOrderErrors = [];

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
        $this->user = $user;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->importSheets($this->filePath, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        @unlink($this->filePath);

        $this->changeOrderStatus();

        return $this->errors;
    }

    /**
     * Thay đổi trạng thái đơn
     *
     * @throws WorkflowException
     */
    protected function changeOrderStatus()
    {
        $orderCanceledDelivering = [];
        $orderCanceled = [];
        $orderErrors   = [];
        $orders        = [];
        foreach ($this->orderStatusData as $key => $orderData) {
            /** @var Order $order */
            $order  = $orderData['order'];
            $status = trim($orderData['status']);
            $orders[$order->code] = $order;
            if(!$order->canChangeStatus($status)) {
                $this->errors[] = array_merge($orderData, ['errors' => 'invalid_status']);
                $orderErrors[$order->code] = $order->code;
                continue;
            }

            if($status == Order::STATUS_CANCELED) {
                if($order->status == Order::STATUS_DELIVERING) {
                    $orderCanceledDelivering[] = $order->code;
                    if(!$this->validateOrderCanceled($order, $orderData)) {
                        $orderErrors[$order->code] = $order->code;
                        continue;
                    }
                } else {
                    $orderCanceled[] = $order->code;
                }
            }
        }

        foreach ($this->reImports as $orderCode => $reImport) {
            if(!in_array($orderCode, $orderCanceledDelivering)) {
                $orderErrors[$orderCode] = $orderCode;
                $errorKey = (in_array($orderCode, $orderCanceled)) ? 'order_not_canceled_delivering' : 'order_not_canceled';
                foreach ($reImport as $reImportSku) {
                    $this->errors[] = array_merge($reImportSku, ['errors' => $errorKey]);
                }
            }
        }

        foreach ($this->orderStatusData as $key => $orderData) {
            /** @var Order $order */
            $order  = $orderData['order'];
            $status = trim($orderData['status']);
            if(isset($orderErrors[$order->code])) {
                continue;
            }
            $order->cancel_note = trim($orderData['cancel_note']);
            $order->changeStatus($status, $this->user);
        }
    }

    /**
     * @param Order $order
     * @param $orderData
     * @return bool
     */
    protected function validateOrderCanceled(Order $order, $orderData)
    {
        if(isset($this->reImportOrderErrors[$order->code])) {
            return false; //nếu có thông báo lỗi ở file tái nhập rồi thì thôi validate file này
        }

        if(empty($this->reImports[$order->code])) {
            $this->errors[] = array_merge($orderData, ['errors' => 'required_re_import']);
            return false;
        }

        $reImportSkus = $this->reImports[$order->code];
        $orderSkus    = $order->orderSkus;
        $reImportOrderSkuQuantity = [];
        $reImportSkuData = [];

        foreach ($reImportSkus as $reImportSku) {
            $sku      = $reImportSku['sku'];
            $quantity = intval($reImportSku['quantity']);
            $reImportSkuData[$sku->id] = $reImportSku;

            if(isset($reImportOrderSkuQuantity[$sku->id])) {
                $reImportOrderSkuQuantity[$sku->id] = $reImportOrderSkuQuantity[$sku->id] + $quantity;
            } else {
                $reImportOrderSkuQuantity[$sku->id] = $quantity;
            }
        }

        foreach ($orderSkus as $orderSku) {
            if(!isset($reImportSkuData[$orderSku->sku_id])) {
                $this->errors[] = array_merge($orderData, ['sku_code' => $orderSku->sku->code], ['errors' => 'sku_not_in_re_import']);
                return false;
            }

            if(
                empty($reImportOrderSkuQuantity[$orderSku->sku_id]) ||
                $reImportOrderSkuQuantity[$orderSku->sku_id] != $orderSku->quantity
            ) {
                $this->errors[] = array_merge($reImportSkuData[$orderSku->sku_id], ['errors' => 'invalid_quantity_re_import']);
                return false;
            }
        }

        /**
         * Thực hiện tái nhập cho các sku
         */
        foreach ($reImportSkus as $reImportSku) {
            $stock = Service::stock()->make($reImportSku['sku'], $reImportSku['warehouseArea']);
            $stock->do(Stock::ACTION_IMPORT, $reImportSku['quantity'], $this->user)->for($order)->run();
        }

        return true;
    }

    /**
     * @param array $row
     * @param $line
     */
    protected function processRow(array $row, $line)
    {
        if(isset($row['Trạng thái (*)'])) {
            $this->lineOrderStatus = $line;
        }

        $row = array_map(function($value){
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function($value){
            return !empty($value);
        });
        if(!count($rowData)) {
            return;
        }

        $sheet = self::SHEET_RE_IMPORT;
        if (isset($row['Trạng thái (*)'])) {
            $sheet = self::SHEET_ORDER_STATUS;
        }
        if (isset($row['Mã SKU (*)'])) {
            $sheet = self::SHEET_RE_IMPORT;
            $line  = max($line - $this->lineOrderStatus + 1, 1);
        }

        $row = $this->makeRow($row, $sheet);
        if(!$row) {
            $this->errors[] = [
                'sheet' => $sheet,
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }


        $row['line']  = $line;
        $row['sheet'] = $sheet;

        if($sheet == self::SHEET_ORDER_STATUS) {
            $validator = new ImportOrderStatusValidator($this->user, $row, $this->insertedOrderKeys, $this->merchantIds);
            if ($validator->fails()) {
                $this->errors[] = [
                    'sheet'  => $sheet,
                    'line'   => $line,
                    'errors' => TransformerService::transform($validator),
                ];
                return;
            }
            $this->insertedOrderKeys[] = $validator->getOrderKey();
            $row['order'] = $validator->getOrder();
            $this->orderStatusData[] = $row;

        } else {
            $validator = new ReImportOrderValidator($this->user, $row, $this->merchantIds);
            if ($validator->fails()) {
                $orderCode = Arr::get($row, 'order_code', '');
                if(!empty($orderCode)) {
                    $this->reImportOrderErrors[$orderCode] = $orderCode;
                }
                $this->errors[] = [
                    'sheet'  => $sheet,
                    'line'   => $line,
                    'errors' => TransformerService::transform($validator),
                ];
                return;
            }
            $row['order'] = $validator->getOrder();
            $row['warehouse'] = $validator->getWarehouse();
            $row['warehouseArea'] = $validator->getWarehouseArea();
            $row['sku'] = $validator->getSku();

            $this->reImports[trim($row['order_code'])][] = $row;
        }
    }

    /**
     * @param $sheet
     * @return array
     */
    protected function getParams($sheet)
    {
        if($sheet == self::SHEET_ORDER_STATUS) {
            return [
                'order_code',
                'status',
                'cancel_note'
            ];
        }

        return [
            'order_code',
            'sku_code',
            'quantity',
            'warehouse_code',
            'warehouse_area_code',
        ];
    }

    /**
     * @param array $row
     * @param $sheet
     * @return array|bool
     */
    protected function makeRow(array $row, $sheet)
    {
        $params = $this->getParams($sheet);

        if(isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if(count($values) != count($params)) {
            return false;
        }

        return array_combine($params, $values);
    }
}
