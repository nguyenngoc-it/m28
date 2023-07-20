<?php

namespace Modules\Stock\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ExportStocks
{
    /** @var User $user */
    protected $user;
    protected $filter;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * ExportStocks constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user)
    {
        $this->user    = $user;
        $this->filter  = $filter;
        $this->filter['exportData'] = true;
        $this->builder = Service::stock()->listStocks($this->filter, $user);
    }

    function stockGenerator()
    {
        foreach ($this->builder->cursor() as $sku) {
            yield $sku;
        }
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->stockGenerator()))->export('stocks-export.xlsx', function (Stock $stock) {
            $sku = $stock->sku;
            return [
                trans('warehouse_area_code') => ($stock->warehouseArea) ? $stock->warehouseArea->code : '',
                trans('product_name') => ($stock->product) ? $stock->product->name : '',
                trans('sku') => isset($sku->code) ? $sku->code :'' ,
                trans('variant_name') => isset($sku->name) ? $sku->name :'' ,
                trans('in_stock') => $stock->real_quantity,
                trans('actual_count') => '',
            ];
        });
    }
}
