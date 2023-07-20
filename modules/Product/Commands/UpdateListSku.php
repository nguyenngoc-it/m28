<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;
use Modules\Product\Services\SkuEvent;
use Modules\User\Models\User;

class UpdateListSku
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * @param array $input
     */
    public function __construct(array $input, User $creator)
    {
        $this->creator = $creator;
        $this->input   = $input;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $skus = Arr::get($this->input, 'skus', []);
        foreach ($skus as $item) {
            $payloadLogs = [];
            /** @var Sku $sku */
            $sku = Sku::query()->where('id', $item['sku_id'])->first();
            unset($item['sku_id']);
            foreach ($item as $att => $value) {
                if ($sku->{$att} != $value) {
                    $payloadLogs[$att]['old'] = $sku->{$att};
                    $payloadLogs[$att]['new'] = $value;
                    $sku->{$att}              = $value;
                }
                $sku->confirm_weight_volume = true;
            }
            $sku->save();
            $sku->logActivity(SkuEvent::SKU_UPDATE, $this->creator, $payloadLogs);
        }

    }

}
