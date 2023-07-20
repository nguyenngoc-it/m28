<?php

namespace Modules\ImportHistory\Transformers;

use App\Base\Transformer;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\Warehouse\Models\WarehouseArea;

class ImportHistoryDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ImportHistory $importHistory
     * @return mixed
     */
    public function transform($importHistory)
    {
        $creator = $importHistory->creator;

        return compact('importHistory', 'creator');
    }
}
