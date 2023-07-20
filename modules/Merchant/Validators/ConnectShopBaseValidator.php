<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

class ConnectShopBaseValidator extends Validator
{
    /**
     * ConnectShopBaseValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     */
    public function __construct(Merchant $merchant, array $input)
    {
        $this->merchant = $merchant;
        parent::__construct($input);
    }


    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'shop_base_account' => 'required',
            'shop_base_app_key' => 'required',
            'shop_base_password' => 'required',
            'shop_base_secret' => 'required',
        ];
    }


    protected function customValidate()
    {
        if(empty(config('app.url'))) {
            $this->errors()->add('config app.url', static::ERROR_INVALID);
            return;
        }

        $shopBaseAccount = trim($this->input['shop_base_account']);
        $merchant = $this->merchant->tenant->merchants()->where('shop_base_account', $shopBaseAccount)
            ->where( 'id' ,'!=', $this->merchant->id)->count();
        if ($merchant > 0) {
            $this->errors()->add('shop_base_account', static::ERROR_ALREADY_EXIST);
            return;
        }
    }
}