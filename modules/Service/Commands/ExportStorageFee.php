<?php

namespace Modules\Service\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Database\DBHelper;
use Illuminate\Database\Eloquent\Builder;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Service\Services\StorageFeeSkuStatisticQuery;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Generator;

class ExportStorageFee
{
    /** @var User $user */
    protected $user;
    protected $inputs = [];

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * ExportStocks constructor.
     * @param array $inputs
     * @param User $user
     */
    public function __construct(array $inputs, User $user)
    {
        $this->user    = $user;
        $this->inputs  = $inputs;
        $this->builder = (new StorageFeeSkuStatisticQuery())->query($this->inputs)->getQuery();
    }

    /**
     * @return Generator
     */
    public function makeGenerator()
    {
        $results = DBHelper::chunkByIdGenerator($this->builder, 200);

        foreach ($results as $storageFeeSkuStatistics) {
            $storageFeeSkuStatistics->load([
                'merchant',
                'sku'
            ]);

            foreach ($storageFeeSkuStatistics as $storageFeeSkuStatistic) {
                yield $this->makeRow($storageFeeSkuStatistic);
            }
        }
    }

    /**
     * @param StorageFeeSkuStatistic $storageFeeSkuStatistic
     * @return array
     */
    protected function makeRow(StorageFeeSkuStatistic $storageFeeSkuStatistic)
    {
        $size = $storageFeeSkuStatistic->sku ? $storageFeeSkuStatistic->sku->length * 1000 . ' x ' .
            $storageFeeSkuStatistic->sku->width * 1000 . ' x ' . $storageFeeSkuStatistic->sku->height * 1000 : '';
        return [
            trans('seller_name') => $storageFeeSkuStatistic->merchant_name,
            trans('warehouse') => $storageFeeSkuStatistic->warehouse_code,
            trans('storaged_at') => $storageFeeSkuStatistic->merchant->storaged_at->format('Y-m-d'),
            trans('closing_time') => $storageFeeSkuStatistic->closing_time->format('Y-m-d'),
            trans('sku') => $storageFeeSkuStatistic->sku_code,
            trans('size') . '(mm)' => $size,
            trans('inventory') => $storageFeeSkuStatistic->quantity,
            trans('volume') => $storageFeeSkuStatistic->volume,
            trans('price') => $storageFeeSkuStatistic->service_price,
            trans('amount') => $storageFeeSkuStatistic->fee,
        ];
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
        return (new FastExcel($this->makeGenerator()))->export('storage-sku-fees.xlsx');
    }
}
