<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\SkuCombo;
use Modules\User\Models\User;

class DetailSkuComboValidator extends Validator
{

    protected $skuComboId;

    protected $merchantId;

    protected $skuCombo;

    public function __construct($skuComboId, $merchantId = null)
    {
        parent::__construct([]);
        $this->skuComboId = $skuComboId;
        $this->merchantId = $merchantId;
    }

    public function customValidate()
    {
        $skuCombo = SkuCombo::where('id', $this->skuComboId);
        if ($this->merchantId) {
            $skuCombo = $skuCombo->merchant($this->merchantId);
        }

        $this->skuCombo = $skuCombo->first();
        if (!$this->skuCombo){
            $this->errors()->add('skuCombo', self::ERROR_NOT_EXIST);
            return;
        }
    }

    public function getSkuCombo()
    {
        return $this->skuCombo;
    }

}
