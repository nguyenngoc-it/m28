<?php

namespace Modules\ShippingPartner\Services;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Http\UploadedFile;
use Modules\Location\Models\LocationShippingPartner;
use Modules\ShippingPartner\Commands\UploadExpectedTransportingPrice;
use Modules\ShippingPartner\Jobs\ImportExpectedTransportingPriceJob;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerExpectedTransportingPrice;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransporting;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceFactory;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiFactoryInterface;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ShippingPartnerService implements ShippingPartnerServiceInterface
{
    /**
     * @var ShippingPartnerApiFactoryInterface
     */
    protected $apiFactory;
    /** @var ExpectedTransportingPriceFactory $expectedTransportingPriceFactory */
    protected $expectedTransportingPriceFactory;

    /**
     * ShippingPartnerService constructor.
     * @param ShippingPartnerApiFactoryInterface $apiFactory
     * @param ExpectedTransportingPriceFactory $expectedTransportingPriceFactory
     */
    public function __construct(ShippingPartnerApiFactoryInterface $apiFactory, ExpectedTransportingPriceFactory $expectedTransportingPriceFactory)
    {
        $this->apiFactory                       = $apiFactory;
        $this->expectedTransportingPriceFactory = $expectedTransportingPriceFactory;
    }

    /**
     * Khởi tạo đối tượng query shipping_partners
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new ShippingPartnerQuery())->query($filter);
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return mixed|ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     */
    public function api(ShippingPartner $shippingPartner)
    {
        return $this->apiFactory->make($shippingPartner);
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return ExpectedTransporting
     * @throws ExpectedTransportingPrice\ExpectedTransportingPriceException
     */
    public function expectedTransporting(ShippingPartner $shippingPartner): ExpectedTransporting
    {
        return (new ExpectedTransporting($this->expectedTransportingPriceFactory->make($shippingPartner)));
    }


    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @return void
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function importExpectedTransportingPrice(ShippingPartner $shippingPartner, UploadedFile $file)
    {
        (new FastExcel)->import($file, function ($line) use ($shippingPartner) {
            if (!empty($line['max_weight'])) {
                ShippingPartnerExpectedTransportingPrice::updateOrCreate(
                    [
                        'tenant_id' => $shippingPartner->tenant_id,
                        'shipping_partner_id' => $shippingPartner->id,
                        'sender_ward_code' => trim($line['sender_ward']),
                        'sender_district_code' => trim($line['sender_district']),
                        'sender_province_code' => trim($line['sender_province']),
                        'receiver_ward_code' => trim($line['receiver_ward']),
                        'receiver_district_code' => trim($line['receiver_district']),
                        'receiver_province_code' => trim($line['receiver_province']),
                        'max_weight' => (float)$line['max_weight'],
                    ],
                    [
                        'price' => (float)$line['price']
                    ]
                );
            }
        });
        dispatch(new ImportExpectedTransportingPriceJob());
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @param $locationId
     * @return LocationShippingPartner
     */
    public function createLocationShippingPartner(ShippingPartner $shippingPartner, $locationId)
    {
        return LocationShippingPartner::firstOrCreate([
            'shipping_partner_id' => $shippingPartner->id
        ], [
            'location_id' => $locationId
        ]);
    }

    /**
     * @param array $inputs
     * @param User $user
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws ExpectedTransportingPrice\ExpectedTransportingPriceException
     */
    public function uploadExpectedTransportingPrice(array $inputs, User $user): array
    {
        return (new UploadExpectedTransportingPrice($inputs, $user))->handle();
    }
}
