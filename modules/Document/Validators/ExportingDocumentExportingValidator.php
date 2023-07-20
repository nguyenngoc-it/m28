<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Stock\Models\Stock;

class ExportingDocumentExportingValidator extends Validator
{
    /** @var Document */
    protected $documentExporting;

    public function rules()
    {
        return [
            'id' => 'required|int',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentExporting(): Document
    {
        return $this->documentExporting;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentExporting = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentExporting->type != Document::TYPE_EXPORTING) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->documentExporting->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }

        /**
         * Kiểm tra tồn thực tế còn đủ để xuất hàng hay ko
         */
        $exportStocks = [];
        foreach ($this->documentExporting->orderExportings as $orderExporting) {
            if ($order = $orderExporting->order) {
                foreach ($order->orderStocks as $orderStock) {
                    $stock                                       = $orderStock->stock;
                    $exportStocks[$stock->id]['export_quantity'] = isset($exportStocks[$stock->id]) ? ($exportStocks[$stock->id]['export_quantity'] + (int)$orderStock->quantity) : (int)$orderStock->quantity;
                    $exportStocks[$stock->id]['sku_code']        = $stock->sku->code;
                }
            }
        }
        $currentStocks = Stock::query()->whereIn('id', array_keys($exportStocks))->pluck('real_quantity', 'id')->all();
        $errorStocks   = [];
        foreach ($exportStocks as $stockId => $exportStock) {
            if (isset($currentStocks[$stockId]) && ($currentStocks[$stockId] < $exportStock['export_quantity'])) {
                $errorStocks[] = [
                    'sku' => $exportStock['sku_code'],
                    'stock_id' => $stockId,
                    'real_quantity' => $currentStocks[$stockId]
                ];
            }
        }
        if ($errorStocks) {
            $this->errors()->add('insufficient', $errorStocks);
            return;
        }
    }
}
