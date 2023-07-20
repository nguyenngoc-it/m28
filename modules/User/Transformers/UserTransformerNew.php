<?php
namespace Modules\User\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Tenant\Transformers\TenantTransformer;
use Modules\User\Models\User;

class UserTransformerNew extends TransformerAbstract
{

    public function __construct()
	{
		$this->setAvailableIncludes(['tenant']);
	}

	public function transform(User $user)
	{
	    return [
	        'id'   => (int) $user->id,
	        'name' => $user->name
	    ];
	}

    /**
     * Include Tenant
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeTenant(User $user)
    {
        $tenant = $user->tenant;

        return $this->item($tenant, new TenantTransformer);
    }
}