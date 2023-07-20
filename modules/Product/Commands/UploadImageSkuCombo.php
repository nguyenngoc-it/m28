<?php

namespace Modules\Product\Commands;

use Gobiz\Support\Helper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\SkuCombo;
use Modules\User\Models\User;

class UploadImageSkuCombo
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
        $skuComboUpdated = $this->updateImage($this->skuCombo);

        return $skuComboUpdated;
    }

    /**
     * @param SkuCombo $skuComboUpdated
     * @return void
     */
    public function updateImage(SkuCombo $skuComboUpdated)
    {
        $files = data_get($this->input, 'images');
        $imageDeletes = data_get($this->input, 'images_delete_url', []);
        
        $skuComboImages = (array) $skuComboUpdated->image;

        // dd($skuComboImages, $imageDeletes);

        // Xoá images
        if ($imageDeletes) {
            foreach ($imageDeletes as $image) {
                if (in_array($image, $skuComboImages)) {
                    if (App::environment('local')) {
                        Storage::delete($image); 
                    } else {
                        $path = str_replace(config('filesystems.disks.s3.url'), '', $image);
                        Storage::disk('s3')->delete($path); 
                    }
                    $key = array_search($image, $skuComboImages);
                    if ($key !== false) {
                        unset($skuComboImages[$key]);
                    }
                }
            }
        }

        // Add ảnh mới
        if ($files) {
            foreach ($files as $file) {
                $nameFile = Helper::quickRandom(10);
                $filePath = 'skuCombo/' . $nameFile . '.jpg';
                $uploaded = '';
                if (App::environment('local')) {
                    if (Storage::put($filePath, $file)) {
                        $uploaded = Storage::url($filePath);
                    }
                } else {
                    $uploaded = Storage::disk('s3')->put('skuCombo-' . $skuComboUpdated->tenant->id . '/' . $nameFile, $file, 'public');
                    $uploaded = config('filesystems.disks.s3.url') . '/' . $uploaded;
                }
                $skuComboImages[] = $uploaded;
            }
        }

        $skuComboUpdated->image = array_values($skuComboImages);
        $skuComboUpdated->save();

        return $skuComboUpdated;

    }
}
