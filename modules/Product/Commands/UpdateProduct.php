<?php

namespace Modules\Product\Commands;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductOption;
use Modules\Product\Models\ProductOptionValue;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuOptionValue;
use Modules\Product\Models\Unit;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Service;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\User;

class UpdateProduct extends UpdateProductBase
{
    /**
     * @var string|null
     */
    protected $oldProductCode = '';

    /**
     * @var array
     */
    protected $updateKeys = [
        'name',
        'code',
        'unit_id',
        'category_id',
        'supplier_id',
        'description',
        'dropship',
        'weight', 'height', 'width', 'length'
    ];

    /**
     * @var array|Collection|ProductOption[]
     */
    protected $productOptions = [];

    /**
     * @var array
     */
    protected $optionValueIds = [];

    /**
     * UpdateProduct constructor.
     * @param Product $product
     * @param array $input
     * @param User $user
     */
    public function __construct(Product $product, array $input, User $user)
    {
        parent::__construct($product, $input, $user);
        $this->productOptions = $this->product->productOptions;
    }

    /**
     * @return Product|null
     * @throws Exception
     */
    public function handle()
    {
        $changeProductCode    = (!empty($this->input['code']) && trim($this->input['code']) != trim($this->product->code));
        $this->oldProductCode = trim($this->product->code);
        $this->update();
        $this->syncOptions();
        $this->syncSkus();
        $this->createDefaultSku();
        if ($changeProductCode) {
            $this->changeSkuCode();
        }

        /**
         * Event
         */
        (new ProductUpdated($this->product->id, $this->user->id, $this->payloadLogs, $this->autoPrice))->queue();

        return $this->product;
    }

    /**
     * Cập nhật thông tin sản phẩm
     */
    protected function update()
    {
        $changeCategory = false;
        $changeUnit     = false;
        $changeSupplier = false;
        foreach ($this->input as $key => $value) {

            if (
                $value !== null &&
                in_array($key, $this->updateKeys) &&
                $this->product->{$key} != $value
            ) {
                $old = $this->product->{$key};
                $new = $value;
                if ($key == 'category_id') {
                    $changeCategory = true;
                    $categoryOld    = Category::find($this->product->category_id);
                    $categoryNew    = Category::find($value);
                    $old            = ($categoryOld instanceof Category) ? $categoryOld->name : '';
                    $new            = ($categoryNew instanceof Category) ? $categoryNew->name : '';
                }

                if ($key == 'unit_id') {
                    $changeUnit = true;
                    $unitOld    = Unit::find($this->product->unit_id);
                    $unitNew    = Unit::find($value);
                    $old        = ($unitOld instanceof Unit) ? $unitOld->name : '';
                    $new        = ($unitNew instanceof Unit) ? $unitNew->name : '';
                }

                if ($key == 'supplier_id') {
                    $changeSupplier = true;
                    $supplierOld    = Supplier::find($this->product->supplier_id);
                    $supplierNew    = Supplier::find($value);
                    $old        = ($supplierOld instanceof Supplier) ? $supplierOld->code : '';
                    $new        = ($supplierNew instanceof Supplier) ? $supplierNew->code : '';
                }

                $this->payloadLogs[$key]['old'] = $old;
                $this->payloadLogs[$key]['new'] = $new;
                $this->product->{$key}          = (is_string($value)) ? trim($value) : $value;
            }
        }
        $this->updateImages();
        $this->updateServicePrices();
        $this->product->save();

        if ($changeCategory) {
            $this->product->skus()->update([
                'category_id' => $this->product->category_id,
            ]);
        }
        if ($changeUnit) {
            $this->product->skus()->update([
                'unit_id' => $this->product->unit_id,
            ]);
        }
        if ($changeSupplier) {
            $this->product->skus()->update([
                'supplier_id' => $this->product->supplier_id,
            ]);
        }
    }

