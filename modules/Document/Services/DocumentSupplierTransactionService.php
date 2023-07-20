<?php

namespace Modules\Document\Services;

use Illuminate\Support\Arr;
use Modules\Document\Commands\CreateDocumentSupplierTransaction;
use Modules\Document\Models\Document;
use Modules\Service;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\User;
class DocumentSupplierTransactionService implements DocumentSupplierTransactionServiceInterface
{
    /**
     * @param Supplier $supplier
     * @param array $data
     * @param User $user
     * @return Document
     */
    public function create(Supplier $supplier, array $data, User $user) : Document
    {
        return (new CreateDocumentSupplierTransaction($supplier, $data, $user))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $sortBy    = Arr::pull($filter, 'sort_by', 'id');
        $sortByIds = Arr::pull($filter, 'sort_by_ids', false);
        $sort      = Arr::pull($filter, 'sort', 'desc');
        $page      = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage   = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate  = Arr::pull($filter, 'paginate', true);
        $ids       = Arr::get($filter, 'ids', []);


        $query = Service::document()->query($filter)->getQuery()->where('type', Document::TYPE_SUPPLIER_PAYMENT);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('documents' . '.' . $sortBy, $sort);
        }
        $query->with(['supplier', 'creator']);

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['documents.*'], 'page', $page);

        return [
            'documents' => $results->items(),
            'pagination' => $results,
        ];
    }
}
