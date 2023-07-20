<?php

namespace Modules\ActiveCode\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\ActiveCode\Models\ActiveCode;

class ActiveCodeTransformers extends TransformerAbstract
{

    public function transform(ActiveCode $activeCode)
    {
        return [
            'id' => $activeCode->id,
            'tenant_id' => $activeCode->tenant_id,
            'service_combo_id' => $activeCode->service_combo_id,
            'code' => $activeCode->code,
            'type' => $activeCode->type,
            'status' => $activeCode->status,
            'expired_at' => $activeCode->expired_at,
            'created_at' => $activeCode->created_at
        ];
    }

}
