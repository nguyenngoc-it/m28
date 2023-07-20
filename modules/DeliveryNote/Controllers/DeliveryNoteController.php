<?php

namespace Modules\DeliveryNote\Controllers;

use App\Base\Controller;
use Modules\DeliveryNote\Models\DeliveryNote;
use Modules\DeliveryNote\Transformers\DeliveryNoteDetailTransformer;
use Modules\DeliveryNote\Transformers\DeliveryNoteListItemTransformer;
use Modules\DeliveryNote\Validators\CreateDeliveryNoteValidator;
use Modules\DeliveryNote\Validators\ListDeliveryNoteValidator;
use Modules\Service;

class DeliveryNoteController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $user = $this->getAuthUser();
        $input = $this->request()->only(CreateDeliveryNoteValidator::$acceptKeys);
        $validator = new CreateDeliveryNoteValidator($input, $user);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $input['skus'] = $validator->getSkus();
        $deliveryNote = Service::deliveryNote()->createDeliveryNote($input, $user);

        return $this->response()->success(compact('deliveryNote'));
    }
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::deliveryNote()->listDeliveryNote($filter);

        return $this->response()->success([
            'deliveryNotes' => array_map(function ($deliveryNote) {
                 return (new DeliveryNoteListItemTransformer())->transform($deliveryNote);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * Tạo filter để query delivery note
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListDeliveryNoteValidator::$acceptKeys);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
            unset($filter['created_at_from']);
            unset($filter['created_at_to']);
        }

        return $filter;
    }

    /**
     * @param DeliveryNote $deliveryNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(DeliveryNote $deliveryNote)
    {
         $deliveryNote = (new DeliveryNoteDetailTransformer())->transform($deliveryNote);
         return $this->response()->success(compact('deliveryNote'));
    }
}
