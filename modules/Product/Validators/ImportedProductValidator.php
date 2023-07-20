<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Category\Models\Category;
use Modules\Product\Models\Unit;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\User\Models\UserMerchant;

class ImportedProductValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Unit
     */
    protected $unit;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Supplier
     */
    protected $supplier;

    /**
     * @var array
     */
    protected $merchants = [];

    /**
     * @var array
     */
    protected $insertedProductKeys = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * ImportedProductValidator constructor.
     * @param User $user
     * @param array $input
     * @param array $insertedProductKeys
     */
    public function __construct(User $user, array $input, $insertedProductKeys = [])
    {
        $this->user   = $user;
        $this->tenant = $user->tenant;
        $this->insertedProductKeys = $insertedProductKeys;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'product_code' => 'required',
            'product_name' => 'required',
        ];
    }

    protected function customValidate()
    {
        $product_code = $this->input('product_code');
        if (
            in_array($this->getProductKey(), $this->insertedProductKeys) ||
            $this->tenant->products()->firstWhere('code', $product_code)
        ) {
            $this->errors()->add('product_code', static::ERROR_ALREADY_EXIST);
            return;
        }

        $merchantCodes = $this->processStringToArray('merchant_codes');
        if (!empty($merchantCodes)) {
            foreach ($merchantCodes as $merchantCode) {
                $merchant = $this->tenant->merchants()->firstWhere('code', $merchantCode);
                if (empty($merchant)) {
                    $this->errors()->add('merchant_code', static::ERROR_NOT_EXIST);
                    return;
                }

                $userMerchant = UserMerchant::query()
                    ->where('merchant_id', $merchant->id)
                    ->where('user_id', $this->user->id)
                    ->first();

                if (empty($userMerchant)) {
                    $this->errors()->add('merchant_code', static::ERROR_INVALID);
                    return;
                }
                $this->merchants[] = $merchant;
            }
        }

        if (
            ($category_code = Arr::get($this->input, 'category_code', null)) &&
            !$this->category = $this->tenant->categories()->firstWhere('code', $category_code)
        ) {
            $this->errors()->add('category_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (
            ($supplier_code = Arr::get($this->input, 'supplier_code', null)) &&
            !$this->supplier = $this->tenant->suppliers()->firstWhere('code', trim($supplier_code))
        ) {
            $this->errors()->add('supplier_code', static::ERROR_NOT_EXIST);
            return;
        }

        if(
            $this->supplier instanceof Supplier &&
            !$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT) &&
            !in_array($this->supplier->id, $this->user->suppliers->pluck('id')->toArray())
        ) {
            $this->errors()->add('supplier_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (
            ($unit_code = Arr::get($this->input, 'unit_code', null)) &&
            !$this->unit = $this->tenant->units()->firstWhere('code', $unit_code)
        ) {
            $this->errors()->add('unit_code', static::ERROR_NOT_EXIST);
            return;
        }


        for ($i = 1; $i <= 3; $i++) {
            if(!$this->validateOption($i)) {
                return;
            }
        }
    }

    /**
     * @return array
     */
    public function validateOption(int $numOption)
    {
        $option       = Arr::get($this->input, "option_${numOption}", '');
        $optionValues = $this->processStringToArray("option_${numOption}_value");

        if (
            (empty($option) && !empty($optionValues)) ||
            (!empty($option) && empty($optionValues))
        ) {
            $this->errors()->add("option_${numOption}", static::ERROR_REQUIRED);
            return false;
        }
        if (empty($option) && empty($optionValues)) {
            return false;
        }

        $this->options[] = [
            'name' => $option,
            'values' => $optionValues,
        ];
    }

    /**
     * @param string $prop
     * @return array
     */
    public function processStringToArray(string $prop)
    {
        $array = Arr::get($this->input, $prop, '');
        $array = explode(',', $array);
        $array = array_filter($array, function ($element) { return !empty($element); });
        $array = array_map(function ($element) { return trim($element); }, $array);

        return array_unique($array);
    }

    /**
     * @return string
     */
    public function getProductKey()
    {
        return $this->input['product_code'];
    }

    /**
     * @return Unit|null
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplier()
    {
        return $this->supplier;
    }


    /**
     * @return array
     */
    public function getMerchants()
    {
        return $this->merchants;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
