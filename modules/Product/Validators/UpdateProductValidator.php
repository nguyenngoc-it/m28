<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Auth\Services\Permission;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;

class UpdateProductValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /**
     * @var Product|null
     */
    protected $product = null;

    /**
     * UpdateProductValidator constructor.
     * @param array $input
     * @param Product $product
     */
    public function __construct(array $input, Product $product)
    {
        parent::__construct($input);
        $this->product = $product;
    }

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'name',
        'code',
        'unit_id',
        'category_id',
        'supplier_id',
        'options',
        'skus',
        'description',
        'dropship',
        'files',
        'removed_files',
        'service_prices',
        'auto_price',
    ];

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'name' => '',
            'code' => '',
            'options' => 'array',
            'skus' => 'array',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'removed_files' => 'array',
            'service_prices' => 'array',
            'auto_price' => 'boolean',
        ];
    }


    protected function customValidate()
    {
        $this->tenant = $this->product->tenant;
        $product_code = $this->input('code');
        $servicePriceIds = $this->input('service_prices');


        if ($product_code && $this->tenant->products()->where('id', '!=', $this->product->id)->where('code', trim($product_code))->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
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

        if (isset($this->input['supplier_id'])) {
            $supplier = $this->tenant->suppliers()->find($this->input['supplier_id']);
            if (
                !$supplier instanceof Supplier ||
                (
                    !$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT) &&
                    !in_array($supplier->id, $this->user->suppliers->pluck('id')->toArray())
                )
            ) {
                $this->errors()->add('supplier_id', self::ERROR_INVALID);
                return;
            }
        }

        if (isset($this->input['unit_id'])) {
            $unit = Unit::find($this->input['unit_id']);
            if (
                empty($unit) ||
                ($unit instanceof Unit && $unit->tenant_id != $this->tenant->id)
            ) {
                $this->errors()->add('unit_id', self::ERROR_INVALID);
                return;
            }
        }

        /**
         * Nếu seller đang dùng gói dịch vụ thì bắt buộc các đơn giá dịch vụ phải nằm trong gói
         */
        if ($servicePriceIds && $this->user->merchant->servicePack) {
            $packServicePriceIds = $this->user->merchant->servicePack->servicePackPrices->pluck('service_price_id')->all();
            if (array_diff($servicePriceIds, $packServicePriceIds)) {
                $this->errors()->add('service_prices', 'not_in_service_pack');
            }
        }
    }
}
