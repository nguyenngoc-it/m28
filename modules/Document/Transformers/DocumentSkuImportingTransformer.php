<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\DocumentSkuImporting;

class DocumentSkuImportingTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param DocumentSkuImporting $documentSkuImporting
     * @return mixed
     */
    public function transform($documentSkuImporting)
    {
        $sku= $documentSkuImporting->sku;
        if ($sku->weight==null||$sku->width==null||$sku->height==null||$sku->length==null||$sku->confirm_weight_volume==null){
            $index=false;
        }else{
            $index= true;
        }
        return [
            'document_sku_importing' => $documentSkuImporting,
            'sku' => $documentSkuImporting->sku,
            'index'=>$index,
            'warehouse_area' => $documentSkuImporting->warehouseArea,
        ];
    }
}
