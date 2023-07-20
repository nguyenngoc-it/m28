<?php

namespace Modules\Product\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\ProductOption;
use Modules\Product\Models\ProductOptionValue;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuOptionValue;
use Modules\Product\Models\Unit;
use Modules\Product\Validators\ImportedProductValidator;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportProducts
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $insertedProductKeys = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * ImportProducts constructor
     *
     * @param User $user
     * @param string $filePath
     */
    public function __construct(User $user, $filePath)
    {
        $this->user     = $user;
        $this->tenant   = $user->tenant;
        $this->filePath = $filePath;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->filePath, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        @unlink($this->filePath);

        return $this->errors;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function ($value) {
            return !empty($value);
        });
        if (!count($rowData)) {
            return;
        }

        $row = $this->makeRow($row);
        if (!$row) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }


        $validator = new ImportedProductValidator($this->user, $row, $this->insertedProductKeys);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        $productInput = [
            'tenant_id' => $this->tenant->id,
            'creator_id' => $this->user->id,
            'code' => trim($row['product_code']),
            'status' => Product::STATUS_ON_SELL,
            'name' => $row['product_name'],
            'description' => $row['product_description'],
            'unit_id' => $validator->getUnit() instanceof Unit ? $validator->getUnit()->id : null,
            'category_id' => $validator->getCategory() instanceof Category ? $validator->getCategory()->id : null,
            'supplier_id' => $validator->getSupplier() instanceof Supplier ? $validator->getSupplier()->id : null,
            'ubox_product_code' => $row['ubox_product_code'],
        ];

        DB::transaction(function () use ($productInput, $validator) {
            $product = Product::create($productInput);

            $this->importProductMerchant($product, $validator->getMerchants());
            $this->importProductOptions($product, $validator->getOptions());
            $this->importSkuOptions($product);

            (new ProductCreated($product->id))->queue();
        });

        $this->insertedProductKeys[] = $validator->getProductKey();
    }

    /**
     * @param array $row
     * @return array|bool|false
     */
    protected function makeRow(array $row)
    {
        $params = [
            'product_code',
            'product_name',
            'category_code',
            'supplier_code',
            'merchant_codes',
            'unit_code',
            'product_description',
            'option_1',
            'option_1_value',
            'option_2',
            'option_2_value',
            'option_3',
            'option_3_value',
            'ubox_product_code',
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return false;
        }

        return array_combine($params, $values);
    }

    /**
     * Thêm merchant cho product
     *
     * @param Product $product
     * @param array $merchants
     */
    public function importProductMerchant(Product $product, array $merchants)
    {
        if (empty($merchants)) return;

        foreach ($merchants as $merchant) {
            ProductMerchant::create([
                'product_id' => $product->id,
                'merchant_id' => $merchant->id
            ]);
        }
    }

    /**
     * Thêm thuộc tính và giá trị của thuộc tính cho product
     *
     * @param Product $product
     * @param array $options
     */
    public function importProductOptions(Product $product, array $options)
    {
        if (empty($options)) return;

        foreach ($options as $option) {
            $productOption = ProductOption::create([
                'product_id' => $product->id,
                'label' => $option['name']
            ]);

            $optionValues = $option['values'];

            foreach ($optionValues as $optionValue) {
                ProductOptionValue::create([
                    'product_id' => $product->id,
                    'product_option_id' => $productOption->id,
                    'label' => $optionValue
                ]);
            }
        }
    }

    /**
     * Tạo sku và Thêm thuộc tính cho sku
     *
     * @param Product $product
     */
    public function importSkuOptions(Product $product)
    {
        $options = $product->options()->map(function ($option) {
            return $option['options'];
        });

        if (($numOptions = $options->count()) == 0) {
            $this->importSku($product);
            return;
        };

        $combineOptions = [];

        for ($i = 0; $i < $options[0]->count(); $i++) {
            if ($numOptions > 1) {
                for ($j = 0; $j < $options[1]->count(); $j++) {
                    if ($numOptions > 2) {
                        for ($k = 0; $k < $options[2]->count(); $k++) {
                            $combineOptions[] = [$options[0][$i], $options[1][$j], $options[2][$k]];
                        }
                    } else {
                        $combineOptions[] = [$options[0][$i], $options[1][$j]];
                    }
                }
            } else {
                $combineOptions[] = [$options[0][$i]];
            }
        }

        foreach ($combineOptions as $index => $optionValues) {
            $sku = $this->importSku($product, $index + 1);
            foreach ($optionValues as $optionValue) {
                $this->importSkuOptionValue($sku, $optionValue);
            }
            $this->makeSkuName($sku, $optionValues);
        }
    }

    /**
     * Tạo sku
     *
     * @param Product $product
     * @param int $index
     * @return Sku
     */
    public function importSku(Product $product, int $index = null)
    {
        $sku = Sku::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'supplier_id' => $product->supplier_id,
            'category_id' => $product->category_id,
            'creator_id' => $this->user->id,
            'status' => Sku::STATUS_ON_SELL,
            'code' => $index ? $this->makeSkuCode($product->code, $index) : trim($product->code),
            'name' => $product->name
        ]);

        return $sku;
    }

    /**
     * Thêm thuộc tính cho sku
     *
     * @param Sku $sku
     * @param ProductOptionValue $optionValue
     */
    public function importSkuOptionValue(Sku $sku, ProductOptionValue $optionValue)
    {
        SkuOptionValue::create([
            'sku_id' => $sku->id,
            'product_option_id' => $optionValue->product_option_id,
            'product_option_value_id' => $optionValue->id,
        ]);
    }

    /**
     * generate sku code
     *
     * @param string $productCode
     * @param int $indexSku
     * @return string
     */
    public function makeSkuCode(string $productCode, int $indexSku)
    {
        $code = '000' . $indexSku;
        $code = substr($code, -3);

        return $productCode . '-' . $code;
    }

    /**
     * generate sku name
     *
     * @param Sku $sku
     * @param array $combineOptionValues
     */
    public function makeSkuName(Sku $sku, array $combineOptionValues)
    {
        $optionValues = array_map(function (ProductOptionValue $optionValue) {
            return $optionValue->label;
        }, $combineOptionValues);

        $optionValues = implode(', ', $optionValues);

        $sku->name = $sku->name . ' - ' . $optionValues;
        $sku->save();
    }
}
