<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\Product\Models\Sku;
use Modules\Service;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadRefSkuByFilter
{
    /**
     * @var array
     */
    protected $querySkus;

    /**
     * DownloadRefSkuByFilter constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $builder         = Service::product()->query($filter)->getQuery();
        $this->querySkus = Sku::query()->whereIn('product_id', $builder->select('products.id'))->orderBy('skus.product_id');
    }

    function skuGenerator()
    {
        foreach ($this->querySkus->cursor() as $sku) {
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
        return (new FastExcel($this->skuGenerator()))->export('package-export.xlsx', function (Sku $sku) {
            return [
                trans('product_code') => $sku->product->code,
                trans('product_name') => $sku->product->name,
                trans('product_description') => $sku->product->description,
                trans('sku') => $sku->code,
                trans('variant_name') => $sku->name,
                trans('ref_code') => $sku->ref
            ];
        });
    }
}
