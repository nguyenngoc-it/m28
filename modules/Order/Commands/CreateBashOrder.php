<?php

namespace Modules\Order\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderSkuCombo;
use Modules\Order\Models\OrderSkuComboSku;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuComboSku;
use Modules\Service;

class CreateBashOrder extends CreateBashOrderBase
{

    protected $orderAmount = 0;
    protected $totalAmount = 0;

    /**
     * ImportOrder constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * @return Order
     */
    public function handle()
    {
        $order = DB::transaction(function () {
            $order = $this->makeBaseOrder();
            $this->makeOrderSkuCombos($order);
            $this->makeOrderSkus($order);
            $this->makeOrderPayment($order);
            $this->makeOrderTransaction($order);
            return $order;
        });

        if ($order) {
            (new OrderCreated($order))->queue();
        }

        return $order;
    }

    /**
     * @param Order $order
     */
    protected function makeOrderSkus(Order $order)
    {
        $orderSkus = [];
        // dd($this->orderSkus);
        foreach ($this->orderSkus as $data) {
            $skuId        = $data['sku_id'];
            $fromSkuCombo = data_get($data, 'from_sku_combo', false);
            if (isset($orderSkus[$skuId]) && !$fromSkuCombo) { //merge sku trùng id
                $orderSkus[$skuId]['quantity']        += $data['quantity'];
                $orderSkus[$skuId]['order_amount']    += $data['order_amount'];
                $orderSkus[$skuId]['discount_amount'] += $data['discount_amount'];
                $orderSkus[$skuId]['total_amount']    += $data['total_amount'];
            } else {
                $orderSkus[] = array_merge($data, [
                    'order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                ]);
            }

            if (!$fromSkuCombo) {
                $this->orderAmount += $data['order_amount'];
                $this->totalAmount += $data['total_amount'];
            }
        }

        foreach ($orderSkus as $orderSku) {
            OrderSku::create($orderSku);
        }
    }

    /**
     * @param Order $order
     */
    protected function makeOrderSkuCombos(Order $order)
    {

        if ($this->orderSkuCombos) {
            foreach ($this->orderSkuCombos as $skuComboData) {
                $skuComboId       = data_get($skuComboData, 'sku_combo_id', 0);
                $skuComboQuantity = (int)data_get($skuComboData, 'quantity', 0);
                $skuComboPrice    = (double)data_get($skuComboData, 'price', 0);

                $dataCreate = [
                    'order_id' => $order->id,
                    'sku_combo_id' => $skuComboId,
                    'quantity' => $skuComboQuantity,
                    'price' => $skuComboPrice,
                ];

                OrderSkuCombo::create($dataCreate);

                $this->orderAmount += ($skuComboQuantity * $skuComboPrice);
                $this->totalAmount += ($skuComboQuantity * $skuComboPrice);

                // Lấy thông tin sku của sku combo

                $skuCombo = SkuCombo::find($skuComboId);

                if ($skuCombo) {
                    $skuComboSkus = $skuCombo->skuComboSkus;
                    if ($skuComboSkus) {
                        foreach ($skuComboSkus as $skuComboSku) {
                            $sku           = $skuComboSku->sku;
                            $totalQuantity = (int)$skuComboSku->quantity * $skuComboQuantity;
                            $totalPrice    = (double)$sku->retail_price;

                            $this->orderSkus[] = [
                                "sku_id" => $sku->id,
                                "quantity" => $totalQuantity,
                                "tax" => 0,
                                "price" => $totalPrice,
                                "discount_amount" => 0,
                                "order_amount" => $totalPrice * $totalQuantity,
                                "total_amount" => $totalPrice * $totalQuantity,
                                "from_sku_combo" => OrderSku::FROM_SKU_COMBO_TRUE
                            ];

                            $dataCreate = [
                                'order_id' => $order->id,
                                'sku_id' => $sku->id,
                                'sku_combo_id' => $skuCombo->id,
                                'quantity' => $totalQuantity,
                                'price' => $totalPrice,
                            ];

                            OrderSkuComboSku::create($dataCreate);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    protected function makeOrderPayment(Order $order)
    {
        $country = $this->merchant->getCountry();
        if ($country instanceof Location) {
            $order->currency_id = $country->currency_id;
        }
        $data['intended_delivery_at'] = (isset($this->input['intended_delivery_at'])) ? Service::order()->formatDateTime($this->input['intended_delivery_at']) : null;
        $data['created_at_origin']    = (isset($this->input['created_at_origin'])) ? Service::order()->formatDateTime($this->input['created_at_origin']) : null;
        $data['cod']                  = (isset($this->input['cod'])) ? trim($this->input['cod']) : null;
        // check có số tiền thanh toán trước trong file import ko?
        $data['payment_amount'] = (isset($this->input['payment_amount']) ? trim($this->input['payment_amount']) : null);
        $orderSkus              = $order->orderSkus;

        // Nếu file import có nhập total_amount thì lấy theo file import
        if (isset($this->input['total_amount'])) {
            if ($this->input['total_amount'] > 0) {
                $orderAmount = $orderSkus->sum('order_amount');
                if ($this->orderAmount) {
                    $orderAmount = $this->orderAmount;
                }
                $totalAmount = $this->input['total_amount'];
                $cod         = $totalAmount - $data['payment_amount'];
            }
            if ($this->input['total_amount'] === 0) {
                $orderAmount = $orderSkus->sum('order_amount');
                $totalAmount = $this->input['total_amount'];
                $cod         = $totalAmount;
                if ($this->orderAmount) {
                    $orderAmount = $this->orderAmount;
                }
            }
        } else {
            $orderAmount = $orderSkus->sum('order_amount');
            $totalAmount = $orderSkus->sum('total_amount');
            if ($this->orderAmount) {
                $orderAmount = $this->orderAmount;
            }
            if ($this->totalAmount) {
                $totalAmount = $this->totalAmount;
            }
            $cod = $totalAmount - $data['payment_amount'];
        }

        $order->order_amount = $orderAmount;
        $order->total_amount = $totalAmount;
        $order->cod          = $cod;
        $order->save();
    }

    /**
     * @param Order $order
     * @return OrderTransaction|null
     */
    public function makeOrderTransaction(Order $order)
    {
        return (new CreateOrderTransaction($order, $this->input, $this->creator))->handle();
    }
}
