<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;

class UpdatingImportingBarcodeReturnGoodsValidator extends Validator
{
    /** @var Document */
    protected $documentImporting;

    /**
     * order_items => [{id:1, skus: [{id:1, quantity:1}]}]
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required',
            'order_items' => 'required',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentImporting(): Document
    {
        return $this->documentImporting;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentImporting = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentImporting->type != Document::TYPE_IMPORTING_RETURN_GOODS) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->documentImporting->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }

        $this->validateOrders();
    }

    /**
     * @return void
     */
    protected function validateOrders()
    {
        $orderItems = $this->input('order_items');
        foreach ($orderItems as $orderItem) {
            /** @var Order $order */
            if ($order = Order::find($orderItem['id'])) {
                $diff = array_diff($order->orderSkus->pluck('sku_id')->all(), collect($orderItem['skus'])->pluck('id')->all());
                if ($diff) {
                    $this->errors()->add('order_items', [
                        'skus_invalid' => ['order_id' => $orderItem['id']]
                    ]);
                }
                break;
            } else {
                $this->errors()->add('order_items', [
                    'not_found_order' => ['order_id' => $orderItem['id']]
                ]);
                break;
            }
        }
    }
}
