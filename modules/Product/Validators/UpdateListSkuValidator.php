<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;

class UpdateListSkuValidator extends Validator
{

    /** @var Sku $sku */
    protected $sku;

    /**
     * @param array $input
     */
    public function __construct(array $input = [])
    {
        parent::__construct($input);
    }

    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'skus' => 'array',
            'skus.*.sku_id' => 'required',
        ];
    }

    public function customValidate()
    {
        $skus = Arr::get($this->input, 'skus', []);
        foreach ($skus as $item) {
            $sku = Sku::query()->where('id', $item['sku_id'])->first();
            if (!$sku) {
                $this->errors()->add('sku_id', self::ERROR_EXISTS);
                return;
            }
        }
    }

}
