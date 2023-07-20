<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;

class IsGoodsBatchValidator extends Validator
{
    /** @var Sku $sku */
    protected $sku;

    /**
     * CreateSKUValidator constructor.
     * @param array $input
     * @param Sku $sku
     */
    public function __construct(array $input, Sku $sku)
    {
        parent::__construct($input);
        $this->sku = $sku;
    }

    public function rules()
    {
        return [
            'is_batch' => 'required|boolean',
            'logic_batch' => 'in:' . BatchOfGood::LOGIC_FIFO . ',' . BatchOfGood::LOGIC_LIFO . ',' .
                BatchOfGood::LOGIC_FEFO . ',' . null
        ];
    }

    protected function customValidate()
    {
        $isBatch    = $this->input('is_batch');
        $logicBatch = $this->input('logic_batch');

        if ($isBatch && empty($logicBatch)) {
            $this->errors()->add('logic_batch', static::ERROR_REQUIRED);
            return;
        }

        /**
         * Không được tắt quản lý lô khi đã có sku lô
         */
        if ($isBatch === false && $this->sku->skuChildren->count() && $this->sku->is_batch) {
            $this->errors()->add('is_batch', 'has_sku_child');
            return;
        }

        /**
         * Tắt quản lý lô thì ko truyền logic quản lý lên
         */
        if ($isBatch === false && $logicBatch) {
            $this->errors()->add('logic_batch', static::ERROR_INVALID);
        }
    }
}
