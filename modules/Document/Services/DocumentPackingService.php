<?php

namespace Modules\Document\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentQuery;
use Modules\Document\Transformers\DocumentTransformer;
use Modules\Service;
use Modules\User\Models\User;

class DocumentPackingService implements DocumentPackingServiceInterface
{
    /**
     * Make query to document
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new DocumentQuery())->query($filter);
    }

    public function listing(array $filter, User $user)
    {
        $sortBy     = Arr::get($filter, 'sort_by', 'id');
        $sort       = Arr::get($filter, 'sort', 'desc');
        $page       = Arr::get($filter, 'page', config('paginate.page'));
        $perPage    = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $paginate   = Arr::get($filter, 'paginate', true);

        foreach (['sort', 'sort_by', 'page', 'per_page', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::document()->query($filter)->getQuery();
        if (!$user->can(Permission::OPERATION_HISTORY_PREPARATION)) {
            $query->where(function($q) use($user) {
                $q->where('creator_id', $user->id);
                $q->orWhere('verifier_id', $user->id);
            });
        }
        $query->with(['warehouse', 'creator', 'verifier']);
        $query->orderBy('documents' . '.' . $sortBy, $sort);

        if (!$paginate)
            return $query->get();

        $results = $query->paginate($perPage, 'documents.*', 'page', $page);

        return [
            'document_packings' => array_map(function ($document_packing) {
                return (new DocumentTransformer())->transform($document_packing);
            }, $results->items()),
            'pagination' => $results,
        ];
    }
}
