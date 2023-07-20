<?php

namespace Modules\OrderPacking\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

class DownloadListItemsByIds
{
    use DownloadListItemsTrait;

    protected $orderPackingIds = [];
    /** @var Warehouse */
    protected $warehouse;

    /**
     * DownloadListItemsByIds constructor.
     * @param array $orderPackingIds
     * @param Warehouse $warehouse
     */
    public function __construct(array $orderPackingIds, Warehouse $warehouse)
    {
        $this->orderPackingIds = $orderPackingIds;
        $this->warehouse       = $warehouse;
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
        $builder = OrderPacking::query()->whereIn('id', $this->orderPackingIds);
        $sheets  = new SheetCollection([
            trans('list') => $this->makeTotalItemsDataSheet($builder, $this->warehouse->id),
            trans('order_list') => $this->makeOrderItemsDataSheet($builder, $this->warehouse->id, $this->orderPackingIds)
        ]);

        return (new FastExcel($sheets))->export($this->warehouse->code . '-items-packing-requests.xlsx');
    }
}
