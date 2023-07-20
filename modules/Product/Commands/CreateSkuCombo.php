<?php

namespace Modules\Product\Commands;

use Gobiz\Support\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuComboSku;
use Modules\Product\Services\SkuEvent;
use Modules\User\Models\User;

class CreateSkuCombo
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
        DB::transaction(function () {
            $this->createSkuCombo();
            $this->updateImage();

            $this->skuCombo->refresh();

            $this->createSkuComboSku();
        });

        $this->skuCombo->logActivity(SkuEvent::SKU_COMBO_CREATE, $this->user);

        return $this->skuCombo;
    }


    /**
     * @param $skuId
     * @return void
     */
    public function createSkuComboSku()
    {
        $skuCombo = $this->skuCombo;
        $skus     = Arr::get($this->input, 'skus');

        $skus = json_decode($skus, true);
        foreach ($skus as $sku) {
            SkuComboSku::create([
                'sku_combo_id' => $skuCombo->id,
                'sku_id' => $sku['id'],
                'quantity' => $sku['quantity']
            ]);
        }
    }

    /**
     * @return void
     */
    public function updateImage()
    {
        $files = Arr::get($this->input, 'files');
        if ($files) {
            $images = [];
            foreach ($files as $file) {
                $nameFile = Helper::quickRandom(10);
                $filePath = 'skuCombo/' . $nameFile . '.jpg';
                if (App::environment('local')) {
                    if (Storage::put($filePath, $file)) {
                        $uploaded = Storage::url($filePath);
                    }
                } else {
                    // $uploaded = $this->skuCombo->tenant->storage()->put('skuCombo/' . $nameFile, $file->openFile(), 'public');
                    $uploaded = Storage::disk('s3')->put('skuCombo-' . $this->skuCombo->tenant->id . '/' . $nameFile, $file, 'public');
                    $uploaded = config('filesystems.disks.s3.url') . '/' . $uploaded;
                    $images[] = $uploaded;
                }
            }
            $this->skuCombo->image = $images;
        }
        $this->skuCombo->save();

    }

    /**
     * @return void
     */
    public function createSkuCombo()
    {
        $name       = Arr::get($this->input, 'name', '');
        $code       = Arr::get($this->input, 'code', '');
        $categoryId = Arr::get($this->input, 'category_id', 0);
        $price      = Arr::get($this->input, 'price', 0);
        $source     = Arr::get($this->input, 'source', '');

        $code    = $code ?: Helper::quickRandom(10);
        $skuCode = Sku::query()->where('code', $code)->first();
        if ($skuCode) {
            $code = Helper::quickRandom(10);
        }
        foreach ([
                     'name' => $name,
                     'code' => $code,
                     'category_id' => $categoryId,
                     'source' => $source,
                     'price' => (float)$price,
                     'image' => ''
                 ] as $field => $value) {
                $this->skuCombo->{$field} = $value;
        }
        $this->skuCombo->save();
    }
}
