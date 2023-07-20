<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Document\Models\Document;
use Modules\Merchant\Models\Merchant;

class ImportingSkuDocumentSkuInventoryValidator extends Validator
{
    /** @var Document */
    protected $documentSkuInventory;

    /** @var Merchant | null */
    protected $merchant;

    public function rules()
    {
        return [
            'id' => 'required|int',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentSkuInventory(): Document
    {
        return $this->documentSkuInventory;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id', 0);

        if (!$this->documentSkuInventory = Document::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $documentId
        ])->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
        if ($this->documentSkuInventory->status != Document::STATUS_DRAFT) {
            $this->errors()->add('id', static::ERROR_STATUS_INVALID);
            return;
        }

        $merchantId = Arr::get($this->input, 'merchant_id', 0);
        if(!empty($merchantId)) {
            $this->merchant = $this->documentSkuInventory->tenant->merchants()->firstWhere('id', $merchantId);
            if(!$this->merchant instanceof Merchant) {
                $this->errors()->add('merchant_id', static::ERROR_EXISTS);
                return;
            }
        }
    }

    /**
     * @return Merchant|null
     */
    public function getMerchant()
    {
        return $this->merchant;
    }
}
