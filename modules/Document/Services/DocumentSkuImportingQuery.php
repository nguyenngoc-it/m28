<?php

namespace Modules\Document\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;

class DocumentSkuImportingQuery extends ModelQueryFactory
{

    protected $joins = [
        'skus' => ['document_sku_importings.sku_id', '=', 'skus.id'],
    ];

    protected function newModel()
    {
        return new DocumentSkuImporting();
    }

    /**
     * Filter by created time
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'document_sku_importings.created_at', $input);
    }

    /**
     * @param ModelQuery $query
     * @param $sku_code
     */
    protected function applySkuCodeFilter(ModelQuery $query, $sku_code)
    {
        $query->join('skus')
            ->where('skus.code', trim($sku_code));
    }

    /**
     * @param ModelQuery $query
     * @param $sku_ref
     */
    protected function applySkuRefFilter(ModelQuery $query, $sku_ref)
    {
        $query->join('skus')
            ->where('skus.ref', trim($sku_ref));
    }
}
