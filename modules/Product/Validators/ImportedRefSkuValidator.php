<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

class ImportedRefSkuValidator extends Validator
{
    /** @var Sku|null $sku */
    protected $sku;
    protected $ref;

    public function __construct(User $user, array $row)
    {
        parent::__construct($row, $user);
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'product_code' => 'required',
            'product_name' => 'required',
            'sku_name' => 'required',
            'sku_code' => 'required',
            'ref' => 'string'
        ];
    }

    /**
     * @return Sku|null
     */
    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    protected function customValidate()
    {
        $productCode = $this->input('product_code');
        $skuCode     = $this->input('sku_code');
        $ref         = $this->input('ref', '');
        $product     = Product::query()->where([
            'code' => $productCode,
            'tenant_id' => $this->user->tenant_id
        ])->first();
        if (!$product) {
            $this->errors()->add('product_code', static::ERROR_EXISTS);
            return;
        }

        if (!$this->sku = Sku::query()->where([
            'code' => $skuCode,
            'tenant_id' => $this->user->tenant_id
        ])->first()) {
            $this->errors()->add('sku_code', static::ERROR_EXISTS);
            return;
        }

        if ($ref) {
            $existRefSku = Sku::query()->where([
                'ref' => $ref,
                'tenant_id' => $this->user->tenant_id
            ])->where('code', '<>', $skuCode)->first();
            if ($existRefSku) {
                $this->errors()->add('ref', static::ERROR_ALREADY_EXIST);
                return;
            }
        }
    }
}
