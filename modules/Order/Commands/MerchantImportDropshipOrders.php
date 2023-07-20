<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Order\Validators\ImportedOrderValidator;
use Modules\Order\Validators\MerchantImportedOrderValidator;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class MerchantImportDropshipOrders
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
     * @var array
     */
    protected $insertedFreightBill = [];

    /**
     * Các phí dịch vụ đi theo bảng giá sản phẩm
     * @var array
     */
    protected $orderServicePrices = [];

    protected $orderProductPriceCombo = [];
    protected $productPrices = [];
    protected $products = [];
    protected $orderProductPriceDetails = [];

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
        $this->merchant = $user->merchant;
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
                if(empty($this->errors)) {
                    $this->errors[] = [
                        'line' => $order['line'],
                        'order_code' => $order['code'],
                        'sku_code' => $order['code'],
                        'merchant_code' => $this->merchant->code,
                        'errors' => ['sku_code' => [ImportedOrderValidator::ERROR_REQUIRED => []]],
                    ];
                }

                continue;
            }

            if(isset($this->orderProductPriceCombo[$orderKey])) {
                $productPriceCombo = $this->orderProductPriceCombo[$orderKey];
                $errors = [];
                $productPriceDetails = [];
                foreach ($productPriceCombo as $productId => $combo) {
                    $product = $this->getProduct($productId);
                    $productPrice = $this->getProductPriceUsing($product);
                    $productPriceDetail = $productPrice->priceDetailCombo($combo);
                    if(!$productPriceDetail instanceof ProductPriceDetail) {
                        $errors[] = [
                            'line' => $order['line'],
                            'order_code' => $order['code'],
                            'sku_code' => $order['code'],
                            'merchant_code' => $this->merchant->code,
                            'errors' => ['product_price_combo' => [ImportedOrderValidator::ERROR_INVALID => []]],
                        ];
                        continue;
                    }
                    $productPriceDetails[] = $productPriceDetail;
                }

                if(!empty($errors)) {
                    $this->errors = array_merge($this->errors, $errors);
                    continue;
                }

                foreach ($productPriceDetails as $priceDetail) {
                    $this->makeOrderServicePrices($orderKey, $priceDetail); // cộng dồn phí theo combo sản phẩm
                }
            }


            $orderAmount = 0;
            $totalAmount = 0;
            foreach ($orderSkus as $orderSku) {
                $orderAmount += $orderSku['total_amount'];
                $totalAmount += $orderSku['total_amount'];
            }

            $discountAmount = 0;
            $totalAmount    = $totalAmount - $discountAmount;

            if(!empty($this->orderServicePrices[$orderKey])) {
                $order = array_merge($order, $this->orderServicePrices[$orderKey]);
            }

            $orders[] = array_merge($order, [
                'dropship' => true,
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
                $newOrder = (new CreateOrder($order))->handle();
                $this->createOrderProductPriceDetail($newOrder);

                if (Service::order()->autoInspection($newOrder->refresh(), $this->user)) {
                    /**
                     * Đối với đơn dropship sẽ chuyển sang chờ xác nhận
                     */
                    if ($newOrder->canChangeStatus(Order::STATUS_WAITING_CONFIRM)) {
                        $newOrder->changeStatus(Order::STATUS_WAITING_CONFIRM, $this->user);
                    }
                }
            }
        }

        return $this->errors;
    }

    /**
     * lưu snapshot lại bảng phí cho đơn hàng khi tạo đơn
     * @param Order $order
     */
    protected function createOrderProductPriceDetail(Order $order)
    {
        if(!empty($this->orderProductPriceDetails[$order->code])) {
            $orderProductPriceDetails = $this->orderProductPriceDetails[$order->code];
            /** @var ProductPriceDetail $productPriceDetail */
            foreach ($orderProductPriceDetails as $productPriceDetail) {
                $order->orderProductPriceDetails()->create([
                    'sku_id' => $productPriceDetail->sku_id,
                    'product_price_detail_id' => $productPriceDetail->id,
                    'product_price_id' => $productPriceDetail->product_price_id,
                    'cost_price' => $productPriceDetail->cost_price,
                    'service_packing_price' => $productPriceDetail->service_packing_price,
                    'service_shipping_price' => $productPriceDetail->service_shipping_price,
                    'total_price' => $productPriceDetail->total_price,
                    'combo' => $productPriceDetail->combo
                ]);
            }
        }
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
        $code = $row['code'];
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

        $freightBill = $row['freight_bill'];
        $shippingPartnerCode = $row['shipping_partner_code'];

        if(
            !empty($freightBill) &&
            !empty($shippingPartnerCode)
        ) {
            if(
                isset($this->insertedFreightBill[$shippingPartnerCode][$freightBill])
            ) {
                $errors[] = [
                    'line' => $line,
                    'shipping_partner_code' => $shippingPartnerCode,
                    'freight_bill' => $freightBill,
                    'errors' => ['freight_bill' => [ImportedOrderValidator::ERROR_DUPLICATED => []]],
                ];
            }
            $this->insertedFreightBill[$shippingPartnerCode][$freightBill] = true;
        }

        $lastCode = '';
        if (!empty($this->insertedOrderKeys)) {
            $lastCode = Arr::last($this->insertedOrderKeys);
        }
        foreach (['quantity', 'price', 'cod'] as $key) {
            if ($key == 'cod' && $row[$key] == '') {
                $row[$key] = null;
            } else {
                $row[$key] = floatval($row[$key]);
            }

        }
        $validator = new MerchantImportedOrderValidator($this->user, $row, $this->insertedOrderKeys, true);
        if ($validator->fails()) {
            $errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
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

            $sku  = $validator->getSku();
            $this->makeOrderSkus($line, $lastCode, $row, $sku);
        }
    }

    /**
     * @param Product $product
     * @return ProductPrice|null
     */
    protected function getProductPriceUsing(Product $product)
    {
        if(isset($this->productPrices[$product->id])) {
            return $this->productPrices[$product->id];
        }

        return $product->productPriceUsing();
    }

    /**
     * @param integer $productId
     * @return mixed|Product|null
     */
    protected function getProduct($productId)
    {
        if(isset($this->products[$productId])) {
            return $this->products[$productId];
        }

        return Product::find($productId);
    }

    /**
     * @param $orderCode
     * @param ProductPriceDetail $productPriceDetail
     * @param int $quantity
     */
    protected function makeOrderServicePrices($orderCode, ProductPriceDetail $productPriceDetail, $quantity = 0)
    {
        if(!isset($this->orderServicePrices[$orderCode]['cost_price'])) {
            $this->orderServicePrices[$orderCode] = [
                'cost_price' => 0,
                'service_amount' => 0,
                'shipping_amount' => 0,
            ];
        }

        $this->orderServicePrices[$orderCode]['cost_price'] += ($quantity) ? $productPriceDetail->cost_price *$quantity  : $productPriceDetail->cost_price;
        $this->orderServicePrices[$orderCode]['service_amount'] += ($quantity) ? $productPriceDetail->service_packing_price*$quantity  : $productPriceDetail->service_packing_price;
        $this->orderServicePrices[$orderCode]['shipping_amount'] += ($quantity) ? $productPriceDetail->service_shipping_price*$quantity  : $productPriceDetail->service_shipping_price;

        $this->orderProductPriceDetails[$orderCode][] = $productPriceDetail;
    }


    /**
     * @param $line
     * @param $orderCode
     * @param $row
     * @param Sku $sku
     */
    protected function makeOrderSkus($line, $orderCode, $row, Sku $sku)
    {
        $errors   = [];
        $product  = $this->getProduct($sku->product_id);
        if(!$product->dropship) {
            $errors[] = [
                'line' => $line,
                'errors' => [
                    'warning_not_dropship' => $sku->code,
                ],
            ];

            $this->errors = array_merge($this->errors, $errors);
            return;
        }

        $productPrice = $this->getProductPriceUsing($product);
        if(!$productPrice instanceof ProductPrice) {
            $errors[] = [
                'line' => $line,
                'errors' => [
                    'warning_not_product_price' => $sku->code,
                ],
            ];
            $this->errors = array_merge($this->errors, $errors);
            return;
        }



        $quantity = intval($row['quantity']);
        if($productPrice->type == ProductPrice::TYPE_SKU) {
            $productPriceDetail = $productPrice->priceDetails()->where('sku_id', $sku->id)->first();
            if(!$productPriceDetail instanceof ProductPriceDetail) {
                $errors[] = [
                    'line' => $line,
                    'errors' => [
                        'warning_not_found_product_price_detail' => $sku->code,
                    ],
                ];
                $this->errors = array_merge($this->errors, $errors);
                return;
            }

            $this->makeOrderServicePrices($orderCode, $productPriceDetail, $quantity); // cộng dồng luôn phí theo bảng giá sku
        } else {
            if(!isset( $this->orderProductPriceCombo[$orderCode][$sku->product_id])) {
                $this->orderProductPriceCombo[$orderCode][$sku->product_id] = 0;
            }

            $this->orderProductPriceCombo[$orderCode][$sku->product_id] += $quantity; //cộng dồn số lượng combo của sản phẩm
        }

        $price           = floatval($row['price']);
        $discount_amount = 0;
        $tax             = 0;

        $orderAmount = $price * $quantity;
        $totalAmount = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discount_amount;

        $orderSku = [
            'sku_id' => $sku->id,
            'quantity' => $quantity,
            'tax' => $tax,
            'price' => $price,
            'discount_amount' => $discount_amount,
            'order_amount' => $orderAmount,
            'total_amount' => $totalAmount,
        ];

        $this->orderSkus[$orderCode][$row['sku_code']] = $orderSku;
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
            'shipping_partner_code',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'province',
            'district',
            'ward',
            'sku_code',
            'quantity',
            'price',
            'description',
            'payment_type',
            'cod',
        ];

        $rowData = [];
        foreach ($row as $key => $value) {
            if(!empty($key)) {
                $rowData[$key] = $value;
            }
        }

        $values = array_values($rowData);
        if (count($values) != count($params)) {
            return false;
        }

        $rowData = array_combine($params, $values);

        return $rowData;
    }
}
