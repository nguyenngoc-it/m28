<?php

namespace Modules\OrderPacking\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

class DownloadListItemsByFilter
{
    use DownloadListItemsTrait;

    protected $filter = [];
    /** @var Warehouse */
    protected $warehouse;

    /**
     * DownloadListItemsByFilter constructor.
     * @param array $filter
     * @param Warehouse $warehouse
     */
    public function __construct(array $filter, Warehouse $warehouse)
    {
        $this->filter    = $filter;
        $this->warehouse = $warehouse;
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
        $sortBy = Arr::pull($this->filter, 'sort_by', 'id');
        $sort   = Arr::pull($this->filter, 'sort', 'desc');

        $builder = Service::orderPacking()->query($this->filter)->getQuery();
        $builder->orderBy('order_packings' . '.' . $sortBy, $sort);

        $sheets = new SheetCollection([
            trans('list') => $this->makeTotalItemsDataSheet($builder, $this->warehouse->id),
            trans('order_list') => $this->makeOrderItemsDataSheet($builder, $this->warehouse->id, [], $sortBy, $sort)
        ]);

        return (new FastExcel($sheets))->export($this->warehouse->code . '-items-packing-requests.xlsx');
    }

}
