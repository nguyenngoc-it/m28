<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;

class UpdateSKUStatusValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /** @var Sku[] $skus */
    protected $skus;

    /**
     * UpdateSKUStatusValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input = [])
    {
        parent::__construct($input);
        $this->tenant = $tenant;
    }

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'ids' => 'required|array',
            'status' => 'required|in:'. Sku::STATUS_ON_SELL.','.Sku::STATUS_STOP_SELLING,
        ];
    }

    /**
     * @return Sku[]|Collection
     */
    public function getSkus()
    {
        return $this->skus;
    }

    protected function customValidate()
    {
        $this->skus = $this->tenant->skus()->whereIn('id', $this->input['ids'])->get();

        if (!$this->skus->count()) {
            $this->errors()->add('ids', self::ERROR_INVALID);
            return;
        }
    }
}
