<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class UpdatingSkuImportingValidator extends Validator
{
    /** @var Document */
    protected $documentImporting;
    /** @var array */
    protected $skus;

    public function rules()
    {
        return [
            'id' => 'required',
            'skus' => 'required|array',
            'skus.*.warehouse_area_id' => 'required',
            'skus.*.real_quantity' => 'required|numeric|gte:0',
            'skus.*.sku_importing_id' => 'int',
            'skus.*.is_deleted' => 'boolean',
            'skus.*.sku_id' => 'required',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentImporting(): Document
    {
        return $this->documentImporting;
    }

    /**
     * @return array
     */
    public function getSkus(): array
    {
        return $this->skus;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        $this->skus = $this->input('skus');
        if (!$this->documentImporting = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentImporting->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }
    }
}
