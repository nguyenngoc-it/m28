<?php

namespace Modules\Document\Services;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Document\Commands\CheckingDocumentDeliveryComparison;
use Modules\Document\Commands\CreatingDocumentDeliveryComparison;
use Modules\Document\Models\Document;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

class DocumentDeliveryComparisonService implements DocumentDeliveryComparisonServiceInterface
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
    public function checking(ShippingPartner $shippingPartner, UploadedFile $file, User $user)
    {
        return (new CheckingDocumentDeliveryComparison($shippingPartner, $file, $user))->handle();
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @param User $user
     * @return Document
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws IOException
     */
    public function create(ShippingPartner $shippingPartner, UploadedFile $file, User $user)
    {
        return (new CreatingDocumentDeliveryComparison($shippingPartner, $file, $user))->handle();
    }

    /**
     * @param array $filter
     * @param int $tenantId
     * @return LengthAwarePaginator|Collection
     */
    public function listing(array $filter, $tenantId)
    {
        $sortBy   = Arr::pull($filter, 'sort_by', 'id');
        $sort     = Arr::pull($filter, 'sort', 'desc');
        $page     = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage  = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate = Arr::pull($filter, 'paginate', true);

        $query = Service::document()->query($filter)->getQuery()
            ->where('documents.tenant_id', $tenantId)
            ->where('documents.type', Document::TYPE_DELIVERY_COMPARISON)
            ->orderBy('documents' . '.' . $sortBy, $sort);
        $query->with(['shippingPartner', 'creator']);

        if (!$paginate) {
            return $query->get();
        }

        return $query->paginate($perPage, ['documents.*'], 'page', $page);
    }
}