    /**
     * Đồng bộ lại options của sản phẩm
     *
     * @throws Exception
     */
    protected function syncOptions()
    {
        if (
            !isset($this->input['options']) ||
            $this->input['options'] === null
        ) return;

        $options   = (array)Arr::get($this->input, 'options');
        $optionIds = [];
        foreach ($options as $option) {
            if (empty($option['label'])) continue;

            $optionLabel = trim($option['label']);
            if (empty($option['id'])) {
                $productOption = ProductOption::create([
                    'product_id' => $this->product->id,
                    'label' => $optionLabel
                ]);

                $this->payloadLogs['options']['added'][] = [
                    'option' => $productOption->attributesToArray(),
                    'values' => (array)$option['values']
                ];
            } else {
                $optionId             = intval($option['id']);
                $optionIds[$optionId] = $optionId;
                $productOption        = ProductOption::find($optionId);
                if ($productOption->label != $optionLabel) {
                    $productOption->label = $optionLabel;
                    $productOption->save();
                }
            }

            if (empty($option['values']) || !$productOption instanceof ProductOption) continue;

            $this->syncOptionValues((array)$option['values'], $productOption);
        }

        $this->deleteOptions($optionIds);
    }

    /**
     * Xóa thuộc tính sản phẩm
     * @param $optionIds
     * @throws Exception
     */
    protected function deleteOptions($optionIds)
    {
        foreach ($this->productOptions as $productOption) {
            if (in_array($productOption->id, $optionIds)) {
                continue;
            }

            $optionValueIds = $productOption->options->pluck('id')->toArray();
            if (!empty($optionValueIds) && !Service::product()->canDeleteOptionValue($optionValueIds)) {
                continue;
            }

            $productOptionValues = $productOption->options;
            foreach ($productOptionValues as $productOptionValue) {
                $this->deleteProductOptionValue($productOptionValue);
            }

            $productOption->delete();

            $this->payloadLogs['options']['removed'][] = [
                'option' => $productOption->attributesToArray(),
            ];
        }
    }

    /**
     * Đồng bộ lại giá tri thuộc tính của sản phẩm
     * @param $values
     * @param ProductOption $productOption
     * @throws Exception
     */
    protected function syncOptionValues($values, ProductOption $productOption)
    {
        $productOptionValues = $productOption->options;
        $valueIds            = [];
        foreach ($values as $value) {
            if (empty($value['label'])) continue;
            $valueLabel = trim($value['label']);

            if (empty($value['id'])) {
                $productOptionValue = ProductOptionValue::create([
                    'product_id' => $this->product->id,
                    'product_option_id' => $productOption->id,
                    'label' => $valueLabel
                ]);
                $valueId            = $productOptionValue->id;

                $this->payloadLogs['option_values']['added'][] = [
                    'option' => $productOption->attributesToArray(),
                    'value' => $productOptionValue->attributesToArray()
                ];

            } else {
                $valueId = $value['id'];
            }
            $valueIds[$valueId]                                       = $valueId;
            $this->optionValueIds[$productOption->label][$valueLabel] = $valueId;
        }

        foreach ($productOptionValues as $productOptionValue) {
            if (
                in_array($productOptionValue->id, $valueIds) ||
                !Service::product()->canDeleteOptionValue($productOptionValue->id)
            ) {
                continue;
            }

            $this->deleteProductOptionValue($productOptionValue);

            $this->payloadLogs['option_values']['removed'][] = [
                'option' => $productOption->attributesToArray(),
                'value' => $productOptionValue->attributesToArray()
            ];
        }
    }

    /**
     * @param ProductOptionValue $productOptionValue
     * @throws Exception
     */
    protected function deleteProductOptionValue(ProductOptionValue $productOptionValue)
    {
        SkuOptionValue::query()->where('product_option_value_id', $productOptionValue->id)->delete();
        $productOptionValue->delete();
    }

