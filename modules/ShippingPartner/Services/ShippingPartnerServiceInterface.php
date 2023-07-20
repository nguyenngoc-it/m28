<?php

namespace Modules\ShippingPartner\Services;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Http\UploadedFile;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransporting;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\User\Models\User;

interface ShippingPartnerServiceInterface
{
    /**
     * Khởi tạo đối tượng query shipping_partners
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);


    /**
     * @param ShippingPartner $shippingPartner
     * @return mixed|ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     */
    public function api(ShippingPartner $shippingPartner);

    /**
     * @param ShippingPartner $shippingPartner
     * @return ExpectedTransporting
     * @throws ExpectedTransportingPrice\ExpectedTransportingPriceException
     */
    public function expectedTransporting(ShippingPartner $shippingPartner): ExpectedTransporting;

    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @return void
     */
    public function importExpectedTransportingPrice(ShippingPartner $shippingPartner, UploadedFile $file);

    /**
     * @param array $inputs
     * @param User $user
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function uploadExpectedTransportingPrice(array $inputs, User $user): array;
}
