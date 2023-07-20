<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Store\Models\StoreSku;

class CreateSkuComboApiValidator extends Validator
{

    protected $merchant;

    public function __construct(array $input = [], Merchant $merchant)
    {
        parent::__construct($input);
        $this->merchant = $merchant;

    }

    public static $acceptKeys = [
        'name',
        'code',
        'files',
        'category_id',
        'skus',
        'price',
        'source'
    ];

    public function rules()
    {
        return [
            'name' => 'required',
            'code' => 'string',
            'skus' => 'required|array',
            'source' => 'string'
        ];
    }

    public function customValidate()
    {
        $code = $this->input('code');
        $skus = $this->input('skus');

        $skuCombo = SkuCombo::query()->where('code', $code)->first();
        if ($skuCombo){
            $this->errors()->add('sku_combo_code', self::ERROR_ALREADY_EXIST);
        }

        if (isset($this->input['category_id'])) {
            $category = Category::find($this->input['category_id']);
            if (
                empty($category) ||
                ($category instanceof Category && $category->tenant_id != $this->merchant->tenant->id)
            ) {
                $this->errors()->add('category_id', self::ERROR_INVALID);
                return;
            }
        }
        if ($skus){
            foreach ($skus as $sku){
                $skuCode = data_get($sku, 'code');
                $quantity = data_get($sku, 'quantity', 0);

                if (!is_int($quantity)) {
                    $this->errors()->add('sku_code_' . $skuCode, 'SKU QUANTITY MUST NUMERIC');
                } else {
                    if ($quantity <= 0) {
                        $this->errors()->add('sku_code_' . $skuCode, 'SKU QUANTITY MUST > 0');
                    }
                }

                $sku = Sku::select('skus.*')
                            ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                            ->where(function($query) use ($skuCode){
                                return $query->where('store_skus.code', $skuCode)
                                                ->orWhere('skus.code', $skuCode);
                            })
                            ->where(function($query) {
                                return $query->where('skus.merchant_id', $this->merchant->id)
                                                ->orWhere('product_merchants.merchant_id', $this->merchant->id);
                            })
                            ->first();
                if (!$sku){
                    $this->errors()->add('sku_code_' . $skuCode, self::ERROR_INVALID);
                    return;
                }

                if ($sku->status == Sku::STATUS_STOP_SELLING){
                    $this->errors()->add('sku_code_' . $skuCode . '_stop_selling', self::ERROR_INVALID);
                    return;
                }
            }
        }
    }


}
