<?php

namespace Modules\ShippingPartner\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Illuminate\Support\Arr;
use Modules\ShippingPartner\Models\ShippingPartner;

class ShippingPartnerQuery extends ModelQueryFactory
{

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new ShippingPartner();
    }


    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyNameFilter(ModelQuery $query, $code)
    {
        $query->getQuery()
            ->where('shipping_partners.name', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $from = Arr::get($input, 'from');
        $to   = ($to = Arr::get($input, 'to')) ? $this->normalizeTimeEnd($to) : $to;
        $this->applyFilterRange($query, 'shipping_partners.created_at', $from, $to);
    }

}
