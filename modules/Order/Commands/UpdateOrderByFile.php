<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Order\Commands\RelateObjects\InputOrderByFile;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Events\OrderSkusChanged;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Service;
use Modules\User\Models\User;

class UpdateOrderByFile
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var InputOrderByFile
     */
    protected $inputOrderByFile;

    /**
     * @var User|null
     */
    protected $creator = null;

    protected $logger;

    /**
     * UpdateOrder constructor.
     * @param Order $order
     * @param InputOrderByFile $inputOrderByFile
     * @param User $creator
     */
    public function __construct(Order $order, InputOrderByFile $inputOrderByFile, User $creator)
    {
        $this->order            = $order;
        $this->inputOrderByFile = $inputOrderByFile;
        $this->creator          = $creator;
        $this->logger           = LogService::logger('update_order_by_file');
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->logger->info('INPUT', get_object_vars($this->inputOrderByFile));
        $skus = $this->inputOrderByFile->skus;
        /**
         * Validate skus
         */
        $skuCodes = collect($skus)->pluck('sku_code')->all();
        $dbSkus   = $this->order->merchant->applyMerchantSkus()->whereIn('code', $skuCodes)->pluck('id', 'code')->all();

        if (count($dbSkus) != collect($skus)->count()) {
            $this->logger->error('EXCEPTION '.$this->order->code, ['skus invalid']);
            throw new Exception('skus invalid');
        }

        $syncSkus = [];
        foreach ($skus as $key => $sku) {
            if ($dbSkus[$sku['sku_code']]) {
                $skuOnOrder = $this->order->skus->where('code', $sku['sku_code'])->first();
                if ($skuOnOrder) {
                    $skuId = $skuOnOrder->id;
                } else {
                    $skuId = $dbSkus[$sku['sku_code']];
                }
                $orderAmount                         = $sku['sku_price'] * $sku['sku_quantity'];
                $totalAmount                         = $orderAmount - $sku['sku_discount'];
                $syncSkus[$skuId]['tenant_id']       = $this->creator->tenant_id;
                $syncSkus[$skuId]['order_id']        = $this->order->id;
                $syncSkus[$skuId]['price']           = $sku['sku_price'];
                $syncSkus[$skuId]['quantity']        = $sku['sku_quantity'];
                $syncSkus[$skuId]['order_amount']    = $orderAmount;
                $syncSkus[$skuId]['discount_amount'] = $sku['sku_discount'];
                $syncSkus[$skuId]['total_amount']    = $totalAmount;
            }
        }

        /**
         * Kiểm tra xem có thay đổi thông tin skus hay không
         */
        $changedSkus = false;
        if ($this->order->skus->count() != count($syncSkus)) {
            $changedSkus = true;
        }
        /** @var OrderSku $orderSku */
        foreach ($this->order->orderSkus as $orderSku) {
            if (empty($syncSkus[$orderSku->sku_id])) {
                $changedSkus = true;
                break;
            } else {
                if (
                    $syncSkus[$orderSku->sku_id]['quantity'] != $orderSku->quantity
                    || $syncSkus[$orderSku->sku_id]['discount_amount'] != $orderSku->discount_amount
                ) {
                    $changedSkus = true;
                    break;
                }
            }
        }

        /**
         * Xoá bản ghi stock trên đơn
         */
        if ($changedSkus) {
            Service::order()->removeStockOrder($this->order, $this->creator);
            /**
             * Insert order_skus
             */
            $this->order->skus()->sync($syncSkus);
            (new OrderSkusChanged($this->order, $this->creator, $syncSkus))->queue();
            Service::order()->updateMoneyWhenChangeSkus($this->order->refresh());
        }

        $shippingPartner = $this->inputOrderByFile->shippingPartner;
        /**
         * Cập nhật thông tin đơn
         */
        $this->order->receiver_name        = $this->inputOrderByFile->receiverName;
        $this->order->receiver_phone       = $this->inputOrderByFile->receiverPhone;
        $this->order->receiver_address     = $this->inputOrderByFile->receiverAddress;
        $this->order->receiver_ward_id     = $this->inputOrderByFile->receiverWard ? $this->inputOrderByFile->receiverWard->id : 0;
        $this->order->receiver_district_id = $this->inputOrderByFile->receiverDistrict ? $this->inputOrderByFile->receiverDistrict->id : 0;
        $this->order->receiver_province_id = $this->inputOrderByFile->receiverProvince ? $this->inputOrderByFile->receiverProvince->id : 0;
        $this->order->receiver_country_id  = $this->inputOrderByFile->receiverCountry ? $this->inputOrderByFile->receiverCountry->id : 0;
        $this->order->cod                  = $this->inputOrderByFile->cod;
        $this->order->shipping_partner_id  = ($shippingPartner) ? $shippingPartner->id : 0;
        $this->order->receiver_postal_code = $this->inputOrderByFile->receiverPostalCode;
        if ($changedAtts = $this->order->getDirty()) {
            $changedAtts = array_diff_key($changedAtts, [
                'receiver_ward_id' => 0,
                'receiver_district_id' => 0,
                'receiver_province_id' => 0,
                'receiver_country_id' => 0,
            ]);
            (new OrderAttributesChanged($this->order, $this->creator, Arr::only($this->order->getOriginal(), array_keys($changedAtts)), $changedAtts))->queue();
        }
        $this->order->save();
    }
}
