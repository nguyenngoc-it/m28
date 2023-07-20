<?php

namespace Modules\Product\Commands;

use Gobiz\Support\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Models\Category;
use Modules\Product\Events\SkuComboAttributesUpdated;
use Modules\Product\Events\SkuComboSkuUpdated;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuComboSku;
use Modules\Product\Services\SkuEvent;
use Modules\User\Models\User;

class UpdateSkuCombo
{
    /**
     * @var array
     */
    protected $input;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var SkuCombo
     */
    protected $skuCombo;

    public function __construct(SkuCombo $skuCombo, array $input, User $user)
    {
        $this->skuCombo = $skuCombo;
        $this->input    = $input;
        $this->user     = $user;

    }

    public function handle()
    {
        $skuComboUpdated = DB::transaction(function () {
            $skuComboUpdated = $this->updateSkuCombo();
            // Update relationship
            $this->updateSkuComboSku($skuComboUpdated);

            return $skuComboUpdated;
        });

        return $skuComboUpdated;
    }


    /**
     * @param SkuCombo $skuComboUpdated
     * @return void
     */
    public function updateSkuComboSku(SkuCombo $skuComboUpdated)
    {

        $skus = data_get($this->input, 'skus');
        if (!is_array($skus)) {
            $skus = json_decode($skus, true);
        }

        $skuSyncs = [];

        if ($skus) {
            foreach ($skus as $sku) {
                $skuSyncs[$sku['id']] = [
                    'quantity' => $sku['quantity']
                ];
            }

            $this->calculateSkuChangedInfo($skuComboUpdated, $skuSyncs);
        }
    }

    /**
     * @return SkuCombo
     */
    public function updateSkuCombo()
    {
        $categoryId = data_get($this->input, 'category_id', 0);
        $price      = data_get($this->input, 'price', 0);
        $source     = data_get($this->input, 'source', '');

        $dataSkuComboOriginal = $this->skuCombo->getOriginal();
        
        $this->skuCombo->category_id = $categoryId;
        $this->skuCombo->price       = $price;
        $this->skuCombo->source      = $source;

        $skuComboUpdated = $this->skuCombo->save();

        if ($skuComboUpdated) {
            $changedAtts = $this->skuCombo->getChanges();
            if (isset($changedAtts['updated_at'])) unset($changedAtts['updated_at']);

            // dd($dataSkuComboOriginal, $changedAtts);

            // Transform data logger for locations
            $dataCompare = Arr::only($dataSkuComboOriginal, array_keys($changedAtts));

            $this->makeLogSkuComboAttribute($dataCompare, $changedAtts);
        }

        return $this->skuCombo;
    }

    /**
     * Make Log For Order Attribute Changed
     *
     * @param array $dataBefore Dữ liệu trước khi thay đổi
     * @param array $dataAfter Dữ liệu sau khi thay đổi
     * @return void
     */
    protected function makeLogSkuComboAttribute(array $dataBefore, array $dataAfter)
    {
        foreach ($dataBefore as $key => $value) {
            $dataTransform = $this->transformCategoryLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataBefore[$key]);
                $dataBefore[$dataTransform[$key]['new_key']] = (string) $dataTransform[$key]['new_value'];
            }
        }

        foreach ($dataAfter as $key => $value) {
            $dataTransform = $this->transformCategoryLogInfo($key, $value);
            if ($dataTransform) {
                unset($dataAfter[$key]);
                $dataAfter[$dataTransform[$key]['new_key']] = (string) $dataTransform[$key]['new_value'];
            }
        }

        (new SkuComboAttributesUpdated($this->skuCombo, $this->user, $dataBefore, $dataAfter))->queue();
    }

    protected function calculateSkuChangedInfo(SkuCombo $skuComboUpdated, array $syncSkusData)
    {
        /**
         * Insert order_skus
         */
        $dataSkuComboSku = $skuComboUpdated->skuComboSkus;
        $dataSync        = $skuComboUpdated->skus()->sync($syncSkusData);
        $dataAttached    = data_get($dataSync, 'attached', []);
        $dataDetached    = data_get($dataSync, 'detached', []);
        $dataUpdated     = data_get($dataSync, 'updated', []);

        // Sku thêm mới
        if ($dataAttached) {
            foreach ($dataAttached as $skuId) {
                if (isset($syncSkusData[$skuId])) {
                    $syncSkusData[$skuId]['action'] = SkuEvent::SKU_COMBO_ADD_SKU;
                }
            }
        }

        // Sku bị xoá
        if ($dataDetached) {
            foreach ($dataDetached as $skuId) {
                $skuComboSku = $dataSkuComboSku->first(function($item) use ($skuId) {
                    return $item->sku_id == $skuId;
                });

                $syncSkusData[$skuId] = [
                    'sku_combo_id'    => $skuComboSku->sku_combo_id,
                    'tenant_id'       => $skuComboSku->sku->tenant_id,
                    'sku_id'          => $skuComboSku->sku_id,
                    'sku_combo_price' => $skuComboSku->skuCombo->price,
                    'sku_price'       => $skuComboSku->sku->price,
                    'sku_quantity'    => $skuComboSku->quantity,
                    'action'          => SkuEvent::SKU_COMBO_REMOVE_SKU,
                ];
            }
        }

        // Sku Update
        if ($dataUpdated) {
            foreach ($dataUpdated as $skuId) {
                if (isset($syncSkusData[$skuId])) {
                    $skuComboSku = $dataSkuComboSku->first(function($item) use ($skuId) {
                        return $item->sku_id == $skuId;
                    });
                    if ($skuComboSku->quantity != $syncSkusData[$skuId]['quantity']) {
                        $syncSkusData[$skuId]['action']       = SkuEvent::SKU_COMBO_UPDATE_SKU;
                        $syncSkusData[$skuId]['old_quantity'] = $skuComboSku->quantity;
                    } else {
                        unset($syncSkusData[$skuId]);
                    }
                }
            }
        }

        if ($syncSkusData) {
            (new SkuComboSkuUpdated($skuComboUpdated, $this->user, $syncSkusData))->queue();
        }
    }

    /**
     * Transform data category log info
     *
     * @param string $key
     * @param string $value
     * @return array
     */
    protected function transformCategoryLogInfo($key, $value)
    {
        $dataReturn   = [];

        // Get Category
        if ($key == 'category_id') {
            $category = Category::find(intval($value));
            if ($category) {
                $dataReturn[$key] = [
                    'new_key'   => 'category',
                    'new_value' => $category->name,
                ];
            } else {
                $dataReturn[$key] = [
                    'new_key'   => 'category',
                    'new_value' => '',
                ];
            }
        }

        return $dataReturn;
    }
    
}
