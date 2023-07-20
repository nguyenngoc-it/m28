<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Auth\Services\Permission;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class AddSellerServicePackValidator extends Validator
{
    /** @var Location */
    protected $country;
    /** @var ServicePack $servicePack */
    protected $servicePack;

    public function __construct(array $input, ServicePack $servicePack, User $user = null)
    {
        parent::__construct($input, $user);
        $this->servicePack = $servicePack;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'seller_ids' => 'required|array',
        ];
    }

    protected function customValidate()
    {
        $sellerIds             = $this->input('seller_ids', []);
        $querySellerCurrentIds = Merchant::query()->whereIn('id', $sellerIds)
            ->where('tenant_id', $this->user->tenant_id)
            ->where('location_id', $this->servicePack->country_id);
        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $querySellerCurrentIds->whereIn('id', $this->user->merchants->pluck('id'));
        }
        $sellerCurrentIds = $querySellerCurrentIds->pluck('id')->all();

        if (array_diff($sellerIds, $sellerCurrentIds)) {
            $this->errors()->add('seller_ids', static::ERROR_INVALID);
        }
    }
}
