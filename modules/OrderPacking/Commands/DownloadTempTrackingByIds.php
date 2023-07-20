<?php

namespace Modules\OrderPacking\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadTempTrackingByIds
{
    use DownloadTempTrackingTrait;

    protected $orderPackingIds = [];
    /** @var ShippingPartner */
    protected $shippingPartner;

    /**
     * DownloadListItemsByIds constructor.
     * @param ShippingPartner $shippingPartner
     * @param array $orderPackingIds
     */
    public function __construct(ShippingPartner $shippingPartner, array $orderPackingIds)
    {
        $this->orderPackingIds = $orderPackingIds;
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
        $builder = OrderPacking::query()->whereIn('id', $this->orderPackingIds);
        return (new FastExcel($builder->get()))->export('temp_trackings.xlsx', function ($orderPacking) {
            $returnData = [];
            foreach ($this->shippingPartner->temp_tracking as $key => $tempTracking) {
                $returnData[$tempTracking['col']] = $this->getTempTrackingValue($orderPacking, $tempTracking['val']);
            }
            return $returnData;
        });
    }
}
