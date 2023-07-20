<?php

namespace Modules\OrderPacking\Commands;

use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

trait DownloadTempTrackingTrait
{
    /**
     * @param $orderPacking
     * @param $defaultValue
     *
     * @return mixed
     */
    protected function getTempTrackingValue(OrderPacking $orderPacking, $defaultValue)
    {
        if (empty($defaultValue)) {
            return '';
        }

        $separateValues = explode('.', $defaultValue);
        if (count($separateValues) == 1) {
            $val = $defaultValue;
            switch ($defaultValue) {
                case 'weight':
                    $val = $this->getWeight($orderPacking);
                    break;
                case 'remark':
                    $val = $this->getRemark($orderPacking);
                    break;
                case 'province':
                    $val = $orderPacking->order->receiverProvince ? $orderPacking->order->receiverProvince->label : '';
                    break;
                case 'district':
                    $val = $orderPacking->order->receiverDistrict ? $orderPacking->order->receiverDistrict->label : '';
                    break;
                case 'ward':
                    $val = $orderPacking->order->receiverWard ? $orderPacking->order->receiverWard->label : '';
                    break;
                default:
            }
            return $val;
        }

        if ($column = $separateValues[1]) {
            $table = $separateValues[0];
            if ($table == 'orders') {
                return $orderPacking->order->{$column};
            }
            if ($table == 'order_packings') {
                return $orderPacking->{$column};
            }
        }

        return '';
    }

    protected function getRemark(OrderPacking $orderPacking)
    {
        if ($orderPacking->remark) {
            return $orderPacking->remark;
        }

        $items         = Service::orderPacking()->makeItemData($orderPacking);
        $remark        = '';
        $totalQuantity = 0;
        foreach ($items as $item) {
            $remark        .= $item['name'] . ' x ' . $item['quantity'] . ' / ';
            $totalQuantity += $item['quantity'];
        }
        if (!empty($remark)) {
            $remark               = substr($remark, 0, -2);
            $remark               .= '- ' . $totalQuantity . 'PCS - ' . round($orderPacking->order->cod, 2);
            $orderPacking->remark = $remark;
            $orderPacking->save();
        }
        return $remark;
    }

    /**
     * @param OrderPacking $orderPacking
     * @return float|int
     */
    protected function getWeight(OrderPacking $orderPacking)
    {
        $weight            = 0;
        $orderPackingItems = $orderPacking->orderPackingItems;
        foreach ($orderPackingItems as $orderPackingItem) {
            $weight = $weight + ($orderPackingItem->sku->weight * $orderPackingItem->quantity);
        }

        return $weight;
    }
}
