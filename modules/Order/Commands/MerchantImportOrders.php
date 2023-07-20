<?php

namespace Modules\Order\Commands;

use Carbon\Carbon;
use Exception;
use Gobiz\Redis\RedisService;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Order\Validators\ImportedOrderValidator;
use Modules\Order\Validators\MerchantImportedOrderValidator;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;

class MerchantImportOrders
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
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $insertedOrderKeys = [];

    /** @var array $warningOrders */
    protected $warningOrders = [];
    /** @var array $validOrders */
    protected $validOrders = [];

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
     * @var array
     */
    protected $orderSkuCombos = [];


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
     * MerchantImportOrders constructor.
     * @param $filePath
     * @param User $user
     * @param Warehouse|null $warehouse
     */
    public function __construct($filePath, User $user, $warehouse = null)
    {
        $this->filePath  = $filePath;
        $this->user      = $user;
        $this->warehouse = $warehouse;
        $this->merchant  = $user->merchant;
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
            $orderKey       = $this->getOrderKey($order);
            $orderSkus      = isset($this->orderSkus[$orderKey]) ? $this->orderSkus[$orderKey]: [];
            $orderSkuCombos = isset($this->orderSkuCombos[$orderKey]) ? $this->orderSkuCombos[$orderKey] : [];

            /**
             * Kiểm tra lại xem có sku của đơn ko
             */
            // if (empty($orderSkus)) {
            //     $this->errors[] = [
            //         'line' => $order['line'],
            //         'order_code' => $order['code'],
            //         'sku_code' => $order['sku_code'],
            //         'merchant_code' => $this->merchant->code,
            //         'errors' => ['sku_code' => [ImportedOrderValidator::ERROR_REQUIRED => []]],
            //     ];
            //     continue;
            // }
            /**
             * Kiểm tra xem sku của đơn còn tồn ko
             */
            $orderAmount = 0;
            $totalAmount = 0;
            $lineRow     = $order['line'] - 1;
            foreach ($orderSkus as $orderSku) {
                $lineRow++;
                $orderAmount += $orderSku['total_amount'];
//                $totalAmount += $orderSku['total_amount'];
                $sku      = Sku::find($orderSku['sku_id']);
                $quantity = $orderSku['quantity'];
                /** @var Stock|null $stock */
                $stock = $sku->stocks->where('warehouse_id', $this->warehouse->id)->sortByDesc('quantity')->first();
                if (empty($stock) || $stock->quantity < $quantity) {
                    $this->warningOrders[] = [
                        'line' => $lineRow,
                        'order_code' => $order['code'],
                        'sku_code' => $sku->code,
                        'warehouse_area_code' => $stock ? $stock->warehouseArea->code : '',
                        'warnings' => [
                            [
                                'message' => 'lack_of_stock',
                                'quantity' => $quantity,
                                'quantity_stock' => $stock ? $stock->quantity : 0
                            ]
                        ],
                    ];
                    continue;
                }
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            foreach ($orderSkuCombos as $orderSkuCombo) {
                $orderAmount += $orderSkuCombo['total_amount'];
                $totalAmount += $orderSkuCombo['total_amount'];
            }

            $discountAmount = 0;
            $totalAmount    = $totalAmount - $discountAmount;

            // merge order và total_amount lúc nào cũng lấy từ file import
            $orders[] = array_merge($order, [
                'discount_amount' => $discountAmount,
                'orderSkus' => $orderSkus,
                'orderSkuCombos' => $orderSkuCombos,
                'creator' => $this->user,
                'orderAmount' => $orderAmount,
                'totalAmount' => $order['total_amount']
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

        $inputBashWarningOrders = $inputBashValidOrders = [];
        foreach ($orders as $order) {
            if (!in_array($order['code'], $orderError)) {
                if (array_search($order['code'], array_column($this->warningOrders, 'order_code')) !== false) {
                    $inputBashWarningOrders[$order['code']] = $this->getInputBash($order);
                } else {
                    $inputBashValidOrders[$order['code']] = $this->getInputBash($order);
                    $this->validOrders[]                  = $order['code'];
                }
            }
        }
        $redis = RedisService::redis()->connection();
        if ($inputBashWarningOrders) {
            $redis->set($this->user->tenant->code . '_' . $this->user->username . '_bash_warning_orders', json_encode(array_values($inputBashWarningOrders)));
        } else {
            $redis->del($this->user->tenant->code . '_' . $this->user->username . '_bash_warning_orders');
        }
        if ($inputBashValidOrders) {
            $redis->set($this->user->tenant->code . '_' . $this->user->username . '_bash_valid_orders', json_encode(array_values($inputBashValidOrders)));
        } else {
            $redis->del($this->user->tenant->code . '_' . $this->user->username . '_bash_valid_orders');
        }
        return [
            'valid_orders' => $this->validOrders,
            'warning_orders' => $this->warningOrders,
            'errors' => $this->errors
        ];
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
        $code = trim($row['code']);
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
                    'merchant_code' => $this->merchant->code,
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

        $validator = new MerchantImportedOrderValidator($this->user, $row, $this->insertedOrderKeys);
        if ($validator->fails()) {
            $errors[] = [
                'line' => $line,
                'order_code' => $lastCode,
                'errors' => TransformerService::transform($validator),
            ];
        }
        if ($row['total_amount'] === 0){
            foreach (['quantity', 'price', 'total_amount'] as $key) {
                if ($key == 'total_amount' && $row[$key] === '') {
                    $row[$key] = null;
                } else {
                    $row[$key] = floatval($row[$key]);
                }
            }
        }else{
            foreach (['quantity', 'price', 'total_amount'] as $key) {
                if ($key == 'total_amount' && $row[$key] == '') {
                    $row[$key] = null;
                } else {
                    $row[$key] = floatval($row[$key]);
                }
            }
        }
        if (!empty($lastCode)) {
            if (isset($this->insertedSkuKeys[$lastCode][$row['sku_code']])) {
                $errors[] = [
                    'line' => $line,
                    'order_code' => $row['code'],
                    'merchant_code' => $this->merchant->code,
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
                    'merchant' => $this->merchant,
                    'receiverProvince' => $validator->getReceiverProvince(),
                    'receiverDistrict' => $validator->getReceiverDistrict(),
                    'receiverWard' => $validator->getReceiverWard(),
                    'shippingPartner' => $validator->getShippingPartner(),
                    'extraServices' => [],
                ]);
            }

            $sku                                          = $validator->getSku();
            $skuCombo                                     = $validator->getSkuCombo();
            $orderSku                                     = ($sku instanceof Sku) ? $this->getOrderSku($row, $sku) : [];
            $orderSkuCombo                                = ($skuCombo instanceof SkuCombo) ? $this->getOrderSkuCombo($row, $skuCombo) : [];

            if ($orderSku) {
                $this->orderSkus[$lastCode][$row['sku_code']] = $orderSku;
                return;
            }

            if ($orderSkuCombo) {
                $this->orderSkuCombos[$lastCode][$row['sku_code']] = $orderSkuCombo;
            }
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
        $discount_amount = 0;
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
     * @param $row
     * @param SkuCombo $skuCombo
     * @return array
     */
    protected function getOrderSkuCombo($row, SkuCombo $skuCombo)
    {
        $price           = floatval($row['price']);
        $quantity        = intval($row['quantity']);
        $discount_amount = 0;
        $tax             = 0;

        $orderAmount = $price * $quantity;
        $totalAmount = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discount_amount;

        return [
            'sku_combo_id'    => $skuCombo->id,
            'quantity'        => $quantity,
            'tax'             => $tax,
            'price'           => $price,
            'discount_amount' => $discount_amount,
            'order_amount'    => $orderAmount,
            'total_amount'    => $totalAmount
        ];
    }

    /**
     * @param array $row
     * @return array|boolean
     */
    protected function makeRow(array $row)
    {
        $params = [
            'code',
            'freight_bill',
            'name_store',
            'shipping_partner_code',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_postal_code',
            'province',
            'district',
            'ward',
            'sku_code',
            'quantity',
            'price',
            'description',
            'payment_type',
            'payment_method',
            'payment_time',
            'payment_amount',
            'bank_name',
            'bank_account',
            'standard_code',
            'total_amount',
            'payment_note'
        ];

        $rowData = [];
        if ($row['Tổng thanh toán'] === 0){
            foreach ($row as $key => $value) {
                if (!empty($key) && $key != 'Tổng thanh toán' && $key != 'Ghi chú thanh toán') {
                    $rowData[$key] = $value ?: null;
                }
            }
            $rowData = array_merge($rowData,['Tổng thanh toán' => $row['Tổng thanh toán'], 'Ghi chú thanh toán' => $row['Ghi chú thanh toán']]);
        }else{
            foreach ($row as $key => $value) {
                if (!empty($key)) {
                    $rowData[$key] = $value ?: null;
                }
            }
        }

        $values = array_values($rowData);
        if (count($values) != count($params)) {
            return false;
        }

        $rowData = array_combine($params, $values);
        return $rowData;
    }

    /**
     * @param $order
     * @return array
     */
    private function getInputBash($order)
    {
        /** @var Location|null $receiverCountry */
        $receiverCountry  = $this->merchant->getCountry();
        $receiverProvince = Arr::get($order, 'receiverProvince');
        $receiverDistrict = Arr::get($order, 'receiverDistrict');
        $receiverWard     = Arr::get($order, 'receiverWard');
        $shippingPartner  = Arr::get($order, 'shippingPartner');
        $orderSkus        = Arr::get($order, 'orderSkus');
        $totalAmount      = Arr::get($order, 'totalAmount');
        $paymentType      = Arr::get($order, 'payment_type');

        $orderSkuCombos   = Arr::get($order, 'orderSkuCombos');

        $paymentTime = Arr::get($order, 'payment_time');

        return [
            'merchant_id' => $this->merchant->id,
            'creator_id' => $this->user->id,
            'code' => Arr::get($order, 'code'),
            'receiver_name' => Arr::get($order, 'receiver_name'),
            'receiver_phone' => Arr::get($order, 'receiver_phone'),
            'receiver_address' => Arr::get($order, 'receiver_address'),
            'receiver_note' => Arr::get($order, 'receiver_note'),
            'receiver_country_id' => $receiverCountry ? $receiverCountry->id : 0,
            'receiver_province_id' => $receiverProvince ? $receiverProvince->id : 0,
            'receiver_district_id' => $receiverDistrict ? $receiverDistrict->id : 0,
            'receiver_ward_id' => $receiverWard ? $receiverWard->id : 0,
            'freight_bill' => Arr::get($order, 'freight_bill'),
            'description' => Arr::get($order, 'description'),
            'campaign' => Arr::get($order, 'campaign'),
            'currency_id' => $receiverCountry ? $receiverCountry->currency_id : 0,
            'shipping_partner_id' => $shippingPartner ? $shippingPartner->id : 0,
            'warehouse_id' => $this->warehouse ? $this->warehouse->id : 0,
            'order_skus' => $orderSkus,
            'order_sku_combos' => $orderSkuCombos,
            // 'cod' => Arr::get($order, 'cod'),
            'receiver_postal_code' => Arr::get($order, 'receiver_postal_code'),
            'name_store' => Arr::get($order, 'name_store'),
            'payment_method' => Arr::get($order, 'payment_method'),
            'payment_time' => $paymentTime ? Carbon::parse(Arr::get($order,'payment_time'))->format('Y-m-d H:i:s') : null,
//            Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->date_begin)->format('Y.m.d')
            'payment_amount' => Arr::get($order, 'payment_amount'),
            'bank_name' => Arr::get($order, 'bank_name'),
            'bank_account' => Arr::get($order, 'bank_account'),
            'standard_code' => Arr::get($order, 'standard_code'),
            'payment_note' => Arr::get($order, 'payment_note'),
            'payment_type' => $paymentType,
            'total_amount' => $totalAmount
        ];
    }
}
