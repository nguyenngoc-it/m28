<?php

namespace Modules\Document\Services;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Document\Models\Document;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

interface DocumentDeliveryComparisonServiceInterface
{

    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @param User $user
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function checking(ShippingPartner $shippingPartner, UploadedFile $file, User $user);

    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @param User $user
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @return Document
     */
    public function create(ShippingPartner $shippingPartner, UploadedFile $file, User $user);

    /**
     * @param array $inputs
     * @param int $tenantId
     * @return LengthAwarePaginator|Collection
     */
    public function listing(array $inputs, int $tenantId);
}
