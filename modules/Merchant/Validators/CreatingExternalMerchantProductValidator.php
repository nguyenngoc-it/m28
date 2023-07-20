<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Gobiz\Support\Helper;
use Illuminate\Http\UploadedFile;
use Modules\Merchant\Models\Merchant;

class CreatingExternalMerchantProductValidator extends Validator
{
    /** @var Merchant $merchant */
    protected $merchant;
    /** @var UploadedFile $fileUpload */
    protected $fileUpload;

    public function rules()
    {
        return [
            'merchant_code' => 'required|string',
            'name' => 'required|string',
            'code' => 'string',
            'image' => 'string',
            'weight' => 'numeric',
            'height' => 'numeric',
            'width' => 'numeric',
            'length' => 'numeric'
        ];
    }

    /**
     * @return Merchant
     */
    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    /**
     * @return UploadedFile
     */
    public function getFileUpload(): UploadedFile
    {
        return $this->fileUpload;
    }

    protected function customValidate()
    {
        $merchantCode   = trim($this->input('merchant_code'));
        $code           = $this->input('code');
        $this->merchant = Merchant::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'code' => $merchantCode,
            'creator_id' => $this->user->id
        ])->first();

        if (empty($this->merchant)) {
            $this->errors()->add('merchant_code', static::ERROR_EXISTS);
            return;
        }
        if ($code && $this->merchant->products->where('code', $code)->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }

        $file = $this->input('image');
        if ($file) {
            $explode = explode(',', $file);
            $allow   = ['png', 'jpg', 'jpeg', 'svg'];
            $format  = str_replace(
                [
                    'data:image/',
                    ';',
                    'base64',
                ],
                [
                    '', '', '',
                ],
                $explode[0]
            );

            // check file format
            if (!in_array($format, $allow)) {
                $this->errors()->add('image', 'only_accept_png_jpg_svg');
                return;
            }

            // check base64 format
            if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                $this->errors()->add('image', static::ERROR_INVALID);
                return;
            }
            $this->fileUpload = Helper::createUploadedFilefromBase64($file);
            if (!$this->fileUpload instanceof UploadedFile) {
                $this->errors()->add('image', static::ERROR_INVALID);
                return;
            }
            if ($this->fileUpload->getSize() > 2000000) {
                $this->errors()->add('image', 'size_limit_2Mb');
                return;
            }
        }
    }
}
