<?php

namespace Modules\OrderPacking\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadTempTrackingByFilter
{

    use DownloadTempTrackingTrait;

    protected $filter = [];
    /** @var ShippingPartner */
    protected $shippingPartner;

    /**
     * DownloadListItemsByIds constructor.
     * @param ShippingPartner $shippingPartner
     * @param array $filter
     */
    public function __construct(ShippingPartner $shippingPartner, array $filter)
    {
        $this->filter          = $filter;
        $this->shippingPartner = $shippingPartner;
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
        $sortBy     = Arr::pull($this->filter, 'sort_by', 'id');
        $sort       = Arr::pull($this->filter, 'sort', 'desc');

        $builder = Service::orderPacking()->query($this->filter)->getQuery();
        $builder->orderBy('order_packings' . '.' . $sortBy, $sort);

        return (new FastExcel($builder->get()))->export('temp_trackings.xlsx', function ($orderPacking) {
            $returnData = [];
            foreach ($this->shippingPartner->temp_tracking as $key => $tempTracking) {
                $returnData[$tempTracking['col']] = $this->getTempTrackingValue($orderPacking, $tempTracking['val']);
            }
            return $returnData;
        });
    }

}
