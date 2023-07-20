<?php

namespace Modules\Document\Services;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Modules\Document\Models\Document;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

interface DocumentFreightBillInventoryServiceInterface
{

    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @param bool $confirm
     * @param User $user
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function create(ShippingPartner $shippingPartner, UploadedFile $file, User $user, bool $confirm = false): array;

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);

    /**
     * @param Document $document
     * @param array $filter
     * @param User $user
     * @return mixed
     */
    public function exportFreightBill(Document $document, array $filter, User $user);

    /**
     * @param Document $document
     * @param User $user
     * @return Document
     */
    public function confirm(Document $document, User $user);

    /**
     * Huỷ chứng từ
     *
     * @param Document $documentImporting
     * @param User $user
     * @return Document
     */
    public function cancel(Document $documentImporting, User $user);

    /**
     * @param Document $document
     * @param array $input
     * @param User $user
     * @return mixed
     */
    public function update(Document $document, $input = [], User $user);

    /**
     * @param Document $document
     * @param $input
     * @param User $user
     * @return mixed
     */
    public function updateInfoFreightBill(Document $document, $inputs = [], User $user);
}
