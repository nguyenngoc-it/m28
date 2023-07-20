<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;

class CreateBatchOfGoodsValidator extends Validator
{
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
            'code' => 'required|string',
            'cost_of_goods' => 'numeric',
            'production_at' => 'required|date_format:Y-m-d',
            'expiration_at' => 'required|date_format:Y-m-d',
        ];
    }

    protected function customValidate()
    {
        $productionAt = $this->input('production_at');
        $productionAt = Carbon::parse($productionAt);
        $expirationAt = $this->input('expiration_at');
        $expirationAt = Carbon::parse($expirationAt);

        if (!$this->sku->is_batch) {
            $this->errors()->add('sku', 'not_is_batch');
            return;
        }
        if (BatchOfGood::query()->where([
            'sku_id' => $this->sku->id,
            'code' => $this->input('code')
        ])->first()) {
            $this->errors()->add('batch_of_good', static::ERROR_EXISTS);
            return;
        }
        if ($productionAt->timestamp > Carbon::now()->timestamp) {
            $this->errors()->add('production_at', static::ERROR_INVALID);
            return;
        }
        if ($productionAt->timestamp >= $expirationAt->timestamp) {
            $this->errors()->add('expiration_at', static::ERROR_INVALID);
        }
    }
}