    /**
     * Đồng bộ lại skus của sản phẩm
     */
    protected function syncSkus()
    {
        if (
            !isset($this->input['skus']) ||
            $this->input['skus'] === null
        ) return;

        $skus = (array)Arr::get($this->input, 'skus');

        $this->softDeleteSku($skus);

        foreach ($skus as $sku) {
            if (!isset($sku['option_values'])) continue;

            $optionValues   = (array)$sku['option_values'];
            $optionValueIds = [];
            foreach ($optionValues as $optionValue) {
                if (!empty($optionValue['id'])) {
                    $optionValueIds[] = intval($optionValue['id']);
                } elseif (!empty($optionValue['label']) && !empty($optionValue['option_label'])) {
                    $optionValueLabel = trim($optionValue['label']);
                    $optionLabel      = trim($optionValue['option_label']);

                    if (isset($this->optionValueIds[$optionLabel][$optionValueLabel])) {
                        $optionValueIds[] = intval($this->optionValueIds[$optionLabel][$optionValueLabel]);
                    }
                }
            }

            if (empty($sku['id'])) {
                $skuData = Service::product()->createSKU($this->product, $sku, $this->user);
            } else {
                $skuData = $this->product->skus()->firstWhere(['id' => intval($sku['id'])]);
            }

            if (!$skuData instanceof Sku) {
                continue;
            }

            $skuData->optionValues()->sync($optionValueIds);
            $skuData->name = $skuData->makeName();
            $skuData->supplier_id = $this->product->supplier_id;
            $skuDataUpdate = [];

            foreach (['weight', 'width', 'height', 'length'] as $w) {
                if (isset($sku[$w])) {
                    if (trim($sku[$w]) !== '') {
                        $wAtt = floatval($sku[$w]);
                    } else {
                        $wAtt = null;
                    }
                    if ($wAtt !== $skuData->{$w}) {
                        $skuDataUpdate[$w] = ['old' => $skuData->{$w}, 'new' => $wAtt];
                        $skuData->{$w}     = $wAtt;
                    }
                }
            }

            foreach (['code', 'images'] as $p) {
                if (isset($sku[$p]) && $skuData->{$p} != $sku[$p]) {
                    if ($p == 'code') {
                        $skuDataUpdate[$p] = ['old' => $skuData->{$p}, 'new' => $sku[$p]];
                    }
                    $skuData->{$p} = $sku[$p];
                }

                if (empty($skuData->code)) {
                    $skuData->code = $this->product->code . '_' . $skuData->id;
                }
            }

            if (!empty($skuDataUpdate)) {
                $skuData->logActivity(SkuEvent::SKU_UPDATE, $this->user, $skuDataUpdate);

                $skuData->product->logActivity(ProductEvent::SKU_UPDATE, $this->user, [
                    'data' => $skuDataUpdate,
                    'sku' => $skuData->only(['id', 'code', 'name', 'ref'])
                ]);
            }

            $skuData->save();
        }
    }

    /**
     * Xoa SKU
     * @param $skuNew
     */
    protected function softDeleteSku($skuNew)
    {
        $skuNewIds = [];
        foreach ($skuNew as $item) {
            if (!empty($item['id'])) {
                $skuNewIds[] = $item['id'];
            }
        }
        $skus = $this->product->skus()->where('status', '!=', Sku::STATUS_STOP_SELLING)->get();
        /** @var Sku $sku */
        foreach ($skus as $sku) {
            if (
                !in_array($sku->id, $skuNewIds) &&
                Service::product()->canDeleteSkus([$sku->id])
            ) {
                $sku->update(['status' => Sku::STATUS_STOP_SELLING]);

                $this->product->logActivity(ProductEvent::SKU_DELETE, $this->user, [
                    'sku' => $sku->only(['id', 'name', 'code', 'barcode'])
                ]);
            }
        }
    }


    /**
     * Thay doi lai ma SKU code
     */
    protected function changeSkuCode()
    {
        $productCode = trim($this->input['code']);
        $skus        = $this->product->skus()->get();
        foreach ($skus as $i => $sku) {
            $skuCode = $sku->code == $this->oldProductCode ? $productCode : $productCode . '-' . $sku->id;
            if ($skuCode != $sku->code) {
                $sku->code = $skuCode;
                $sku->save();
            }
        }
    }

    /**
     * Tạo sku mặc định nếu xóa toàn bộ thuộc tính
     */
    protected function createDefaultSku()
    {
        $product = $this->product->refresh();

        if (
        !$product->skus()->where('status', '!=', Sku::STATUS_STOP_SELLING)->count()
        ) {
            $systemUser = Service::user()->getSystemUserDefault();
            $sku        = Sku::updateOrCreate(
                [
                    'code' => $this->oldProductCode,
                    'tenant_id' => $this->product->tenant_id,
                ],
                [
                    'status' => Sku::STATUS_ON_SELL,
                    'merchant_id' => $this->product->merchant_id,
                    'product_id' => $this->product->id,
                    'unit_id' => $this->product->unit_id,
                    'category_id' => $this->product->category_id,
                    'supplier_id' => $this->product->supplier_id,
                    'creator_id' => $systemUser ? $systemUser->id : $this->user->id,
                    'name' => $this->product->name,
                    'weight' => $this->product->weight,
                    'height' => $this->product->height,
                    'width' => $this->product->width,
                    'length' => $this->product->length
                ]
            );

            $this->product->logActivity(ProductEvent::SKU_CREATE, $systemUser, [
                'sku' => $sku->only(['id', 'name', 'code', 'barcode']),
                'default' => true,
            ]);
        }
    }
}
