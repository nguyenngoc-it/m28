<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Validators\ImportedOrderValidator;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportOrders
{
    /**
     * @var Merchant
     */
    protected $merchant;

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
    protected $errors = [];

    /**
     * @var array
     */
    protected $insertedOrderKeys = [];

    /**
     * @var array
     */
    protected $insertedSkuKeys = [];

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * @var array
     */
    protected $orderSkus = [];


    /**
     * Tổng tiền khách phải trả
     * @var int
     */
    protected $totalAmount = [];

    /**
     * Tổng số tiền hàng
     * @var int
     */
    protected $orderAmount = [];

    /**
     * ImportOrders constructor
     *
     * @param string $filePath
     * @param User $user
     */
    public function __construct($filePath, User $user)
    {
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

        $orders = [];
        foreach ($this->orders as $order) {
            $orderKey  = $this->getOrderKey($order);
            $orderSkus = isset($this->orderSkus[$orderKey]) ? $this->orderSkus[$orderKey] : [];
            if (empty($orderSkus)) {
                $this->errors[] = [
                    'line' => $order['line'],
                    'order_code' => $order['code'],
                    'sku_code' => $order['code'],
                    'merchant_code' => $order['merchant_code'],
                    'errors' => ['sku_code' => [ImportedOrderValidator::ERROR_REQUIRED => []]],
                ];
                continue;
            }

            $orderAmount = 0;
            $totalAmount = 0;
            foreach ($orderSkus as $orderSku) {
                $orderAmount += $orderSku['total_amount'];
                $totalAmount += $orderSku['total_amount'];
            }

            $discountAmount = isset($order['discount_amount_order']) ? floatval($order['discount_amount_order']) : 0;
            $totalAmount    = $totalAmount - $discountAmount;

            if (isset($order['payment_amount']) && floatval($order['payment_amount']) > $totalAmount) {
                $this->errors[] = [
                    'line' => $order['line'],
                    'order_code' => $order['code'],
                    'order_amount' => $orderAmount,
                    'merchant_code' => $order['merchant_code'],
                    'payment_amount' => $order['payment_amount'],
                    'total_amount' => $totalAmount,
                    'errors' => ['payment_amount' => [ImportedOrderValidator::ERROR_GREATER => []]],
                ];
                continue;
            }

            $orders[] = array_merge($order, [
                'discount_amount' => $discountAmount,
                'orderSkus' => $orderSkus,
                'creator' => $this->user,
                'orderAmount' => $orderAmount,
                'totalAmount' => $totalAmount,
            ]);
        }

        $orderError = [];
        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                if (!empty($error['order_code'])) {
                    $orderError[] = $error['order_code'];
                }
            }
        }

        foreach ($orders as $order) {
            if (!in_array($order['code'], $orderError)) {
                (new CreateOrder($order))->handle();
            }
        }

        return $this->errors;
    }

    /**
     * @param array $row
     * @param $line
     * @return array|void
     */
    protected function processRowValidator(array $row, $line)
    {
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
                'errors' => ImportedOrderValidator::ERROR_INVALID,
            ];
            return;
        }

        return $row;
    }

    /**
     * @param $row
     * @return string
     */
    protected function getOrderKey($row)
    {
        $code = $row['code'] . '_' . $row['merchant_code'];
        return $code;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $row);

        $row = $this->processRowValidator($row, $line);
        if (empty($row)) {
            return;
        }

        $errors = [];

        if (!empty($row['code'])) {
            if (!empty($this->insertedOrderKeys) && in_array($this->getOrderKey($row), $this->insertedOrderKeys)) {
                $errors[] = [
                    'line' => $line,
                    'order_code' => $row['code'],
                    'merchant_code' => $row['merchant_code'],
                    'errors' => ['order_code' => [ImportedOrderValidator::ERROR_DUPLICATED => []]],
                ];
            } else {
                $this->insertedOrderKeys[] = $this->getOrderKey($row);
            }
        }

        $lastCode = '';
        if (!empty($this->insertedOrderKeys)) {
            $lastCode = Arr::last($this->insertedOrderKeys);
        }


        $validator = new ImportedOrderValidator($this->user, $row, $this->insertedOrderKeys);
        if ($validator->fails()) {
            $errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
        }

        foreach (['quantity', 'price', 'discount_amount_sku', 'discount_amount_order', 'payment_amount'] as $key) {
            $row[$key] = floatval($row[$key]);
        }

        if (!empty($lastCode)) {
            if (isset($this->insertedSkuKeys[$lastCode][$row['sku_code']])) {
                $errors[] = [
                    'line' => $line,
                    'order_code' => $row['code'],
                    'merchant_code' => $row['merchant_code'],
                    'sku_code' => $row['sku_code'],
                    'errors' => ['sku_code' => [ImportedOrderValidator::ERROR_DUPLICATED => []]],
                ];
            } else {
                $this->insertedSkuKeys[$lastCode][$row['sku_code']] = $row['sku_code'];
            }
        }

        if (!empty($errors)) {
            $this->errors = array_merge($this->errors, $errors);
            return;
        }

        if (!empty($lastCode)) {
            if (!empty($row['code'])) {
                $this->orders[$lastCode] = array_merge($row, [
                    'line' => $line,
                    'merchant' => $validator->getMerchant(),
                    'receiverProvince' => $validator->getReceiverProvince(),
                    'receiverDistrict' => $validator->getReceiverDistrict(),
                    'receiverWard' => $validator->getReceiverWard(),
                    'shippingPartner' => $validator->getShippingPartner(),
                    'extraServices' => [],
                ]);
            }

            $sku                                          = $validator->getSku();
            $orderSku                                     = ($sku instanceof Sku) ? $this->getOrderSku($row, $sku) : [];
            $this->orderSkus[$lastCode][$row['sku_code']] = $orderSku;
        }
    }

    /**
     * @param $row
     * @param Sku $sku
     * @return array
     */
    protected function getOrderSku($row, Sku $sku)
    {
        $price           = floatval($row['price']);
        $quantity        = intval($row['quantity']);
        $discount_amount = floatval($row['discount_amount_sku']);
        $tax             = 0;

        $orderAmount = $price * $quantity;
        $totalAmount = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discount_amount;

        return [
            'sku_id' => $sku->id,
            'quantity' => $quantity,
            'tax' => $tax,
            'price' => $price,
            'discount_amount' => $discount_amount,
            'order_amount' => $orderAmount,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'stt',
            'merchant_code',
            'code',
            'created_at_origin',
            'campaign',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_postal_code',
            'province',
            'district',
            'ward',
            'sku_code',
            'sku_name',
            'quantity',
            'price',
            'discount_amount_sku',
            'discount_amount_order',
            'intended_delivery_at',
            'shipping_partner_code',
            'description',
            'payment_type',
            'payment_method',
            'payment_amount',
            'payment_time',
            'bank_name',
            'bank_account',
            'payment_note'
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return false;
        }

        $row = array_combine($params, $values);
        foreach (['discount_amount_sku', 'discount_amount_order', 'payment_amount'] as $p) {
            if ($row[$p] === null) {
                $row[$p] = "";
            }
        }

        return $row;
    }
}
