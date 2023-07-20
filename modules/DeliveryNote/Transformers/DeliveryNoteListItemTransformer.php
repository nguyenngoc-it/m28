<?php

namespace Modules\DeliveryNote\Transformers;

use App\Base\Transformer;
use Modules\DeliveryNote\Models\DeliveryNote;

class DeliveryNoteListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param DeliveryNote $deliveryNote
     * @return mixed
     */
    public function transform($deliveryNote)
    {
        return array_merge($deliveryNote->only(['warehouse', 'creator']), [
            'deliveryNote' => $deliveryNote,
        ]);
    }
}
