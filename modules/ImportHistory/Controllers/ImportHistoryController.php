<?php

namespace Modules\ImportHistory\Controllers;

use App\Base\Controller;
use Modules\ImportHistory\Models\ImportHistoryItem;
use Modules\ImportHistory\Transformers\ImportHistoryDetailTransformer;
use Modules\ImportHistory\Transformers\ImportHistoryListItemTransformer;
use Modules\ImportHistory\Validators\ListImportHistoryItemValidator;
use Modules\Service;
use Illuminate\Http\JsonResponse;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\ImportHistory\Transformers\ImportHistoryListTransformer;
use Modules\ImportHistory\Validators\ListImportHistoryValidator;

class ImportHistoryController extends Controller
{

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::importHistory()->listImportHistories($filter, $this->getAuthUser());

        return $this->response()->success([
            'importHistories' => array_map(function (ImportHistory $importHistory) {
                return (new ImportHistoryListTransformer())->transform($importHistory);
            }, $results->items()),
            'pagination' => $results
        ]);
    }

    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListImportHistoryValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
        }

        return $filter;
    }


    /**
     * @param ImportHistory $importHistory
     * @return JsonResponse
     */
    public function detail(ImportHistory $importHistory)
    {
        $data = (new ImportHistoryDetailTransformer())->transform($importHistory);
        return $this->response()->success($data);
    }

    /**
     * @param ImportHistory $importHistory
     * @return JsonResponse
     */
    public function items(ImportHistory $importHistory)
    {
        $filter = $this->request()->only(ListImportHistoryItemValidator::$keyRequests);
        $filter['import_history_id'] = $importHistory->id;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        $query = Service::importHistory()->importHistoryItemQuery($filter)
            ->getQuery()->with([
                'sku', 'warehouse', 'warehouseArea'
            ]);
        $items = $query->get();
        return $this->response()->success([
            'items' => $items->map(function (ImportHistoryItem $item) {
                return (new ImportHistoryListItemTransformer())->transform($item);
            })
        ]);
    }
}
