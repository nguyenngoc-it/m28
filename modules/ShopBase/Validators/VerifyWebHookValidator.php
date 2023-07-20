<?php

namespace Modules\ShopBase\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Service;

class VerifyWebHookValidator extends Validator
{

    /**
     * @var
     */
    protected $inputData;

    public function __construct($merchantId, $inputData)
    {
        $this->merchantId = $merchantId;
        $this->inputData  = $inputData;
        parent::__construct([]);
    }

    /**
     * @var integer
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    protected function customValidate()
    {
        $this->merchant = Merchant::find($this->merchantId);
        if (!$this->merchant instanceof Merchant) {
            $this->errors()->add('merchant_id', static::ERROR_NOT_EXIST);
            return;
        }

        if(empty($_SERVER['HTTP_X_SHOPBASE_HMAC_SHA256'])) {
            $this->errors()->add('HTTP_X_SHOPBASE_HMAC_SHA256', static::ERROR_NOT_EXIST);
            return;
        }

        $hmac_header = $_SERVER['HTTP_X_SHOPBASE_HMAC_SHA256'];
        if(!Service::shopBase()->verifyWebhook($this->inputData, $hmac_header, $this->merchant->shop_base_secret)) {
            $this->errors()->add('verifyWebhook', static::ERROR_INVALID);
            return;
        }
    }

    /**
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }
}