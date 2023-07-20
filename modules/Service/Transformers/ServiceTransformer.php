<?php
namespace Modules\Service\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Service\Models\Service;

class ServiceTransformer extends TransformerAbstract
{
	public function transform(Service $service)
	{
	    return [
	        'id'     => (int) $service->id,
	        'name'   => $service->name,
	        'type'   => $service->type,
	        'code'   => $service->code,
	    ];
	}
}
