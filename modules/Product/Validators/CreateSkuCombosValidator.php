<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Gobiz\Support\Helper;
use Illuminate\Http\UploadedFile;
use Modules\Category\Models\Category;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Tenant\Models\Tenant;

class CreateSkuCombosValidator extends Validator
{

    protected $tenant;

    public function __construct(array $input = [], Tenant $tenant = null)
    {
        parent::__construct($input);
        $this->tenant = $tenant;

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
            'files' => 'array|max:5',
            'files.*' => 'file|max:5120',
            'skus' => 'required|string',
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
                ($category instanceof Category && $category->tenant_id != $this->tenant->id)
            ) {
                $this->errors()->add('category_id', self::ERROR_INVALID);
                return;
            }
        }
        if ($skus){
            $skus = json_decode($skus, true);
            foreach ($skus as $sku){
                $sku = Sku::find($sku['id']);
                if (!$sku){
                    $this->errors()->add('sku', self::ERROR_INVALID);
                    return;
                }
            }
        }
    }


}
