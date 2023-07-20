<?php
namespace Modules\Tenant\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Tenant\Models\Tenant;

class TenantTransformer extends TransformerAbstract
{
	public function transform(Tenant $tenant)
	{
	    return [
	        'id'   => (int) $tenant->id,
	        'code' => $tenant->code
	    ];
	}
}